<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Web Push sender (VAPID, RFC 8292) with no external packages.
 * Pushes carry no payload, so nothing needs encrypting and no customer
 * data leaves the server: the service worker asks /ping for the count.
 */
class WebPush
{
    public static function enabled(): bool
    {
        return (bool) (config('services.webpush.public_key') && config('services.webpush.private_key'));
    }

    public static function publicKey(): ?string
    {
        return config('services.webpush.public_key');
    }

    /** Wake every subscribed device. Returns how many pushes were accepted. */
    public static function notifyAll(): int
    {
        if (!self::enabled()) {
            return 0;
        }
        $sent = 0;
        foreach (PushSubscription::all() as $sub) {
            try {
                $code = self::send($sub->endpoint);
                if ($code === 404 || $code === 410) {
                    $sub->delete(); // phone uninstalled the app or revoked permission
                } elseif ($code >= 200 && $code < 300) {
                    $sent++;
                } else {
                    Log::warning("Web push to sub {$sub->id} returned HTTP {$code}");
                }
            } catch (\Throwable $e) {
                Log::warning("Web push to sub {$sub->id} failed: " . $e->getMessage());
            }
        }
        return $sent;
    }

    private static function send(string $endpoint): int
    {
        $parts = parse_url($endpoint);
        $aud = $parts['scheme'] . '://' . $parts['host'];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'TTL: 86400',
                'Urgency: high',
                'Content-Length: 0',
                'Authorization: vapid t=' . self::jwt($aud) . ', k=' . self::publicKey(),
            ],
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code === 0) {
            throw new \RuntimeException($err ?: 'no response');
        }
        return $code;
    }

    private static function jwt(string $aud): string
    {
        $header = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64(json_encode([
            'aud' => $aud,
            'exp' => time() + 12 * 3600,
            'sub' => config('services.webpush.subject', 'mailto:1dollardigitizing@gmail.com'),
        ]));
        $input = "$header.$claims";

        $pem = base64_decode((string) config('services.webpush.private_key'));
        $key = openssl_pkey_get_private($pem);
        if (!$key || !openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('VAPID signing failed');
        }
        return $input . '.' . self::b64(self::derToRaw($der));
    }

    /** OpenSSL returns an ASN.1 DER signature; JWT ES256 wants raw r||s (64 bytes). */
    private static function derToRaw(string $der): string
    {
        $pos = 2; // SEQUENCE tag + length
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7f;
        }
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            $pos++; // INTEGER tag
            $len = ord($der[$pos++]);
            $int = substr($der, $pos, $len);
            $pos += $len;
            $out .= str_pad(ltrim($int, "\x00"), 32, "\x00", STR_PAD_LEFT);
        }
        return $out;
    }

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
}
