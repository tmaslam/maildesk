/* MailDesk service worker: installable app, offline screen, push notifications.
 * Pages are always fetched live (they contain private mail); only static
 * assets are cached. */
const CACHE = 'maildesk-static-v1';
const STATIC = ['/offline.html', '/icons/icon-192.png', '/icons/badge-96.png', '/favicon.svg'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(STATIC)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil((async () => {
        for (const key of await caches.keys()) if (key !== CACHE) await caches.delete(key);
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    // Pages: network only, with an offline screen when there is no connection.
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    // Same-origin static files (css, icons): serve cached copy, refresh in background.
    if (url.origin === location.origin && /^\/(css|icons)\/|\/favicon\.svg$/.test(url.pathname)) {
        e.respondWith(caches.open(CACHE).then(async (c) => {
            const hit = await c.match(req);
            const live = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => hit);
            return hit || live;
        }));
    }
});

// Push carries no data (no customer details leave the server); ask the
// server how many emails are unread and show that.
self.addEventListener('push', (e) => {
    e.waitUntil((async () => {
        let unread = 0;
        try {
            const r = await fetch('/ping', { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
            if (r.ok) unread = (await r.json()).unread || 0;
        } catch (err) {}
        await self.registration.showNotification('MailDesk — new email', {
            body: unread > 0 ? `${unread} unread email${unread > 1 ? 's' : ''} waiting` : 'A new customer email arrived',
            icon: '/icons/icon-192.png',
            badge: '/icons/badge-96.png',
            tag: 'maildesk-new',
            renotify: true,
            data: { url: '/?unread=1' },
        });
        if (self.navigator.setAppBadge) {
            try { unread > 0 ? await self.navigator.setAppBadge(unread) : await self.navigator.clearAppBadge(); } catch (err) {}
        }
    })());
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const target = (e.notification.data && e.notification.data.url) || '/';
    e.waitUntil((async () => {
        const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const w of wins) {
            if (new URL(w.url).origin === location.origin) {
                await w.focus();
                return w.navigate(target);
            }
        }
        return self.clients.openWindow(target);
    })());
});
