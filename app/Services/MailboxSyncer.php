<?php

namespace App\Services;

use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Mailbox;
use Illuminate\Support\Facades\Log;

/**
 * Pulls new messages from a connected Gmail account into the local database.
 * Customer email addresses are stored in the DB but only ever shown to admins.
 */
class MailboxSyncer
{
    /**
     * @param bool $fullHistory true = "Pull recent emails" button: fetch all
     *   recent mail. false (default) = only mail that arrived since the mailbox
     *   was connected / last synced.
     * @return int number of newly imported messages
     */
    public function sync(Mailbox $mb, bool $fullHistory = false): int
    {
        if (!$mb->isConnected() || !$mb->active) {
            return 0;
        }

        if ($fullHistory) {
            // On-demand historical pull (capped so one click stays reasonable).
            $queries = ['in:inbox', 'in:sent', 'in:spam'];
            $cap = 500;
        } else {
            // Only mail received since connection / last successful sync.
            $since = $mb->last_synced_at ?? $mb->created_at ?? now();
            $after = $since->copy()->subMinutes(5)->timestamp;
            $queries = ["in:inbox after:$after", "in:sent after:$after", "in:spam after:$after"];
            $cap = 300;
        }

        $imported = 0;
        $failures = 0;
        // Spam is synced too: customer mail wrongly flagged by Gmail still
        // reaches the portal inbox (marked with a Spam badge).
        foreach ($queries as $q) {
            $ids = GmailClient::listMessageIds($mb, $q, $cap);
            $known = EmailMessage::whereIn('gmail_id', $ids)->pluck('gmail_id')->flip();

            foreach ($ids as $gmailId) {
                if (isset($known[$gmailId])) {
                    continue;
                }
                // One bad message (broken encoding, concurrent sync race) must not
                // wedge the whole mailbox: log it and keep importing the rest.
                try {
                    $this->importMessage($mb, GmailClient::getMessage($mb, $gmailId));
                    $imported++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    // Another sync (second open tab) imported it first — fine.
                } catch (\Throwable $e) {
                    $failures++;
                    Log::warning("Skipped Gmail message $gmailId for {$mb->email}: " . $e->getMessage());
                }
            }
        }

        if ($failures === 0 || $imported > 0) {
            $mb->forceFill(['last_synced_at' => now()])->save();
        }

        return $imported;
    }

    private function importMessage(Mailbox $mb, array $msg): void
    {
        [$fromName, $fromEmail] = GmailClient::parseAddress(GmailClient::header($msg, 'From'));
        $direction = $fromEmail === strtolower((string) $mb->email) ? 'out' : 'in';
        $sentAt = date('Y-m-d H:i:s', (int) (($msg['internalDate'] ?? 0) / 1000));
        $subject = self::cleanUtf8(mb_substr(
            mb_decode_mimeheader((string) (GmailClient::header($msg, 'Subject') ?? '(no subject)')), 0, 500));
        $body = self::cleanUtf8(GmailClient::extractBody($msg));

        $thread = EmailThread::firstOrCreate(
            ['mailbox_id' => $mb->id, 'gmail_thread_id' => $msg['threadId']],
            ['subject' => $subject, 'unread' => false]
        );

        if ($direction === 'in') {
            // Messages arrive newest-first, so only let the newest incoming
            // message define who the customer is (name + reply-to address).
            $newestIn = $thread->messages()->where('direction', 'in')->max('sent_at');
            if (!$newestIn || $sentAt >= $newestIn) {
                $thread->customer_name = $fromName !== '' ? self::cleanUtf8($fromName) : $thread->customer_name;
                $thread->customer_email = $fromEmail;
            }
        } elseif (!$thread->customer_email) {
            // Brand-initiated thread: customer is the To: recipient.
            [$toName, $toEmail] = GmailClient::parseAddress(GmailClient::header($msg, 'To'));
            $thread->customer_name = $toName !== '' ? self::cleanUtf8($toName) : $thread->customer_name;
            $thread->customer_email = $toEmail;
        }

        if (!$thread->last_message_at || $thread->last_message_at->lte($sentAt)) {
            $thread->last_message_at = $sentAt;
            $thread->snippet = mb_substr(preg_replace('/\s+/u', ' ', $body) ?? '', 0, 280);
            if ($direction === 'in') {
                $thread->unread = true;
                // Newest incoming message decides the spam badge.
                $thread->spam = in_array('SPAM', $msg['labelIds'] ?? [], true);
            }
        }
        $thread->save();

        $record = EmailMessage::create([
            'thread_id'         => $thread->id,
            'gmail_id'          => $msg['id'],
            'direction'         => $direction,
            'sender_name'       => $direction === 'in' ? self::cleanUtf8($fromName) : $mb->brand_name,
            'body_text'         => $body,
            'message_id_header' => mb_substr((string) GmailClient::header($msg, 'Message-ID'), 0, 500),
            'sent_at'           => $sentAt,
        ]);

        foreach (GmailClient::collectAttachments($msg) as $att) {
            $att['filename'] = self::cleanUtf8($att['filename']);
            $record->attachments()->create($att + ['gmail_message_id' => $msg['id']]);
        }
    }

    /** Strip byte sequences MySQL's utf8mb4 would reject. */
    private static function cleanUtf8(string $s): string
    {
        return (string) mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    }
}
