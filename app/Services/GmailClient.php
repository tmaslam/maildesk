<?php

namespace App\Services;

use App\Models\Mailbox;
use RuntimeException;

/**
 * Minimal Gmail REST API client using plain curl — no SDK, no paid services.
 * Gmail API is free for normal usage volumes.
 */
class GmailClient
{
    const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const API       = 'https://gmail.googleapis.com/gmail/v1/users/me';
    const SCOPES    = 'https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.send';

    public static function oauthUrl(int $mailboxId): string
    {
        $g = config('services.google');
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => $g['client_id'],
            'redirect_uri'  => $g['redirect'],
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => self::signState($mailboxId),
        ]);
    }

    /**
     * The OAuth callback is unauthenticated, so the state carries an
     * HMAC-signed mailbox id + timestamp (valid for 10 minutes, no replay).
     */
    public static function signState(int $mailboxId): string
    {
        $payload = $mailboxId . '.' . time();
        return $payload . '.' . hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    /** @return int|null the mailbox id, or null if invalid or expired */
    public static function verifyState(?string $state): ?int
    {
        $parts = explode('.', (string) $state);
        if (count($parts) !== 3) {
            return null;
        }
        [$id, $ts, $sig] = $parts;
        if (!hash_equals(hash_hmac('sha256', "$id.$ts", (string) config('app.key')), $sig)) {
            return null;
        }
        if ((int) $ts < time() - 600) {
            return null;
        }
        return (int) $id;
    }

    public static function exchangeCode(string $code): array
    {
        $g = config('services.google');
        return self::http('POST', self::TOKEN_URL, http_build_query([
            'code'          => $code,
            'client_id'     => $g['client_id'],
            'client_secret' => $g['client_secret'],
            'redirect_uri'  => $g['redirect'],
            'grant_type'    => 'authorization_code',
        ]), ['Content-Type: application/x-www-form-urlencoded']);
    }

    public static function accessToken(Mailbox $mb): string
    {
        if ($mb->access_token && $mb->token_expires_at && $mb->token_expires_at->gt(now()->addMinute())) {
            return $mb->access_token;
        }
        $g = config('services.google');
        $res = self::http('POST', self::TOKEN_URL, http_build_query([
            'refresh_token' => $mb->refresh_token,
            'client_id'     => $g['client_id'],
            'client_secret' => $g['client_secret'],
            'grant_type'    => 'refresh_token',
        ]), ['Content-Type: application/x-www-form-urlencoded']);

        if (empty($res['access_token'])) {
            throw new RuntimeException("Token refresh failed for {$mb->email}");
        }
        $mb->forceFill([
            'access_token'     => $res['access_token'],
            'token_expires_at' => now()->addSeconds((int) ($res['expires_in'] ?? 3600) - 30),
        ])->save();

        return $mb->access_token;
    }

    public static function profile(string $accessToken): array
    {
        return self::http('GET', self::API . '/profile', null, ["Authorization: Bearer $accessToken"]);
    }

    public static function listMessageIds(Mailbox $mb, string $q, int $cap = 300): array
    {
        $ids = [];
        $pageToken = null;
        do {
            $url = self::API . '/messages?' . http_build_query(array_filter([
                'q' => $q, 'maxResults' => 100, 'pageToken' => $pageToken,
            ]));
            $res = self::http('GET', $url, null, ['Authorization: Bearer ' . self::accessToken($mb)]);
            foreach ($res['messages'] ?? [] as $m) {
                $ids[] = $m['id'];
            }
            $pageToken = $res['nextPageToken'] ?? null;
        } while ($pageToken && count($ids) < $cap);

        return $ids;
    }

    public static function getMessage(Mailbox $mb, string $id): array
    {
        return self::http('GET', self::API . "/messages/{$id}?format=full",
            null, ['Authorization: Bearer ' . self::accessToken($mb)]);
    }

    public static function sendRaw(Mailbox $mb, string $rawMime, ?string $threadId): array
    {
        $body = ['raw' => self::b64urlEncode($rawMime)];
        if ($threadId) {
            $body['threadId'] = $threadId;
        }
        return self::http('POST', self::API . '/messages/send', json_encode($body), [
            'Authorization: Bearer ' . self::accessToken($mb),
            'Content-Type: application/json',
        ]);
    }

    /**
     * Register Gmail push notifications: Gmail will publish to the Pub/Sub
     * topic whenever this mailbox receives mail. Expires in ~7 days, so the
     * sync cron renews it automatically.
     */
    public static function watch(Mailbox $mb): array
    {
        $topic = config('services.google.push_topic');
        $res = self::http('POST', self::API . '/watch', json_encode([
            'topicName' => $topic,
            'labelIds'  => ['INBOX', 'SPAM'],
            'labelFilterBehavior' => 'INCLUDE',
        ]), [
            'Authorization: Bearer ' . self::accessToken($mb),
            'Content-Type: application/json',
        ]);

        if (!empty($res['expiration'])) {
            $mb->forceFill([
                'watch_expires_at' => date('Y-m-d H:i:s', (int) ($res['expiration'] / 1000)),
            ])->save();
        }
        return $res;
    }

    public static function getAttachment(Mailbox $mb, string $msgId, string $attId): array
    {
        return self::http('GET', self::API . "/messages/{$msgId}/attachments/" . rawurlencode($attId),
            null, ['Authorization: Bearer ' . self::accessToken($mb)]);
    }

    // ---------- Parsing helpers ----------

    public static function header(array $message, string $name): ?string
    {
        foreach ($message['payload']['headers'] ?? [] as $h) {
            if (strcasecmp($h['name'], $name) === 0) {
                return $h['value'];
            }
        }
        return null;
    }

    /** "Some Name <a@b.com>" -> ['Some Name', 'a@b.com'] */
    public static function parseAddress(?string $from): array
    {
        $from = trim((string) $from);
        if (preg_match('/^"?(.*?)"?\s*<([^>]+)>$/', $from, $m)) {
            return [trim(mb_decode_mimeheader($m[1])), strtolower(trim($m[2]))];
        }
        return ['', strtolower(trim($from, '<> '))];
    }

    public static function extractBody(array $message): string
    {
        $plain = '';
        $html = '';
        $walk = function (array $part) use (&$walk, &$plain, &$html) {
            $mime = $part['mimeType'] ?? '';
            $data = $part['body']['data'] ?? null;
            if ($data && $mime === 'text/plain' && $plain === '') {
                $plain = self::b64urlDecode($data);
            } elseif ($data && $mime === 'text/html' && $html === '') {
                $html = self::b64urlDecode($data);
            }
            foreach ($part['parts'] ?? [] as $p) {
                $walk($p);
            }
        };
        $walk($message['payload'] ?? []);

        if ($plain !== '') {
            return trim($plain);
        }
        if ($html !== '') {
            $html = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', '', $html);
            $html = preg_replace('/<(br|\/p|\/div|\/tr)[^>]*>/i', "\n", $html);
            return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return trim((string) ($message['snippet'] ?? ''));
    }

    public static function collectAttachments(array $message): array
    {
        $out = [];
        $walk = function (array $part) use (&$walk, &$out) {
            if (!empty($part['filename']) && !empty($part['body']['attachmentId'])) {
                $out[] = [
                    'filename'      => $part['filename'],
                    'mime_type'     => $part['mimeType'] ?? 'application/octet-stream',
                    'attachment_id' => $part['body']['attachmentId'],
                    'size'          => (int) ($part['body']['size'] ?? 0),
                ];
            }
            foreach ($part['parts'] ?? [] as $p) {
                $walk($p);
            }
        };
        $walk($message['payload'] ?? []);

        return $out;
    }

    public static function b64urlEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }

    private static function http(string $method, string $url, ?string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Google API request failed: $err");
        }
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $json = json_decode($resp, true) ?? [];
        if ($status >= 400) {
            throw new RuntimeException("Google API error HTTP $status: " . substr($resp, 0, 500));
        }
        return $json;
    }
}
