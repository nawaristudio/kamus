const CACHE_NAME = "kamus-v2";
const FILES_TO_CACHE = [
    "/",
    "/index.php",
    "/manifest.json",
    "/icons/icon-192.png",
    "/icons/icon-512.png",
    "https://cdn.tailwindcss.com"
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(FILES_TO_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});