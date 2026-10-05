@extends('layouts.app')

@section('title', (\App\Support\Mask::body($thread->subject) ?: 'Conversation') . ' — MailDesk')

@section('content')
    <div class="toolbar">
        <a class="chip" href="{{ url()->previous() === url()->current() ? route('inbox') : url()->previous() }}">&#8592; Back</a>
        <span class="brand" style="background: {{ \App\Support\Palette::color($thread->mailbox_id) }}; color:#fff; border-radius:999px; padding:3px 12px; font-size:11px; font-weight:600">
            {{ $thread->mailbox->brand_name }}
        </span>
        @if($thread->spam)<span class="pill spam">Spam in Gmail</span>@endif
        @if(\App\Support\Mask::isAdmin() && $thread->customer_email)
            <span style="font-size:12px">Customer: {{ $thread->customer_email }} <em>(visible to admins only)</em></span>
        @endif
        @if($thread->reads->isNotEmpty())
            <span class="seen" title="{{ $thread->reads->map(fn ($r) => $r->user->name . ' · ' . $r->read_at->format('M j, g:i A'))->implode("\n") }}">
                &#128065; Seen by:
                @foreach($thread->reads as $r)
                    <b>{{ $r->user->name }}</b><em>({{ $r->user->isAdmin() ? 'Super Admin' : 'Team' }})</em>@if(!$loop->last), @endif
                @endforeach
            </span>
        @endif
    </div>

    <div class="thread-head">
        <h1>{{ \App\Support\Mask::body($thread->subject) ?: '(no subject)' }}</h1>
    </div>

    @foreach($thread->messages->sortBy('sent_at') as $msg)
        <div class="msg {{ $msg->direction }}">
            <div class="msg-head">
                <span class="avatar" style="background: {{ $msg->direction === 'out' ? '#5f6368' : \App\Support\Palette::color($thread->mailbox_id) }}">
                    {{ mb_substr($msg->direction === 'out' ? $thread->mailbox->brand_name : \App\Support\Mask::customer($thread), 0, 1) }}
                </span>
                <div class="meta">
                    <b>
                        @if($msg->direction === 'out')
                            {{ $thread->mailbox->brand_name }}
                        @else
                            {{ \App\Support\Mask::customer($thread) }}
                        @endif
                    </b>
                    <small>
                        @if($msg->direction === 'in')
                            to {{ $thread->mailbox->brand_name }}
                        @elseif($msg->sentBy)
                            sent by {{ $msg->sentBy->name }} ({{ $msg->sentBy->isAdmin() ? 'Super Admin' : 'Team' }})
                        @else
                            sent from Gmail
                        @endif
                    </small>
                </div>
                <span class="when">{{ $msg->sent_at?->format('D, M j, Y g:i A') }}</span>
            </div>
            <div class="msg-body">{{ \App\Support\Mask::body($msg->body_text) }}</div>
            @foreach($msg->attachments as $att)
                <a class="att" href="{{ route('attachment', $att) }}">
                    &#128206; {{ \App\Support\Mask::body($att->filename) }}
                    <small>({{ number_format($att->size / 1024, 0) }} KB)</small>
                </a>
            @endforeach
        </div>
    @endforeach

    <form class="reply" method="post" action="{{ route('thread.reply', $thread) }}">
        @csrf
        <textarea name="body" placeholder="Reply as {{ $thread->mailbox->brand_name }}…" required>{{ old('body') }}</textarea>
        <div class="reply-foot">
            <button class="btn-send">Send</button>
            <span class="hint">
                Sends from <b>{{ $thread->mailbox->brand_name }}</b>'s own email — the customer's address stays hidden from agents.
            </span>
        </div>
    </form>
@endsection
