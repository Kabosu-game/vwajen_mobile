/* Vwajèn — service worker : cache local (faible connexion), page hors ligne, notifications push */
const VERSION = 'vwajen-v1';
const STATIC = ['/css/app.css', '/js/app.js', '/images/mark.png', '/images/icon-192.png', '/images/icon-64.png', '/favicon.png', '/offline'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(STATIC)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  // Ressources statiques et médias : cache d'abord (stale-while-revalidate)
  if (/\.(css|js|png|jpe?g|webp|gif|svg|woff2?)$/.test(url.pathname) || url.pathname.startsWith('/storage/')) {
    if (url.pathname.match(/\.(mp4|webm|mov)$/)) return; // vidéos : pas de cache (plages d'octets)
    e.respondWith(caches.open(VERSION).then(async (cache) => {
      const cached = await cache.match(req);
      const network = fetch(req).then((res) => { if (res.ok && res.type === 'basic') cache.put(req, res.clone()); return res; }).catch(() => cached);
      return cached || network;
    }));
    return;
  }

  // Pages HTML : réseau d'abord, repli sur le cache puis la page hors ligne
  if (req.headers.get('accept') && req.headers.get('accept').includes('text/html')) {
    e.respondWith(fetch(req).then((res) => {
      if (res.ok && !url.pathname.startsWith('/admin') && !url.pathname.startsWith('/settings') && !url.pathname.startsWith('/messages')) {
        const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy));
      }
      return res;
    }).catch(async () => (await caches.match(req)) || caches.match('/offline')));
  }
});

self.addEventListener('push', (e) => {
  let data = {};
  try { data = e.data ? e.data.json() : {}; } catch (err) { data = { body: e.data && e.data.text() }; }
  e.waitUntil(self.registration.showNotification(data.title || 'Vwajèn', {
    body: data.body || '', icon: data.icon || '/images/icon-192.png', badge: '/images/icon-64.png', data: { url: data.url || '/' }, lang: 'ht',
  }));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const url = (e.notification.data && e.notification.data.url) || '/';
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
    for (const c of list) { if (c.url === url && 'focus' in c) return c.focus(); }
    return self.clients.openWindow(url);
  }));
});
