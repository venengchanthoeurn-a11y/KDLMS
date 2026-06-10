// ================================================================
// sw.js — KDLMS Service Worker (PWA)
// Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

const CACHE_NAME    = 'kdlms-v1.0';
const OFFLINE_URL   = '/offline.html';

// Assets to cache immediately on SW install (App Shell)
const APP_SHELL = [
    '/',
    '/browse.php',
    '/login.php',
    '/offline.html',
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/css/admin.css',
    '/assets/js/main.js',
    '/assets/js/admin.js',
    '/assets/img/icons/icon-192.png',
    '/assets/img/icons/icon-512.png',
    '/assets/img/khmer-pattern.svg',
    // External CDN (cached for offline use)
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://fonts.googleapis.com/css2?family=Moul&family=Hanuman:wght@300;400;700&family=Kantumruy+Pro:wght@300;400;600;700&family=Inter:wght@300;400;500;600;700&display=swap',
];

// ── INSTALL: Pre-cache app shell ─────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching app shell');
            // Cache each individually so one failure doesn't break all
            return Promise.allSettled(
                APP_SHELL.map(url =>
                    cache.add(url).catch(err =>
                        console.warn('[SW] Failed to cache:', url, err)
                    )
                )
            );
        }).then(() => self.skipWaiting())
    );
});

// ── ACTIVATE: Clean old caches ───────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => {
                        console.log('[SW] Deleting old cache:', key);
                        return caches.delete(key);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// ── FETCH: Network-first for PHP pages, Cache-first for assets ───
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (POST forms, API calls)
    if (request.method !== 'GET') return;

    // Skip Chrome extensions and dev tools
    if (url.protocol === 'chrome-extension:') return;

    // Skip download requests — always fresh
    if (url.pathname.includes('download.php')) return;

    // Strategy: Cache-first for static assets
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Strategy: Network-first for PHP pages (always fresh data)
    // Falls back to offline page if network unavailable
    event.respondWith(networkFirstWithOfflineFallback(request));
});

// ── Strategy: Cache First ────────────────────────────────────────
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Asset unavailable offline', { status: 503 });
    }
}

// ── Strategy: Network First with offline fallback ────────────────
async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request, { signal: AbortSignal.timeout(8000) });
        // Update cache with fresh response for key pages
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        // Network failed — try cache first
        const cached = await caches.match(request);
        if (cached) return cached;
        // Final fallback: offline page
        return caches.match(OFFLINE_URL);
    }
}

// ── Helper: Is this a static asset? ─────────────────────────────
function isStaticAsset(url) {
    const staticExts = [
        '.css', '.js', '.png', '.jpg', '.jpeg',
        '.svg', '.ico', '.woff', '.woff2', '.ttf', '.gif', '.webp'
    ];
    return (
        staticExts.some(ext => url.pathname.endsWith(ext)) ||
        url.hostname === 'cdn.jsdelivr.net' ||
        url.hostname === 'cdnjs.cloudflare.com' ||
        url.hostname === 'fonts.googleapis.com' ||
        url.hostname === 'fonts.gstatic.com'
    );
}

// ── Background Sync placeholder (for future offline upload queue)
self.addEventListener('sync', (event) => {
    if (event.tag === 'kdlms-sync') {
        console.log('[SW] Background sync triggered');
    }
});

// ── Push Notifications placeholder
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        self.registration.showNotification(data.title || 'KDLMS', {
            body: data.body || 'New content available!',
            icon: '/assets/img/icons/icon-192.png',
            badge: '/assets/img/icons/icon-72.png',
            tag: 'kdlms-push',
        });
    }
});
