{{-- Mobile browsers only: ask once whether to get the app or keep using the browser. --}}
@php
    $apkFile = public_path('download/MailDesk.apk');
    $promptAndroidUrl = is_file($apkFile) ? url('/download/MailDesk.apk') . '?v=' . filemtime($apkFile) : route('get-app', ['os' => 'android']);
@endphp
<div class="app-prompt" id="appPrompt" hidden>
    <div class="app-prompt-sheet" role="dialog" aria-labelledby="appPromptTitle">
        <img src="/app-icons/icon-192.png" width="56" height="56" alt="">
        <h2 id="appPromptTitle">Open MailDesk in the app?</h2>
        <p>The app opens full screen, has its own home-screen icon and alerts you when new email arrives.</p>
        <a class="btn app-prompt-go" id="appPromptGo" href="{{ route('get-app') }}">Use the app</a>
        <button type="button" class="app-prompt-skip" id="appPromptSkip">Continue in browser</button>
    </div>
</div>
<script>
(function () {
    const ua = navigator.userAgent;
    const isIOS = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    const isAndroid = /android/i.test(ua);
    const inApp = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone
        || document.referrer.startsWith('android-app://') || /[?&]source=app/.test(location.search);
    if (inApp) { try { localStorage.setItem('md_in_app', '1'); } catch (e) {} return; }
    if (!(isIOS || isAndroid)) return;
    try {
        if (localStorage.getItem('md_in_app')) return;            // already uses the installed app
        const until = +localStorage.getItem('md_browser_ok') || 0; // chose "Continue in browser"
        if (until > Date.now()) return;
    } catch (e) {}

    const box = document.getElementById('appPrompt');
    document.getElementById('appPromptGo').href = isAndroid ? @json($promptAndroidUrl) : @json(route('get-app', ['os' => 'iphone']));
    if (isAndroid && /\.apk/.test(document.getElementById('appPromptGo').href)) document.getElementById('appPromptGo').setAttribute('download', 'MailDesk.apk');
    box.hidden = false;
    const close = () => { box.hidden = true; };
    document.getElementById('appPromptSkip').addEventListener('click', () => {
        try { localStorage.setItem('md_browser_ok', Date.now() + 7 * 864e5); } catch (e) {} // ask again in a week
        close();
    });
    document.getElementById('appPromptGo').addEventListener('click', close);
    box.addEventListener('click', e => { if (e.target === box) close(); });
})();
</script>
