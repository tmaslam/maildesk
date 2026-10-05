<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MailDesk')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="topbar">
    <a class="logo" href="{{ route('inbox') }}">
        <svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="lgTop" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#4285f4"/>
                    <stop offset="1" stop-color="#7b3ff2"/>
                </linearGradient>
            </defs>
            <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#lgTop)"/>
            <path d="M15 22h34a3 3 0 0 1 3 3v16a3 3 0 0 1-3 3H15a3 3 0 0 1-3-3V25a3 3 0 0 1 3-3z" fill="#fff"/>
            <path d="M13 24l19 13 19-13" fill="none" stroke="url(#lgTop)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span style="font-weight:700; color:var(--text)">Mail<span style="color:var(--blue)">Desk</span></span>
    </a>
    @php
        $advActive = request()->hasAny(['from', 'subject', 'not', 'date_from', 'date_to', 'has_att', 'scope']);
    @endphp
    <form class="search" id="searchForm" method="get" action="{{ route('inbox') }}">
        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search mail">
        <button class="adv-btn {{ $advActive ? 'active' : '' }}" type="button" id="advBtn" title="Advanced search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></svg>
        </button>

        <div class="adv-panel" id="advPanel" {{ $advActive ? '' : 'hidden' }}>
            <div class="adv-row">
                <label>From (customer)</label>
                <input type="text" name="from" value="{{ request('from') }}" placeholder="Customer name{{ auth()->user()?->isAdmin() ? ' or email' : '' }}">
            </div>
            <div class="adv-row">
                <label>Brand</label>
                <select name="mailbox">
                    <option value="">All brands</option>
                    @isset($mailboxes)
                        @foreach($mailboxes as $amb)
                            <option value="{{ $amb->id }}" {{ (int) request('mailbox') === $amb->id ? 'selected' : '' }}>{{ $amb->brand_name }}</option>
                        @endforeach
                    @endisset
                </select>
            </div>
            <div class="adv-row">
                <label>Subject</label>
                <input type="text" name="subject" value="{{ request('subject') }}">
            </div>
            <div class="adv-row">
                <label>Has the words</label>
                <input type="text" name="words" value="{{ request('words') }}" placeholder="Searches inside messages too">
            </div>
            <div class="adv-row">
                <label>Doesn't have</label>
                <input type="text" name="not" value="{{ request('not') }}">
            </div>
            <div class="adv-row">
                <label>Date within</label>
                <div class="adv-dates">
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                    <span>to</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>
            </div>
            <div class="adv-row">
                <label>Search</label>
                <select name="scope">
                    <option value="" {{ request('scope') === null || request('scope') === '' ? 'selected' : '' }}>All mail</option>
                    <option value="unread" {{ request('scope') === 'unread' ? 'selected' : '' }}>Unread</option>
                    <option value="read" {{ request('scope') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="spam" {{ request('scope') === 'spam' ? 'selected' : '' }}>Spam</option>
                    <option value="sent" {{ request('scope') === 'sent' ? 'selected' : '' }}>Sent</option>
                </select>
            </div>
            <div class="adv-row">
                <label></label>
                <label class="adv-check"><input type="checkbox" name="has_att" value="1" {{ request()->boolean('has_att') ? 'checked' : '' }}> Has attachment</label>
            </div>
            <div class="adv-actions">
                <a class="adv-clear" href="{{ route('inbox') }}">Clear</a>
                <button class="btn" type="submit">Search</button>
            </div>
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('advBtn');
            const panel = document.getElementById('advPanel');
            btn.addEventListener('click', () => { panel.hidden = !panel.hidden; });
            document.addEventListener('click', (e) => {
                if (!panel.hidden && !document.getElementById('searchForm').contains(e.target)) panel.hidden = true;
            });

            // Live search: typing filters the inbox list below without a reload.
            const listEl = document.getElementById('threadList');
            const qInput = document.querySelector('#searchForm input[name="q"]');
            if (listEl && qInput) {
                let timer = null;
                let seq = 0;
                qInput.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(async () => {
                        const mySeq = ++seq;
                        const url = new URL(window.location.href);
                        url.searchParams.set('q', qInput.value);
                        url.searchParams.delete('page');
                        if (!qInput.value) url.searchParams.delete('q');
                        try {
                            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
                            const html = await res.text();
                            if (mySeq !== seq) return; // a newer keystroke already fetched
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newList = doc.getElementById('threadList');
                            const newPager = doc.getElementById('topPager');
                            if (newList) listEl.innerHTML = newList.innerHTML;
                            const pager = document.getElementById('topPager');
                            if (pager && newPager) pager.innerHTML = newPager.innerHTML;
                            history.replaceState(null, '', url);
                        } catch (e) { /* network hiccup — keep current list */ }
                    }, 250);
                });
            }
        });
    </script>
    <div class="who">
        <span>{{ auth()->user()->name ?? '' }}</span>
        <span class="avatar">{{ mb_substr(auth()->user()->name ?? '?', 0, 1) }}</span>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn-logout">Sign out</button></form>
    </div>
