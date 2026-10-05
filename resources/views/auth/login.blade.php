<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — MailDesk</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
        <input class="f" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
        <button class="btn">Sign in</button>
    </form>
</div>
</body>
</html>
