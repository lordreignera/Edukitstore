const CACHE_NAME = 'edukit-static-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/images/website/EDUKIT_STORE_logo2.png',
];

const PRIVATE_PATH_PREFIXES = [
    '/admin',
    '/dashboard',
    '/driver',
    '/supplier',
    '/finance',
    '/school',
    '/parent',
    '/user',
    '/login',
    '/logout',
    '/change-password',
    '/two-factor-challenge',
    '/forgot-password',
    '/reset-password',
    '/email',
    '/password',
    '/sanctum',
    '/api',
    '/livewire',
];

const isPrivatePath = (pathname) => PRIVATE_PATH_PREFIXES.some((prefix) => (
    pathname === prefix || pathname.startsWith(`${prefix}/`)
));

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('edukit-static-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

const cacheFirst = async (request) => {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(CACHE_NAME);
        await cache.put(request, response.clone());
    }

    return response;
};

const networkFirstPublicPage = async (request) => {
    try {
        return await fetch(request);
    } catch (error) {
        return caches.match(OFFLINE_URL);
    }
};

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || isPrivatePath(url.pathname)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstPublicPage(request));
        return;
    }

    if (['font', 'image', 'script', 'style'].includes(request.destination)) {
        event.respondWith(cacheFirst(request));
    }
});
