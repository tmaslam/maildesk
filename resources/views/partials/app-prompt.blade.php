{{-- Mobile app install helpers:
     - window.mdInstall('android'|'iphone') — one-tap install where the phone allows it
     - "use the app or continue in browser?" sheet for mobile browsers (asked once a week). --}}
@php
    $apkFile = public_path('download/MailDesk.apk');
    $promptApkUrl = is_file($apkFile) ? url('/download/MailDesk.apk') . '?v=' . filemtime($apkFile) : null;
@endphp
<div class="app-prompt" id="appPrompt" hidden>
    <div class="app-prompt-sheet" role="dialog" aria-labelledby="appPromptTitle">
        <img src="/app-icons/icon-192.png" width="56" height="56" alt="">
        <h2 id="appPromptTitle">Open MailDesk in the app?</h2>
        <p>The app opens full screen, has its own home-screen icon and alerts you when new email arrives.</p>
        <button type="button" class="btn app-prompt-go" id="appPromptGo">Install the app</button>
        <button type="button" class="app-prompt-skip" id="appPromptSkip">Continue in browser</button>
    </div>
</div>

{{-- iPhone: Apple only installs web apps from Safari's Share menu, so show the 3 taps right here. --}}
<div class="app-prompt" id="iosSteps" hidden>
    <div class="app-prompt-sheet ios-steps" role="dialog" aria-labelledby="iosStepsTitle">
        <img src="/app-icons/icon-192.png" width="56" height="56" alt="">
        <h2 id="iosStepsTitle">Install MailDesk on iPhone</h2>
        <ol>
            <li><span class="ios-n">1</span> Tap the <b>Share</b> button
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0b57d0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 8l5-5 5 5"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>
                <small>(bottom of Safari)</small></li>
            <li><span class="ios-n">2</span> Choose <b>Add to Home Screen</b>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1f1f1f" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg></li>
            <li><span class="ios-n">3</span> Tap <b>Add</b>. MailDesk appears on your home screen.</li>
        </ol>
        <p class="ios-safari" id="iosNotSafari" hidden>Open this page in <b>Safari</b> first. Other iPhone browsers may not show "Add to Home Screen".</p>
        <div class="ios-arrow" aria-hidden="true">&#8595;</div>
        <button type="button" class="btn app-prompt-go" id="iosStepsOk">Got it</button>
    </div>
</div>
{{-- Android: shown while MailDesk.apk downloads. --}}
<div class="app-prompt" id="apkSteps" hidden>
    <div class="app-prompt-sheet ios-steps" role="dialog" aria-labelledby="apkStepsTitle">
        <img src="/app-icons/icon-192.png" width="56" height="56" alt="">
        <h2 id="apkStepsTitle">MailDesk is downloading…</h2>
        <ol>
            <li><span class="ios-n">1</span> When the download finishes, tap <b>Open</b> (or open <b>MailDesk.apk</b> from Downloads).</li>
            <li><span class="ios-n">2</span> If Android asks, allow <b>Install unknown apps</b> for Chrome, then go back.</li>
            <li><span class="ios-n">3</span> Tap <b>Install</b>, then <b>Open</b>, and sign in.</li>
        </ol>
        <button type="button" class="btn app-prompt-go" id="apkStepsOk">Got it</button>
    </div>
</div>
<script>
(function () {
    const ua = navigator.userAgent;
    const isIOS = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    const isAndroid = /android/i.test(ua);
    const APK = @json($promptApkUrl);
    const PAGE = @json(route('get-app'));
    const inApp = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone
        || document.referrer.startsWith('android-app://') || /[?&]source=app/.test(location.search);

    // Chrome on Android offers a one-tap "Install" dialog — keep it for our buttons.
    let installEvt = null;
    window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); installEvt = e; });
    window.addEventListener('appinstalled', () => { try { localStorage.setItem('md_in_app', '1'); } catch (e) {} });

    const apkBox = document.getElementById('apkSteps');
    document.getElementById('apkStepsOk').addEventListener('click', () => { apkBox.hidden = true; });
    apkBox.addEventListener('click', e => { if (e.target === apkBox) apkBox.hidden = true; });
    const iosBox = document.getElementById('iosSteps');
    document.getElementById('iosStepsOk').addEventListener('click', () => { iosBox.hidden = true; });
    iosBox.addEventListener('click', e => { if (e.target === iosBox) iosBox.hidden = true; });

    window.mdInstall = async function (os) {
        if (os === 'android') {
            if (!isAndroid) { location.href = PAGE + '?os=android'; return; }   // desktop: QR + steps
            if (APK) {                                                          // real Android app
                const a = document.createElement('a'); a.href = APK; a.download = 'MailDesk.apk';
                document.body.appendChild(a); a.click(); a.remove();
                document.getElementById('apkSteps').hidden = false;
                return;
            }
            if (installEvt) {                                                   // one tap: Chrome install
                const evt = installEvt; installEvt = null;
                try { evt.prompt(); await evt.userChoice; return; } catch (e) {}
            }
            if (location.pathname === '/app') { document.getElementById('gaManual')?.scrollIntoView({ behavior: 'smooth' }); return; }
            location.href = PAGE + '?os=android';
            return;
        }
        if (!isIOS) { if (location.pathname !== '/app') location.href = PAGE + '?os=iphone'; return; }
        document.getElementById('iosNotSafari').hidden = /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
        iosBox.hidden = false;
    };

    document.querySelectorAll('[data-install]').forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        window.mdInstall(el.dataset.install);
    }));

    // ---- "Use the app or continue in browser?" (mobile browsers only) ----
    if (inApp) { try { localStorage.setItem('md_in_app', '1'); } catch (e) {} return; }
    if (!(isIOS || isAndroid) || location.pathname === '/app') return;
    try {
        if (localStorage.getItem('md_in_app')) return;
        if ((+localStorage.getItem('md_browser_ok') || 0) > Date.now()) return;
    } catch (e) {}

    const box = document.getElementById('appPrompt');
    box.hidden = false;
    document.getElementById('appPromptSkip').addEventListener('click', () => {
        try { localStorage.setItem('md_browser_ok', Date.now() + 7 * 864e5); } catch (e) {} // ask again in a week
        box.hidden = true;
    });
    document.getElementById('appPromptGo').addEventListener('click', () => {
        box.hidden = true;
        window.mdInstall(isAndroid ? 'android' : 'iphone');
    });
    box.addEventListener('click', e => { if (e.target === box) box.hidden = true; });
})();
</script>
