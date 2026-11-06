@php
    $apk = public_path('download/MailDesk.apk');
    $hasApk = is_file($apk);
    $apkUrl = $hasApk ? url('/download/MailDesk.apk') . '?v=' . filemtime($apk) : null;
    $apkSize = $hasApk ? number_format(filesize($apk) / 1048576, 1) . ' MB' : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Get the MailDesk app</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-head')
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
</head>
<body class="get-app">
<div class="ga-wrap">
    <a class="ga-back" href="{{ auth()->check() ? route('inbox') : route('login') }}">&larr; {{ auth()->check() ? 'Back to inbox' : 'Sign in' }}</a>

    <div class="ga-hero">
        <img src="/app-icons/icon-192.png" alt="" width="84" height="84">
        <h1>Get the MailDesk app</h1>
        <p>Choose your phone. Same login as the website.</p>
    </div>

    <div class="ga-choose" id="gaChoose">
        <button class="ga-pick" data-pick="android" type="button">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="#34a853"><path d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.86 3.22a11.4 11.4 0 0 0-9.84 0L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.78 10.78 0 0 0 1 18h22a10.78 10.78 0 0 0-5.4-8.52zM7 15.25A1.25 1.25 0 1 1 8.25 14 1.25 1.25 0 0 1 7 15.25zm10 0A1.25 1.25 0 1 1 18.25 14 1.25 1.25 0 0 1 17 15.25z"/></svg>
            <b>Android</b>
            <small>Samsung, Oppo, Vivo, Xiaomi…</small>
        </button>
        <button class="ga-pick" data-pick="iphone" type="button">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="#1f1f1f"><path d="M16.37 12.6c-.02-2.3 1.88-3.4 1.96-3.46a4.22 4.22 0 0 0-3.32-1.8c-1.41-.15-2.76.83-3.48.83-.73 0-1.83-.81-3-.79a4.45 4.45 0 0 0-3.76 2.28c-1.6 2.78-.41 6.9 1.15 9.16.76 1.1 1.67 2.34 2.86 2.3 1.15-.05 1.58-.74 2.97-.74s1.78.74 2.99.72c1.24-.02 2.02-1.12 2.77-2.23a9.6 9.6 0 0 0 1.26-2.58 3.98 3.98 0 0 1-2.4-3.69zM14.1 5.86A3.98 3.98 0 0 0 15.04 3a4.06 4.06 0 0 0-2.63 1.36 3.8 3.8 0 0 0-.96 2.76 3.36 3.36 0 0 0 2.65-1.26z"/></svg>
            <b>iPhone</b>
            <small>iOS 16.4 or newer</small>
        </button>
    </div>

    {{-- Android --}}
    <div class="ga-panel" id="ga-android" hidden>
        <h2>Android</h2>
        @if($hasApk)
            <a class="btn ga-dl" href="{{ $apkUrl }}" download="MailDesk.apk">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                Download MailDesk.apk <small>({{ $apkSize }})</small>
            </a>
            <ol class="ga-steps">
                <li>Tap <b>Download</b> above. If Chrome warns “file might be harmful”, tap <b>Download anyway</b>.</li>
                <li>Open the downloaded <b>MailDesk.apk</b> (from the notification or the Downloads folder).</li>
                <li>If asked, allow <b>“Install unknown apps”</b> for Chrome, then go back.</li>
                <li>Tap <b>Install</b>, then <b>Open</b>. Sign in with your username and password.</li>
                <li>Tap the <b>🔔 bell</b> and <b>Allow</b> notifications to get new-email alerts.</li>
            </ol>
        @else
            <button type="button" class="btn ga-dl" data-install="android">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                Install MailDesk
            </button>
            <p class="ga-note" id="gaManual">If no install window appears, follow these steps:</p>
            <ol class="ga-steps">
                <li>Open <b>{{ url('/') }}</b> in <b>Chrome</b> on your phone.</li>
                <li>Tap the <b>⋮</b> menu → <b>Install app</b> (or “Add to Home screen”).</li>
                <li>Open MailDesk from the home screen and sign in.</li>
                <li>Tap the <b>🔔 bell</b> and <b>Allow</b> notifications.</li>
            </ol>
        @endif
        <p class="ga-alt">Or install straight from Chrome: open the site → <b>⋮</b> → <b>Install app</b>.</p>
    </div>

    {{-- iPhone --}}
    <div class="ga-panel" id="ga-iphone" hidden>
        <h2>iPhone</h2>
        <button type="button" class="btn ga-dl" data-install="iphone">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Install on iPhone
        </button>
        <p class="ga-note">Apple only allows App Store downloads, so on iPhone MailDesk installs from Safari. It works the same as an app: own icon, full screen, notifications.</p>
        <ol class="ga-steps">
            <li>Open <b>{{ url('/') }}</b> in <b>Safari</b> (not Chrome).</li>
            <li>Tap the <b>Share</b> button <span class="ga-ico">⬆</span> at the bottom.</li>
            <li>Scroll down and tap <b>Add to Home Screen</b>, then <b>Add</b>.</li>
            <li>Open MailDesk from the home screen and sign in.</li>
            <li>Tap the <b>🔔 bell</b> and <b>Allow</b> notifications.</li>
        </ol>
    </div>

    <div class="ga-qr">
        <img src="/app-icons/qr-app.png" alt="QR code for this page" width="148" height="148">
        <div>
            <b>On a computer?</b>
            <span>Scan this with your phone camera to open this page on the phone.</span>
            <span class="ga-link">{{ url('/app') }}</span>
        </div>
    </div>
</div>
<script>
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isAndroid = /android/i.test(navigator.userAgent);
    function pick(which) {
        document.querySelectorAll('.ga-pick').forEach(b => b.classList.toggle('on', b.dataset.pick === which));
        document.getElementById('ga-android').hidden = which !== 'android';
        document.getElementById('ga-iphone').hidden = which !== 'iphone';
    }
    document.querySelectorAll('.ga-pick').forEach(b => b.addEventListener('click', () => pick(b.dataset.pick)));
    const want = new URLSearchParams(location.search).get('os');
    if (want === 'android' || want === 'iphone') pick(want);
    else if (isIOS) pick('iphone'); else if (isAndroid) pick('android');
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 1 }}', { scope: '/' }).catch(() => {});
</script>
@include('partials.app-prompt')
</body>
</html>
