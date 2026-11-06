<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Mailbox;
use App\Services\GmailClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gmail-style "Compose": start a brand-new conversation from one of the
 * connected brand Gmail accounts. The sent message becomes a MailDesk thread,
 * so the customer's reply lands in the same conversation.
 */
class ComposeController extends Controller
{
    private const MAX_TOTAL_BYTES = 20 * 1024 * 1024; // keep well under Gmail's 25 MB

    public function send(Request $request)
    {
        $data = $request->validate([
            'mailbox_id'    => ['required', 'integer', 'exists:mailboxes,id'],
            'to'            => ['required', 'string', 'max:2000'],
            'cc'            => ['nullable', 'string', 'max:2000'],
            'bcc'           => ['nullable', 'string', 'max:2000'],
            'subject'       => ['nullable', 'string', 'max:300'],
            'body'          => ['required', 'string', 'max:50000'],
            'attachments'   => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480'],
        ], [
            'attachments.*.max' => 'Each attachment must be under 20 MB.',
            'body.required'     => 'Write a message first.',
        ]);

        $mb = Mailbox::findOrFail($data['mailbox_id']);
        if (!$mb->isConnected() || !$mb->email) {
            return $this->fail('That brand is not connected to Gmail yet.');
        }

        $to = self::addresses($data['to']);
        $cc = self::addresses($data['cc'] ?? '');
        $bcc = self::addresses($data['bcc'] ?? '');
        foreach ([$to, $cc, $bcc] as $list) {
            if ($list === null) {
                return $this->fail('One of the email addresses is not valid. Separate several with commas.');
            }
        }
        if (!$to) {
            return $this->fail('Add at least one recipient in “To”.');
        }
        if (count($to) + count($cc) + count($bcc) > 25) {
            return $this->fail('Too many recipients (25 max).');
        }

        $files = $request->file('attachments', []);
        $total = array_sum(array_map(fn ($f) => $f->getSize(), $files));
        if ($total > self::MAX_TOTAL_BYTES) {
            return $this->fail('Attachments are too large together (20 MB max).');
        }

        // No line breaks in headers (header injection).
        $subject = trim(preg_replace('/[\r\n]+/', ' ', (string) ($data['subject'] ?? '')));
        $bodyText = rtrim($data['body']);
        if ($mb->signature) {
            $bodyText .= "\n\n" . $mb->signature;
        }

        $headers = [
            'From: ' . self::encodeName($mb->brand_name) . " <{$mb->email}>",
            'To: ' . implode(', ', $to),
        ];
        if ($cc) {
            $headers[] = 'Cc: ' . implode(', ', $cc);
        }
        if ($bcc) {
            $headers[] = 'Bcc: ' . implode(', ', $bcc);
        }
        $headers[] = 'Subject: ' . self::encodeText($subject);
        $headers[] = 'MIME-Version: 1.0';

        if (!$files) {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $raw = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($bodyText));
        } else {
            $boundary = 'md_' . bin2hex(random_bytes(12));
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            $raw = implode("\r\n", $headers) . "\r\n\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($bodyText));
            foreach ($files as $f) {
                $name = str_replace(['"', "\r", "\n"], '', $f->getClientOriginalName()) ?: 'attachment';
                $mime = $f->getMimeType() ?: 'application/octet-stream';
                $encName = self::encodeText($name);
                $raw .= "--{$boundary}\r\n"
                    . "Content-Type: {$mime}; name=\"{$encName}\"\r\n"
                    . "Content-Disposition: attachment; filename=\"{$encName}\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode((string) file_get_contents($f->getRealPath())));
            }
            $raw .= "--{$boundary}--\r\n";
        }

        @set_time_limit(120);
        try {
            $sent = GmailClient::sendRaw($mb, $raw, null);
        } catch (\Throwable $e) {
            Log::error('Compose send failed: ' . $e->getMessage());
            return $this->fail('Sending failed — check the brand’s Gmail connection and try again.');
        }

        // First "To" person is the customer of this conversation.
        $customer = $to[0];
        $thread = EmailThread::firstOrCreate(
            ['mailbox_id' => $mb->id, 'gmail_thread_id' => $sent['threadId'] ?? ('local-' . uniqid())],
            ['subject' => $subject !== '' ? $subject : '(no subject)', 'unread' => false]
        );
        $thread->forceFill([
            'customer_email'  => strtolower($customer),
            'last_message_at' => now(),
            'snippet'         => mb_substr(preg_replace('/\s+/u', ' ', $bodyText), 0, 280),
            'unread'          => false,
        ])->save();

        $message = EmailMessage::firstOrCreate(
            ['gmail_id' => $sent['id'] ?? ('local-' . uniqid())],
            [
                'thread_id'       => $thread->id,
                'direction'       => 'out',
                'sender_name'     => $mb->brand_name,
                'sent_by_user_id' => $request->user()->id,
                'body_text'       => $bodyText,
                'sent_at'         => now(),
            ]
        );

        // Record attachments so they can be downloaded from the conversation.
        if ($files && !empty($sent['id'])) {
            try {
                foreach (GmailClient::collectAttachments(GmailClient::getMessage($mb, $sent['id'])) as $att) {
                    $message->attachments()->create($att + ['gmail_message_id' => $sent['id']]);
                }
            } catch (\Throwable $e) {
                Log::warning('Could not record compose attachments: ' . $e->getMessage());
            }
        }

        $url = route('thread', $thread);
        session()->flash('status', 'Message sent from ' . $mb->brand_name . '.');
        return $request->expectsJson()
            ? response()->json(['ok' => true, 'redirect' => $url])
            : redirect($url);
    }

    private function fail(string $msg)
    {
        return request()->expectsJson()
            ? response()->json(['message' => $msg], 422)
            : back()->withErrors(['compose' => $msg])->withInput();
    }

    /** "a@x.com, Bob <b@y.com>; c@z.com" → bare addresses; null if any is invalid. */
    private static function addresses(string $input): ?array
    {
        $out = [];
        foreach (preg_split('/[,;\n]+/', $input) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/<([^>]+)>/', $part, $m)) {
                $part = trim($m[1]);
            }
            if (!filter_var($part, FILTER_VALIDATE_EMAIL)) {
                return null;
            }
            $out[] = strtolower($part);
        }
        return array_values(array_unique($out));
    }

    private static function encodeText(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function encodeName(string $s): string
    {
        $s = str_replace(['"', "\r", "\n"], '', $s);
        return preg_match('/[^\x20-\x7E]/', $s) ? self::encodeText($s) : '"' . $s . '"';
    }
}
