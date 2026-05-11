const CACHE_NAME = 'lovecakes-pos-v1';

// Daftar file Rangkaian UI yang wajib disimpan di brankas HP Kasir
const urlsToCache = [
  './manifest.json',
  './pos/kasir/index.php',
  './pos/kasir/ajax.js'
];

// FASE INSTALL: Satpam mendownload file-file penting
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Opened cache');
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting();
});

// FASE AKTIVASI: Membersihkan memori/cache versi lama jika ada update
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// FASE CEGATAN (FETCH): Mengatur rute saat Online vs Offline
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // 1. JIKA MINTA DATA KE DATABASE (logic_kasir.php, logic.php, dll)
  if (url.pathname.includes('logic')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        // Jika internet mati, kembalikan JSON error buatan sendiri
        return new Response(
          JSON.stringify({ status: 'error', message: 'Anda sedang offline. Mode lokal diaktifkan.' }), 
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // 2. JIKA MINTA HALAMAN WEB & ASET (HTML, JS, CSS, Gambar)
  event.respondWith(
    caches.match(event.request).then(response => {
      // Jika ada di cache (memori lokal), langsung berikan tanpa loading!
      if (response) return response;

      // Jika tidak ada di cache, coba tarik dari internet
      return fetch(event.request).then(fetchRes => {
        // (Opsional) Simpan file baru ke cache agar besok bisa dibuka offline
        return fetchRes;
      }).catch(() => {
        // Jika internet mati dan file tidak ada di cache sama sekali
        // Paksa kembalikan ke halaman kasir utama
        if (event.request.mode === 'navigate') {
          return caches.match('./kasir/index.php');
        }
      });
    })
  );
});