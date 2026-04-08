# PWA & hybrid (WebView) setup

## PWA (installable web app)

- **Manifest:** `GET /manifest.webmanifest` (`PwaManifestController`), configured in `config/pwa.php` and `.env` (`PWA_*`).
- **Icons:** place PNGs under `public/` (default `public/images/logo.png`). For best results add dedicated **192×192** and **512×512** assets and point `PWA_ICON` or extend the manifest controller.
- **Service worker:** `public/sw.js` — network-only for GET requests (avoids caching authenticated Laravel pages). Bump `PWA_SW_VERSION` in `.env` and `CACHE_NAME` in `sw.js` when you change the worker.
- **Blade:** `partials/pwa-head` + `partials/pwa-scripts` are included on shop, auth, admin, and welcome views.

Install: use Chrome/Edge/Samsung Internet → menu → **Install app** / **Add to Home screen**.

## Hybrid (native shell)

The app detects a **hybrid** context so you can tune UI (safe area, status bar) and optionally skip the service worker.

| Method | How |
|--------|-----|
| Query | `?hybrid=1` (configurable `HYBRID_QUERY_PARAM`) — good for local testing |
| Header | Send `X-Kedi-Client: hybrid` on each request from your native WebView (configurable) |
| User-Agent | Optional `HYBRID_DETECT_WEBVIEW_UA=true` — heuristic; prefer headers in production |

### JavaScript globals

- `window.__KEDI_APP__.hybrid` — boolean  
- `window.__KEDI_APP__.pwaStandalone` — installed PWA / standalone display mode  
- `window.kediNative.ready()` — posts to native if you implement:
  - **Android:** `window.AndroidBridge.onPageReady()`
  - **iOS:** `webkit.messageHandlers.kedi.postMessage({ event: 'ready' })`

### Capacitor / Cordova

Point the WebView `server.url` (or `config.xml` `content`) at your `APP_URL`, inject the header from native code on each request if you need reliable hybrid detection without query strings.

## Related files

- `config/pwa.php`, `config/hybrid.php`
- `app/Support/HybridApp.php`
- `app/Http/Controllers/PwaManifestController.php`
- `resources/views/partials/pwa-head.blade.php`, `pwa-scripts.blade.php`
- `public/sw.js`
