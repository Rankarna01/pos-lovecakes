const CACHE_NAME = 'pos-v1';
const urlsToCache = [
  './',
  './manifest.json',
  './kasir/index.php', // Cache halaman kasir
  './components/header.php',
  './assets/js/tailwind.js',
  './assets/js/alpine.min.js',
  './assets/js/sweetalert2.all.js',
  './assets/js/localforage.min.js',
  './assets/js/pos_db.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  // Biarkan request ke logic.php / API langsung ke network (karena butuh database asli)
  if (event.request.url.includes('logic.php') || event.request.url.includes('api.php')) {
      event.respondWith(fetch(event.request).catch(() => {
          return new Response(JSON.stringify({ status: 'error', message: 'Anda sedang offline. Data akan disimpan lokal.' }), { headers: { 'Content-Type': 'application/json' }});
      }));
      return;
  }

  // Untuk halaman index.php & aset, ambil dari cache kalau offline
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    }).catch(() => {
        // Fallback jika sama sekali tidak ada koneksi dan tidak ada cache
        return caches.match('./kasir/index.php');
    })
  );
});