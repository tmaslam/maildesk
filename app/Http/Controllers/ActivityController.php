<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Models\Mailbox;
use App\Models\ThreadRead;

class ActivityController extends Controller
{
    public function index()
    {
        $reads = ThreadRead::with(['user', 'thread.mailbox'])
            ->orderByDesc('read_at')->limit(60)->get()
            ->map(fn ($r) => [
                'type'   => 'read',
                'user'   => $r->user,
                'thread' => $r->thread,
                'time'   => $r->read_at,
            ]);

        $replies = EmailMessage::whereNotNull('sent_by_user_id')
            ->with(['sentBy', 'thread.mailbox'])
            ->orderByDesc('sent_at')->limit(60)->get()
            ->map(fn ($m) => [
                'type'   => 'reply',
                'user'   => $m->sentBy,
                'thread' => $m->thread,
                'time'   => $m->sent_at,
            ]);

        $events = $reads->concat($replies)
            ->filter(fn ($e) => $e['user'] && $e['thread'])
            ->sortByDesc('time')->take(60)->values();

        return view('admin.activity', [
            'events'    => $events,
            'mailboxes' => Mailbox::sidebar(),
        ]);
    }
}
