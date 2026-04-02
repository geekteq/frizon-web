var CACHE_NAME = 'frizon-v3';
var PRECACHE = [
  '/css/main.css',
  '/css/pages/public.css',
  '/js/app.js',
  '/js/gps.js',
  '/js/lists.js',
  '/js/trips.js',
  '/js/ratings.js',
  '/js/ai.js',
  '/img/frizon-logo.png',
  '/icon-192.png',
  '/favicon-32.png',
  '/apple-touch-icon.png'
];

// Install — precache static assets
self.addEventListener('install', function(e) {
  e.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(PRECACHE);
    }).then(function() {
      return self.skipWaiting();
    })
  );
});

// Activate — clean old caches
self.addEventListener('activate', function(e) {
  e.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(n) { return n !== CACHE_NAME; })
             .map(function(n) { return caches.delete(n); })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});

// Fetch — network first, cache fallback for navigation and static assets
self.addEventListener('fetch', function(e) {
  var url = new URL(e.request.url);

  // Skip non-GET and cross-origin
  if (e.request.method !== 'GET' || url.origin !== location.origin) return;

  // API calls — network only
  if (url.pathname.startsWith('/adm/api/')) return;

  // JS/CSS — network first so code updates are always picked up
  if (url.pathname.match(/\.(css|js)$/)) {
    e.respondWith(
      fetch(e.request).then(function(res) {
        var clone = res.clone();
        caches.open(CACHE_NAME).then(function(c) { c.put(e.request, clone); });
        return res;
      }).catch(function() {
        return caches.match(e.request);
      })
    );
    return;
  }

  // Images/fonts — cache first (ändras sällan)
  if (url.pathname.match(/\.(png|jpg|webp|woff2?|ico)$/)) {
    e.respondWith(
      caches.match(e.request).then(function(cached) {
        return cached || fetch(e.request).then(function(res) {
          var clone = res.clone();
          caches.open(CACHE_NAME).then(function(c) { c.put(e.request, clone); });
          return res;
        });
      })
    );
    return;
  }

  // HTML pages — network first, cache fallback
  if (e.request.headers.get('Accept').includes('text/html')) {
    e.respondWith(
      fetch(e.request).then(function(res) {
        var clone = res.clone();
        caches.open(CACHE_NAME).then(function(c) { c.put(e.request, clone); });
        return res;
      }).catch(function() {
        return caches.match(e.request);
      })
    );
    return;
  }
});
