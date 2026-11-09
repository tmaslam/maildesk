<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Sign in — MailDesk</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-head')
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
</head>
<body>
<div class="login-wrap">
    <form class="login-card" method="post" action="{{ route('login') }}">
        @csrf
        <div class="login-logo">
            <svg width="72" height="72" viewBox="0 0 64 64" aria-hidden="true">
                <defs>
                    <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#4285f4"/>
                        <stop offset="1" stop-color="#7b3ff2"/>
                    </linearGradient>
                </defs>
                <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#lg)"/>
                <path d="M15 22h34a3 3 0 0 1 3 3v16a3 3 0 0 1-3 3H15a3 3 0 0 1-3-3V25a3 3 0 0 1 3-3z" fill="#fff"/>
                <path d="M13 24l19 13 19-13" fill="none" stroke="url(#lg)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="48" cy="20" r="7" fill="#ea4335" stroke="#fff" stroke-width="2.5"/>
            </svg>
        </div>
        <div class="login-brand">Mail<span>Desk</span></div>
        <p class="login-sub">One inbox for all your brands</p>

        @if($errors->any())<div class="flash err" style="margin:0 0 16px">{{ $errors->first() }}</div>@endif

        <div class="seg" role="radiogroup" aria-label="Sign in as">
            <input type="radio" name="portal" value="admin" id="pAdmin" {{ old('portal', 'admin') === 'admin' ? 'checked' : '' }}>
            <label for="pAdmin">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1 3 5v6c0 5.6 3.8 10.7 9 12 5.2-1.3 9-6.4 9-12V5l-9-4zm0 10.9h7c-.5 4.1-3.3 7.9-7 9V12H5V6.3l7-3.1v8.7z"/></svg>
                Super Admin
            </label>
            <input type="radio" name="portal" value="agent" id="pTeam" {{ old('portal') === 'agent' ? 'checked' : '' }}>
            <label for="pTeam">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zM8 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.7 0-8 1.3-8 4v3h10v-3c0-1 .5-1.9 1.4-2.6A13.6 13.6 0 0 0 8 13zm8 0c-.6 0-1.3 0-2 .1A5.3 5.3 0 0 1 16 17v3h8v-3c0-2.7-5.3-4-8-4z"/></svg>
                Team
            </label>
        </div>

        <input class="f" type="text" name="username" placeholder="Username" value="{{ old('username') }}"
               autocomplete="username" required autofocus>
        <div class="pw-wrap">
            <input class="f" type="password" name="password" id="pwInput" placeholder="Password" autocomplete="current-password" required>
            <button type="button" class="pw-eye" id="pwEye" title="Show password" aria-label="Show password">
                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
                <svg class="eye-off" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" hidden><path d="M12 7a5 5 0 0 1 5 5c0 .65-.13 1.26-.36 1.83l2.92 2.92A11.82 11.82 0 0 0 23 12c-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 0 0 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55A2.8 2.8 0 0 0 9 12a3 3 0 0 0 3 3c.22 0 .44-.03.65-.08l1.55 1.55A4.97 4.97 0 0 1 7 12c0-.79.2-1.53.53-2.2zm4.31-.78 3.15 3.15.02-.16a3 3 0 0 0-3-3l-.17.01z"/></svg>
            </button>
        </div>
        <button class="btn">Sign in</button>
        <div class="login-apps">
            <span>Get the mobile app</span>
            <div class="login-app-btns">
                <a class="login-app" href="{{ route('get-app', ['os' => 'android']) }}" data-install="android">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#3ddc84"><path d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.86 3.22a11.4 11.4 0 0 0-9.84 0L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.78 10.78 0 0 0 1 18h22a10.78 10.78 0 0 0-5.4-8.52zM7 15.25A1.25 1.25 0 1 1 8.25 14 1.25 1.25 0 0 1 7 15.25zm10 0A1.25 1.25 0 1 1 18.25 14 1.25 1.25 0 0 1 17 15.25z"/></svg>
                    <span><small>DOWNLOAD FOR</small><b>Android</b></span>
                </a>
                <a class="login-app" href="{{ route('get-app', ['os' => 'iphone']) }}" data-install="iphone">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="#fff"><path d="M16.37 12.6c-.02-2.3 1.88-3.4 1.96-3.46a4.22 4.22 0 0 0-3.32-1.8c-1.41-.15-2.76.83-3.48.83-.73 0-1.83-.81-3-.79a4.45 4.45 0 0 0-3.76 2.28c-1.6 2.78-.41 6.9 1.15 9.16.76 1.1 1.67 2.34 2.86 2.3 1.15-.05 1.58-.74 2.97-.74s1.78.74 2.99.72c1.24-.02 2.02-1.12 2.77-2.23a9.6 9.6 0 0 0 1.26-2.58 3.98 3.98 0 0 1-2.4-3.69zM14.1 5.86A3.98 3.98 0 0 0 15.04 3a4.06 4.06 0 0 0-2.63 1.36 3.8 3.8 0 0 0-.96 2.76 3.36 3.36 0 0 0 2.65-1.26z"/></svg>
                    <span><small>INSTALL ON</small><b>iPhone</b></span>
                </a>
            </div>
        </div>
    </form>
</div>
<script>
    // Avoid "419 Page Expired": no double submit, and never post a stale token.
    (function () {
        const form = document.querySelector('form.login-card');
        const btn = form.querySelector('button.btn');
        const loadedAt = Date.now();
        const STALE = 100 * 60 * 1000; // session lifetime is 120 min
        form.addEventListener('submit', e => {
            if (Date.now() - loadedAt > STALE) { e.preventDefault(); location.reload(); return; }
            if (form.dataset.sent) { e.preventDefault(); return; }
            form.dataset.sent = '1';
            btn.disabled = true; btn.textContent = 'Signing in…';
        });
        // Back button / app resumed from memory: get a fresh page (fresh token).
        window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && Date.now() - loadedAt > STALE) location.reload();
        });
    })();

    // Show / hide password
    document.getElementById('pwEye').addEventListener('click', function () {
        const input = document.getElementById('pwInput');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('.eye-open').toggleAttribute('hidden', show);
        this.querySelector('.eye-off').toggleAttribute('hidden', !show);
        this.title = this.ariaLabel = show ? 'Hide password' : 'Show password';
        input.focus();
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 1 }}', { scope: '/' }).catch(() => {});
    }
</script>
@include('partials.app-prompt')
</body>
</html>
