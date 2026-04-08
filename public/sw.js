/**
 * KEDI PWA — service worker (installability + offline navigation fallback).
 * Bump CACHE_NAME + config/pwa.php sw_version when changing this file.
 */
/* global self, clients */

const CACHE_NAME = 'kedi-pwa-v4';
const OFFLINE_URL = new URL('/offline.html', self.location.origin).href;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => cache.add(OFFLINE_URL).catch(() => {}))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.map((key) => {
            if (key !== CACHE_NAME) {
              return caches.delete(key);
            }
            return undefined;
          }),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function shouldNetworkOnly(pathname) {
  return (
    pathname.startsWith('/api/') ||
    pathname.startsWith('/broadcasting/') ||
    pathname.includes('/livewire/') ||
    pathname.includes('sanctum')
  );
}

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);
  if (!isSameOrigin(url)) {
    return;
  }

  if (shouldNetworkOnly(url.pathname)) {
    event.respondWith(fetch(event.request));
    return;
  }

  const accept = event.request.headers.get('accept') || '';
  const isDocument =
    event.request.mode === 'navigate' || accept.includes('text/html');

  if (isDocument) {
    event.respondWith(
      fetch(event.request).catch(() =>
        caches.match(OFFLINE_URL).then(
          (cached) =>
            cached ||
            new Response('Offline', {
              status: 503,
              statusText: 'Offline',
              headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
            }),
        ),
      ),
    );
    return;
  }

  event.respondWith(fetch(event.request));
});
