{{-- App preloader:
     - Splash (logo + wordmark + progress bar) the first time MailDesk opens in a tab/app session.
     - Thin top progress bar while moving between pages afterwards (like Gmail). --}}
<style>
#mdSplash{position:fixed;inset:0;z-index:2147483000;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;
    background:radial-gradient(120% 80% at 50% 0%,#ffffff 0%,#f6f8fc 55%,#eaf1fb 100%);transition:opacity .35s ease,visibility .35s ease}
#mdSplash.md-out{opacity:0;visibility:hidden}
html.md-nosplash #mdSplash{display:none}
#mdSplash .md-logo{width:88px;height:88px;border-radius:24px;box-shadow:0 10px 30px rgba(11,87,208,.18),0 2px 6px rgba(0,0,0,.06);
    animation:mdPop .6s cubic-bezier(.2,.9,.3,1.2) both}
#mdSplash .md-name{font:500 24px/1 Roboto,system-ui,-apple-system,"Segoe UI",sans-serif;color:#1f1f1f;letter-spacing:.2px;animation:mdUp .5s .12s ease both}
#mdSplash .md-tag{font:400 13px/1.3 Roboto,system-ui,sans-serif;color:#5e5e5e;margin-top:-10px;animation:mdUp .5s .2s ease both}
#mdSplash .md-track{width:148px;height:4px;border-radius:4px;background:#d3e3fd;overflow:hidden;margin-top:6px;animation:mdUp .5s .28s ease both}
#mdSplash .md-track i{display:block;width:40%;height:100%;border-radius:4px;background:linear-gradient(90deg,#0b57d0,#4c8df6);animation:mdSlide 1.1s ease-in-out infinite}
#mdSplash .md-foot{position:absolute;bottom:calc(28px + env(safe-area-inset-bottom));font:400 11.5px/1.4 Roboto,system-ui,sans-serif;color:#8a8f98;text-align:center;padding:0 16px}
#mdBar{position:fixed;top:0;left:0;height:3px;width:0;z-index:2147483001;background:linear-gradient(90deg,#0b57d0,#4c8df6);
    box-shadow:0 0 8px rgba(11,87,208,.5);opacity:0;transition:width .25s ease,opacity .3s ease;pointer-events:none}
#mdBar.md-run{opacity:1}
@keyframes mdPop{from{opacity:0;transform:scale(.7)}to{opacity:1;transform:none}}
@keyframes mdUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
@keyframes mdSlide{0%{transform:translateX(-110%)}100%{transform:translateX(260%)}}
@media (prefers-reduced-motion:reduce){#mdSplash *{animation:none!important}#mdSplash .md-track i{width:100%}}
</style>
<script>
(function () {
    // Splash only once per app/tab session; later page loads use the slim top bar.
    try { if (sessionStorage.getItem('md_splash')) document.documentElement.classList.add('md-nosplash'); } catch (e) {}
})();
</script>
<div id="mdSplash" aria-hidden="true">
    <img class="md-logo" src="/app-icons/icon-192.png" width="88" height="88" alt="">
    <div class="md-name">MailDesk</div>
    <div class="md-tag">Shared inbox</div>
    <div class="md-track"><i></i></div>
    <div class="md-foot">1Dollar Digitizing &middot; Aplus Digitizing &middot; Digitizing Zone</div>
</div>
<div id="mdBar" aria-hidden="true"></div>
<script>
(function () {
    const splash = document.getElementById('mdSplash');
    const bar = document.getElementById('mdBar');
    const shownAt = Date.now();

    function hideSplash() {
        if (!splash || splash.classList.contains('md-out')) return;
        try { sessionStorage.setItem('md_splash', '1'); } catch (e) {}
        const wait = Math.max(0, 700 - (Date.now() - shownAt));   // long enough to look intentional, not a flicker
        setTimeout(() => {
            splash.classList.add('md-out');
            setTimeout(() => splash.remove(), 400);
        }, wait);
    }
    if (document.documentElement.classList.contains('md-nosplash')) splash.remove();
    else {
        if (document.readyState === 'complete') hideSplash();
        else window.addEventListener('load', hideSplash);
        setTimeout(hideSplash, 4000);                              // never block a slow page
    }

    // ---- Top progress bar while the next page loads ----
    let timer = null;
    function start() {
        clearInterval(timer);
        let w = 8;
        bar.style.transition = 'none'; bar.style.width = '0';
        void bar.offsetWidth;
        bar.style.transition = '';
        bar.classList.add('md-run'); bar.style.width = w + '%';
        timer = setInterval(() => { w += (90 - w) * 0.08; bar.style.width = w + '%'; if (w > 89) stop(); }, 200);
    }
    function stop() {
        clearInterval(timer);
        if (!bar.classList.contains('md-run')) return;
        bar.style.width = '100%';
        setTimeout(() => { bar.classList.remove('md-run'); bar.style.width = '0'; }, 300);
    }

    document.addEventListener('click', e => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        if (!a || a.target === '_blank' || a.hasAttribute('download') || a.dataset.install !== undefined) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || /\.(apk|pdf|zip)$/i.test(url.pathname) || url.pathname.startsWith('/attachment/')) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
        start();
    });
    document.addEventListener('submit', e => {
        if (e.defaultPrevented) return;
        setTimeout(() => { if (!e.defaultPrevented) start(); }, 0);
    });
    window.addEventListener('pageshow', e => { if (e.persisted) stop(); });
    window.addEventListener('pagehide', () => setTimeout(stop, 0));
    window.mdLoading = { start, stop };
})();
</script>
