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
        <div class="thread-labels">
            <span id="threadLabelChips">
                @foreach($thread->labels as $lb)<span class="lbl" style="--lc: {{ $lb->color }}">{{ $lb->name }}</span>@endforeach
            </span>
            <details class="label-menu" id="threadLabelMenu">
                <summary class="lbl-add" title="Label this conversation">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.63 5.84A2 2 0 0 0 16 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 1.63-.84L22 12l-4.37-6.16z"/></svg>
                    {{ $thread->labels->isEmpty() ? 'Add label' : 'Labels' }}
                </summary>
                <div class="label-pop">
                    <div class="label-pop-title">Label as:</div>
                    <div id="threadLabelList">
                        @foreach($allLabels as $lb)
                            <label class="label-opt">
                                <input type="checkbox" value="{{ $lb->id }}" {{ $thread->labels->contains('id', $lb->id) ? 'checked' : '' }}>
                                <span class="lbl-dot" style="background: {{ $lb->color }}"></span>{{ $lb->name }}
                            </label>
                        @endforeach
                    </div>
                    <div class="label-pop-sep"></div>
                    <form class="label-new" id="threadLabelNew">
                        <input type="text" maxlength="60" placeholder="Create new label…" required>
                        <button type="submit" class="btn">Add</button>
                    </form>
                </div>
            </details>
        </div>
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
            @php [$mainText, $quotedText] = \App\Support\Quote::split($msg->body_text); @endphp
            <div class="msg-body">{{ \App\Support\Mask::body($mainText) }}</div>
            @if($quotedText !== '')
                <details class="quoted">
                    <summary title="Show quoted text">&#8226;&#8226;&#8226;</summary>
                    <div class="msg-body quoted-body">{{ \App\Support\Mask::body($quotedText) }}</div>
                </details>
            @endif
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

@section('scripts')
<script>
(function () {
    const HEAD = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' };
    const list = document.getElementById('threadLabelList');
    const chips = document.getElementById('threadLabelChips');
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function paint(labels) {
        chips.innerHTML = labels.map(l => `<span class="lbl" style="--lc: ${esc(l.color)}">${esc(l.name)}</span>`).join('');
    }

    // Tick/untick = save immediately, like Gmail.
    list.addEventListener('change', async () => {
        const ids = Array.from(list.querySelectorAll('input:checked')).map(i => +i.value);
        const res = await fetch("{{ route('thread.labels', $thread) }}", { method: 'POST', headers: HEAD, body: JSON.stringify({ labels: ids }) });
        if (res.ok) paint((await res.json()).labels);
        else alert('Could not save labels — please try again.');
    });

    document.getElementById('threadLabelNew').addEventListener('submit', async e => {
        e.preventDefault();
        const input = e.target.querySelector('input');
        const name = input.value.trim();
        if (!name) return;
        const res = await fetch("{{ route('labels.store') }}", {
            method: 'POST', headers: HEAD, body: JSON.stringify({ name, thread_id: {{ $thread->id }} })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { alert(data.message || 'Could not create the label.'); return; }
        location.reload(); // shows the new label everywhere (sidebar too)
    });

    // Close the menu when tapping elsewhere.
    document.addEventListener('click', e => {
        const m = document.getElementById('threadLabelMenu');
        if (m.open && !m.contains(e.target)) m.open = false;
    });
})();
</script>
@endsection
