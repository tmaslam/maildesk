<script>
/* MailDesk app shell: service worker, phone push, mobile drawer, install button. */
(function () {
    const CSRF = '{{ csrf_token() }}';
    const VAPID = @json(\App\Services\WebPush::publicKey());
    const SW_URL = '/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 1 }}';

    // ---- Mobile drawer ----
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('drawerBackdrop');
    function drawer(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        backdrop.hidden = !open;
        document.body.classList.toggle('no-scroll', open);
    }
    if (menuBtn) menuBtn.addEventListener('click', () => drawer(!sidebar.classList.contains('open')));
    if (backdrop) backdrop.addEventListener('click', () => drawer(false));
    window.addEventListener('keydown', e => { if (e.key === 'Escape') drawer(false); });

    // ---- Service worker + push ----
    function b64ToBytes(b64) {
        const pad = '='.repeat((4 - b64.length % 4) % 4);
        const raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, c => c.charCodeAt(0));
    }
    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
    }

    window.mdPush = {
        // Make this device's push subscription match the bell (on/off).
        async sync(on) {
            if (!VAPID || !('serviceWorker' in navigator) || !('PushManager' in window)) return;
            try {
                const reg = await navigator.serviceWorker.ready;
                let sub = await reg.pushManager.getSubscription();
                if (on && Notification.permission === 'granted') {
                    if (!sub) {
                        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(VAPID) });
                    }
                    await post('{{ route('push.subscribe') }}', { endpoint: sub.endpoint });
                } else if (!on && sub) {
                    await post('{{ route('push.unsubscribe') }}', { endpoint: sub.endpoint });
                    await sub.unsubscribe();
                }
            } catch (e) { /* push not available on this browser */ }
        }
    };

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(SW_URL, { scope: '/' }).then(() => {
            let on = true;
            try { on = localStorage.getItem('md_alerts') !== 'off'; } catch (e) {}
            if ('Notification' in window && Notification.permission === 'granted') window.mdPush.sync(on);
        }).catch(() => {});
    }

    // ---- Install button ----
    const installItem = document.getElementById('installItem');
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    let deferred = null;
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferred = e;
        if (installItem) installItem.hidden = false;
    });
    if (installItem && isIOS && !standalone) installItem.hidden = false;
    if (installItem) installItem.addEventListener('click', async e => {
        e.preventDefault();
        if (deferred) {
            deferred.prompt();
            await deferred.userChoice;
            deferred = null;
            installItem.hidden = true;
        } else if (isIOS) {
            alert('iPhone par install karne ke liye:\n1. Safari mein neeche Share button dabayein\n2. "Add to Home Screen" chunein\n3. "Add" dabayein');
        }
    });
    window.addEventListener('appinstalled', () => { if (installItem) installItem.hidden = true; });

    // ---- Back in the app after a long time: refresh so forms keep working ----
    let hiddenAt = 0;
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { hiddenAt = Date.now(); return; }
        const typing = Array.from(document.querySelectorAll('textarea')).some(t => t.value.trim() !== '');
        if (hiddenAt && Date.now() - hiddenAt > 30 * 60 * 1000 && !typing) location.reload();
    });
})();
</script>
