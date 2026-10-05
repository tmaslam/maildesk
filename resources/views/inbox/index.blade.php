@extends('layouts.app')

@section('title', ($folder === 'sent' ? 'Sent' : 'Inbox') . ' — MailDesk')

@section('content')
    @php
        $mbParam = request('mailbox') ? ['mailbox' => request('mailbox')] : [];
        $isUnread = $folder === 'inbox' && request()->boolean('unread');
        $isRead = $folder === 'inbox' && request()->boolean('read');
        $isTotal = $folder === 'inbox' && !$isUnread && !$isRead;
    @endphp
    <div class="toolbar">
        <button class="chip" id="syncBtn" type="button" title="Check for new mail">&#x21bb; Refresh</button>
        <a class="stat {{ $isTotal ? 'on' : '' }}" href="{{ route('inbox', $mbParam) }}"><b>{{ $stats['total'] }}</b> Total</a>
        <a class="stat unread {{ $isUnread ? 'on' : '' }}" href="{{ route('inbox', $mbParam + ['unread' => 1]) }}"><b>{{ $stats['unread'] }}</b> Unread</a>
        <a class="stat read {{ $isRead ? 'on' : '' }}" href="{{ route('inbox', $mbParam + ['read' => 1]) }}"><b>{{ $stats['read'] }}</b> Read</a>
        <a class="stat sent {{ $folder === 'sent' ? 'on' : '' }}" href="{{ route('inbox', $mbParam + ['folder' => 'sent']) }}"><b>{{ $stats['sent'] }}</b> Sent</a>
        <span id="syncNote" style="font-size:12px"></span>
        <span class="top-pager" id="topPager">
            @if($threads->total())
                <span class="range">{{ $threads->firstItem() }}&ndash;{{ $threads->lastItem() }} of {{ $threads->total() }}</span>
            @else
                <span class="range">0 of 0</span>
            @endif
            @if($threads->onFirstPage())
                <span class="chev disabled">&#8249;</span>
            @else
                <a class="chev" href="{{ $threads->previousPageUrl() }}" title="Newer">&#8249;</a>
            @endif
            @if($threads->hasMorePages())
                <a class="chev" href="{{ $threads->nextPageUrl() }}" title="Older">&#8250;</a>
            @else
                <span class="chev disabled">&#8250;</span>
            @endif
        </span>
    </div>

    <div id="threadList" data-latest="{{ $latest }}">
    @if($folder === 'sent')
        <div class="folder-banner">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#7b3ff2"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            <b>Sent messages</b> — replies your team sent, newest first
        </div>
    @endif

    @forelse($threads as $t)
        @if($folder === 'sent')
            <a class="row sent-row" href="{{ route('thread', $t) }}">
                <span class="row-avatar" style="background: #f3e8fd; color: #7b3ff2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </span>
                <span class="name-col">
                    <span class="name"><em class="to">To:</em> {{ \App\Support\Mask::customer($t) }}</span>
                    <span class="brand" style="background: {{ \App\Support\Palette::color($t->mailbox_id) }}">
                        {{ $t->mailbox->brand_name }}
                    </span>
                </span>
                <span class="line">
                    {{ \App\Support\Mask::body($t->subject) }}
                    <span class="snip"> — {{ \Illuminate\Support\Str::limit(\App\Support\Mask::body(preg_replace('/\s+/u', ' ', (string) $t->latestOut?->body_text)), 80) }}</span>
                </span>
                <span class="byline-chip">
                    <span class="mini-avatar">{{ mb_substr($t->latestOut?->sentBy->name ?? 'G', 0, 1) }}</span>
                    {{ $t->latestOut?->sentBy->name ?? 'Gmail' }}
                    <em>{{ $t->latestOut?->sentBy ? ($t->latestOut->sentBy->isAdmin() ? 'Super Admin' : 'Team') : 'direct' }}</em>
                </span>
                <span class="when">{{ $t->latestOut?->sent_at?->format($t->latestOut->sent_at->isToday() ? 'g:i A' : 'M j') }}</span>
            </a>
        @else
            <a class="row {{ $t->unread ? 'unread' : '' }}" href="{{ route('thread', $t) }}">
                <span class="row-avatar" style="background: {{ \App\Support\Palette::color($t->mailbox_id) }}20; color: {{ \App\Support\Palette::color($t->mailbox_id) }}">
                    {{ mb_substr(\App\Support\Mask::customer($t), 0, 1) }}
                </span>
                <span class="name-col">
                    <span class="name">{{ \App\Support\Mask::customer($t) }}</span>
                    <span class="brand" style="background: {{ \App\Support\Palette::color($t->mailbox_id) }}">
                        {{ $t->mailbox->brand_name }}
                    </span>
                </span>
                @if($t->spam)<span class="pill spam">Spam</span>@endif
                <span class="line">
                    {{ \App\Support\Mask::body($t->subject) }}
                    <span class="snip"> — {{ \Illuminate\Support\Str::limit(\App\Support\Mask::body($t->snippet), 90) }}</span>
                </span>
                <span class="when">{{ $t->last_message_at?->format($t->last_message_at->isToday() ? 'g:i A' : 'M j') }}</span>
            </a>
        @endif
    @empty
        <div class="empty">
            <div style="font-size:48px">{{ $folder === 'sent' ? '📤' : '📬' }}</div>
            <h3>{{ $folder === 'sent' ? 'Nothing sent yet' : 'No conversations yet' }}</h3>
            <p>
                @if($folder === 'sent')
                    Replies you send from MailDesk will show up here.
                @elseif(auth()->user()->isAdmin())
                    Connect a brand mailbox under <a href="{{ route('mailboxes') }}" style="color:var(--blue)">Mailboxes</a>, then hit Refresh.
                @else
                    New customer messages will appear here.
                @endif
            </p>
        </div>
    @endforelse
    </div>

@endsection

@section('scripts')
<script>
    const syncBtn = document.getElementById('syncBtn');
    const syncNote = document.getElementById('syncNote');

    async function syncNow(auto) {
        if (syncBtn.disabled) return;
        syncBtn.disabled = true;
        syncNote.textContent = 'Checking for new mail…';
        try {
            const res = await fetch('{{ route('sync') }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            });
            const data = await res.json();
            const list = document.getElementById('threadList');
            const changed = data.new > 0 || (data.latest && list && list.dataset.latest && data.latest !== list.dataset.latest);
            if (changed) { location.reload(); return; }
            syncNote.textContent = auto ? '' : 'You’re up to date';
        } catch (e) {
            syncNote.textContent = '';
        }
        syncBtn.disabled = false;
        setTimeout(() => { if (syncNote.textContent === 'You’re up to date') syncNote.textContent = ''; }, 4000);
    }

    syncBtn.addEventListener('click', () => syncNow(false));
    setInterval(() => syncNow(true), 60000); // auto-check every minute
</script>
@endsection
