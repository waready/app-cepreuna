"use strict";

const CACHE_NAME = "cepreuna-pwa-v3";
const OFFLINE_URL = "/offline";
const PRECACHE_URLS = [
    OFFLINE_URL,
    "/images/icons/icon-192x192.png",
    "/images/icons/icon-512x512.png",
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS.map((url) => new Request(url, { cache: "reload" }))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        Promise.all([
            caches.keys().then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName.startsWith("pwa-") || cacheName.startsWith("cepreuna-pwa-"))
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => caches.delete(cacheName))
                )
            ),
            self.registration.navigationPreload ? self.registration.navigationPreload.enable() : Promise.resolve(),
        ]).then(() => self.clients.claim())
    );
});

self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

async function serveNavigation(event) {
    try {
        const preloadedResponse = await event.preloadResponse;
        return preloadedResponse || (await fetch(event.request));
    } catch (error) {
        return caches.match(OFFLINE_URL);
    }
}

async function servePrecachedAsset(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request, { ignoreSearch: true });
    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
        await cache.put(request, networkResponse.clone());
    }
    return networkResponse;
}

self.addEventListener("fetch", (event) => {
    if (event.request.method !== "GET") {
        return;
    }

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    if (event.request.mode === "navigate") {
        event.respondWith(serveNavigation(event));
        return;
    }

    if (requestUrl.pathname === OFFLINE_URL || requestUrl.pathname.startsWith("/images/icons/")) {
        event.respondWith(servePrecachedAsset(event.request));
    }
});
