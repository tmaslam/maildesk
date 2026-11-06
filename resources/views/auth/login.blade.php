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
        <a class="login-getapp" href="{{ route('get-app') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 1H7a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm0 18H7V5h10v14zm-5 3a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
            Get the mobile app (Android / iPhone)
        </a>
    </form>
</div>
<script>
    // Show / hide password
    document.getElementById('pwEye').addEventListener('click', function () {
        const input = document.getElementById('pwInput');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('.eye-open').hidden = show;
        this.querySelector('.eye-off').hidden = !show;
        this.title = this.ariaLabel = show ? 'Hide password' : 'Show password';
        input.focus();
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 1 }}', { scope: '/' }).catch(() => {});
    }
</script>
</body>
</html>