</div>

<div class="shell">
    <nav class="sidebar">
        @php $totalUnread = isset($mailboxes) ? $mailboxes->sum('unread_count') : 0; @endphp
        <a class="side-item {{ request()->routeIs('inbox') && !request('mailbox') && !request('unread') && !request('folder') ? 'active' : '' }}"
           href="{{ route('inbox') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
            All inboxes
            @if($totalUnread)<span class="count">{{ $totalUnread }}</span>@endif
        </a>
        <a class="side-item {{ request()->boolean('unread') ? 'active' : '' }}" href="{{ route('inbox', ['unread' => 1]) }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><circle cx="12" cy="12" r="6"/></svg>
            Unread
            @if($totalUnread)<span class="count">{{ $totalUnread }}</span>@endif
        </a>
        <a class="side-item {{ request('folder') === 'sent' ? 'active' : '' }}" href="{{ route('inbox', ['folder' => 'sent']) }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            Sent
        </a>

        @isset($mailboxes)
            <div class="side-sep"></div>
            <div class="side-label">Brands</div>
            @foreach($mailboxes as $mb)
                <a class="side-item {{ (int) request('mailbox') === $mb->id ? 'active' : '' }}"
                   href="{{ route('inbox', ['mailbox' => $mb->id]) }}">
                    <span class="dot" style="background: {{ \App\Support\Palette::color($mb->id) }}"></span>
                    {{ $mb->brand_name }}
                    @if($mb->unread_count ?? 0)<span class="count">{{ $mb->unread_count }}</span>@endif
                </a>
            @endforeach
        @endisset

        @if(auth()->user()?->isAdmin())
            <div class="side-sep"></div>
            <div class="side-label">Admin</div>
            <a class="side-item {{ request()->routeIs('mailboxes') ? 'active' : '' }}" href="{{ route('mailboxes') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M19.4 13a7.9 7.9 0 0 0 0-2l2.1-1.6a.5.5 0 0 0 .1-.7l-2-3.4a.5.5 0 0 0-.6-.2l-2.5 1a7.7 7.7 0 0 0-1.7-1L14.5 2.4a.5.5 0 0 0-.5-.4h-4a.5.5 0 0 0-.5.4l-.3 2.7a7.7 7.7 0 0 0-1.7 1l-2.5-1a.5.5 0 0 0-.6.2l-2 3.4a.5.5 0 0 0 .1.7L4.6 11a7.9 7.9 0 0 0 0 2l-2.1 1.6a.5.5 0 0 0-.1.7l2 3.4c.1.2.4.3.6.2l2.5-1a7.7 7.7 0 0 0 1.7 1l.3 2.7c0 .2.2.4.5.4h4c.3 0 .5-.2.5-.4l.3-2.7a7.7 7.7 0 0 0 1.7-1l2.5 1c.2.1.5 0 .6-.2l2-3.4a.5.5 0 0 0-.1-.7L19.4 13zM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5z"/></svg>
                Mailboxes
            </a>
            <a class="side-item {{ request()->routeIs('users') ? 'active' : '' }}" href="{{ route('users') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.7 0-8 1.3-8 4v3h10v-3c0-1 .5-1.9 1.4-2.6A13.6 13.6 0 0 0 8 13zm8 0c-.6 0-1.3 0-2 .1A5.3 5.3 0 0 1 16 17v3h8v-3c0-2.7-5.3-4-8-4z"/></svg>
                Team
            </a>
            <a class="side-item {{ request()->routeIs('activity') ? 'active' : '' }}" href="{{ route('activity') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#5f6368"><path d="M13 3a9 9 0 0 0-9 9H1l3.9 3.9L8.8 12H6a7 7 0 1 1 2 4.9l-1.4 1.4A9 9 0 1 0 13 3zm-1 5v5l4.3 2.5.7-1.2-3.5-2.1V8H12z"/></svg>
                Activity
            </a>
        @endif

        <div class="side-bottom">
            <div class="user-card">
                <a class="user-info" href="{{ route('password') }}" title="Change password">
                    <span class="avatar">{{ mb_substr(auth()->user()->name ?? '?', 0, 1) }}</span>
                    <span class="user-meta">
                        <b>{{ auth()->user()->name ?? '' }}</b>
                        <small>&nbsp;·&nbsp;{{ auth()->user()?->isAdmin() ? 'Super Admin' : 'Team' }}</small>
                    </span>
                </a>
                <form method="post" action="{{ route('logout') }}" title="Logout">
                    @csrf
                    <button class="logout-ico" type="submit" aria-label="Logout">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8v-2H4V5z"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="main">
        @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
@yield('scripts')
</body>
</html>
