<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Mailbox;
use App\Services\GmailClient;
use App\Services\MailboxSyncer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->get('q'));
        $scope = (string) $request->get('scope');
        $folder = ($request->get('folder') === 'sent' || $scope === 'sent') ? 'sent' : 'inbox';
        $words = trim((string) $request->get('words'));
        $exclude = trim((string) $request->get('not'));

        $threads = EmailThread::with('mailbox')
            ->when($folder === 'sent', fn ($q) => $q
                ->whereHas('messages', fn ($m) => $m->where('direction', 'out'))
                ->with('latestOut.sentBy'))
            ->when($request->integer('mailbox'), fn ($q, $id) => $q->where('mailbox_id', $id))
            ->when($folder === 'inbox' && ($request->boolean('unread') || $scope === 'unread'), fn ($q) => $q->where('unread', true))
            ->when($folder === 'inbox' && ($request->boolean('read') || $scope === 'read'), fn ($q) => $q->where('unread', false))
            ->when($scope === 'spam', fn ($q) => $q->where('spam', true))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(fn ($w) => $w
                    ->where('subject', 'like', "%$term%")
                    ->orWhere('customer_name', 'like', "%$term%")
                    ->orWhere('snippet', 'like', "%$term%"));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $from = trim((string) $request->get('from'));
                $q->where(function ($w) use ($from, $request) {
                    $w->where('customer_name', 'like', "%$from%");
                    if ($request->user()->isAdmin()) {
                        $w->orWhere('customer_email', 'like', "%$from%");
                    }
                });
            })
            ->when($request->filled('subject'), fn ($q) => $q->where('subject', 'like', '%' . trim((string) $request->get('subject')) . '%'))
            ->when($words !== '', function ($q) use ($words) {
                $q->where(fn ($w) => $w
                    ->where('subject', 'like', "%$words%")
                    ->orWhere('snippet', 'like', "%$words%")
                    ->orWhereHas('messages', fn ($m) => $m->where('body_text', 'like', "%$words%")));
            })
            ->when($exclude !== '', fn ($q) => $q
                ->whereRaw("COALESCE(subject,'') NOT LIKE ?", ["%$exclude%"])
                ->whereRaw("COALESCE(snippet,'') NOT LIKE ?", ["%$exclude%"]))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('last_message_at', '>=', $request->get('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('last_message_at', '<=', $request->get('date_to')))
            ->when($request->boolean('has_att'), fn ($q) => $q->whereHas('messages', fn ($m) => $m->whereHas('attachments')))
            ->orderByDesc('last_message_at')
            ->paginate(20)->withQueryString();

        // Inbox stats for the current brand filter (search/unread filters aside).
        $scope = EmailThread::query()
            ->when($request->integer('mailbox'), fn ($q, $id) => $q->where('mailbox_id', $id));
        $total = (clone $scope)->count();
        $unread = (clone $scope)->where('unread', true)->count();
        $stats = [
            'total'  => $total,
            'unread' => $unread,
            'read'   => $total - $unread,
            'sent'   => \App\Models\EmailMessage::where('direction', 'out')
                ->when($request->integer('mailbox'), fn ($q, $id) => $q
                    ->whereIn('thread_id', EmailThread::where('mailbox_id', $id)->select('id')))
                ->count(),
        ];

        return view('inbox.index', [
            'mailboxes' => $this->sidebarMailboxes(),
            'threads'   => $threads,
            'stats'     => $stats,
            'folder'    => $folder,
            'latest'    => (string) EmailThread::max('updated_at'),
        ]);
    }

    /** Cheap DB-only poll so the page can check for new mail every few seconds. */
    public function ping()
    {
        return response()->json([
            'latest' => (string) EmailThread::max('updated_at'),
            'unread' => (int) EmailThread::where('unread', true)->count(),
        ]);
    }

    public function show(EmailThread $thread)
    {
        $thread->load(['mailbox', 'messages.attachments', 'messages.sentBy', 'reads.user']);
        if ($thread->unread) {
            $thread->forceFill(['unread' => false])->save();
        }
        // Read receipt: remember who opened this conversation and when.
        $thread->reads()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['read_at' => now()]
        );
        return view('inbox.show', ['thread' => $thread, 'mailboxes' => $this->sidebarMailboxes()]);
    }

    private function sidebarMailboxes()
    {
        return Mailbox::sidebar();
    }

    public function reply(Request $request, EmailThread $thread)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:50000']]);
        $mb = $thread->mailbox;

        if (!$mb->isConnected()) {
            return back()->withErrors(['body' => 'This mailbox is not connected to Gmail yet.']);
        }
        if (!$thread->customer_email) {
            return back()->withErrors(['body' => 'No recipient address stored for this thread.']);
        }

        $lastIncoming = $thread->messages()->where('direction', 'in')->orderByDesc('sent_at')->first();

        $subject = (string) $thread->subject;
        if (!preg_match('/^re:/i', $subject)) {
            $subject = 'Re: ' . $subject;
        }

        $bodyText = rtrim($data['body']);
        if ($mb->signature) {
            $bodyText .= "\n\n" . $mb->signature;
        }

        $headers = [
            'From: ' . self::encodeHeaderName($mb->brand_name) . " <{$mb->email}>",
            'To: ' . $thread->customer_email,
            'Subject: ' . self::encodeHeaderText($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($lastIncoming?->message_id_header) {
            $headers[] = 'In-Reply-To: ' . $lastIncoming->message_id_header;
            // Full References chain so the customer's mail client threads properly.
            $refs = $thread->messages()->where('direction', 'in')
                ->orderBy('sent_at')->pluck('message_id_header')
                ->filter()->unique()->implode(' ');
            $headers[] = 'References: ' . mb_substr($refs, -2000);
        }

        $raw = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($bodyText));

        try {
            $sent = GmailClient::sendRaw($mb, $raw, $thread->gmail_thread_id);
        } catch (\Throwable $e) {
            Log::error('Reply failed: ' . $e->getMessage());
            return back()->withErrors(['body' => 'Sending failed — check the mailbox connection and try again.']);
        }

        EmailMessage::create([
            'thread_id'        => $thread->id,
            'gmail_id'         => $sent['id'] ?? ('local-' . uniqid()),
            'direction'        => 'out',
            'sender_name'      => $mb->brand_name,
            'sent_by_user_id'  => auth()->id(),
            'body_text'        => $bodyText,
            'sent_at'          => now(),
        ]);

        $thread->forceFill([
            'last_message_at' => now(),
            'snippet'         => mb_substr(preg_replace('/\s+/u', ' ', $bodyText), 0, 280),
            'unread'          => false,
        ])->save();

        return redirect()->route('thread', $thread)->with('status', 'Reply sent from ' . $mb->brand_name . '.');
    }

    public function sync(MailboxSyncer $syncer)
    {
        // The first sync of a busy mailbox imports hundreds of messages;
        // give it room instead of dying at PHP's default 30s limit.
        @set_time_limit(600);

        $new = 0;
        $errors = [];
        foreach (Mailbox::where('active', true)->whereNotNull('refresh_token')->get() as $mb) {
            try {
                $new += $syncer->sync($mb);
            } catch (\Throwable $e) {
                Log::error("Sync failed for {$mb->email}: " . $e->getMessage());
                $errors[] = $mb->brand_name;
            }
        }
        return response()->json([
            'new'    => $new,
            'errors' => $errors,
            // Lets the page notice mail imported by the background sync task too.
            'latest' => (string) EmailThread::max('updated_at'),
        ]);
    }

    private static function encodeHeaderText(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function encodeHeaderName(string $s): string
    {
        $s = str_replace(['"', "\r", "\n"], '', $s);
        return preg_match('/[^\x20-\x7E]/', $s) ? self::encodeHeaderText($s) : '"' . $s . '"';
    }
}
