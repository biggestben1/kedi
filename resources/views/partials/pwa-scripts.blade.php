@php
    $pwaScopePath = parse_url(url('/'), PHP_URL_PATH);
    $pwaScopePath = ($pwaScopePath === null || $pwaScopePath === '') ? '/' : rtrim($pwaScopePath, '/') . '/';
    $registerSw = (! ($isHybridApp ?? false)) || config('hybrid.register_service_worker_in_hybrid', true);
@endphp
<script>
(function () {
  window.__KEDI_APP__ = window.__KEDI_APP__ || {};
  var hybrid = {{ ($isHybridApp ?? false) ? 'true' : 'false' }};
  var standalone = false;
  try {
    standalone = window.matchMedia('(display-mode: standalone)').matches
      || window.matchMedia('(display-mode: fullscreen)').matches
      || (typeof window.navigator !== 'undefined' && window.navigator.standalone === true);
  } catch (e) {}
  window.__KEDI_APP__.hybrid = hybrid;
  window.__KEDI_APP__.pwaStandalone = standalone;
  window.__KEDI_APP__.version = '1';

  if (hybrid) {
    document.documentElement.setAttribute('data-hybrid', '1');
    if (document.body) {
      document.body.classList.add('kedi-hybrid');
    }
    var m = document.querySelector('meta[name="viewport"]');
    if (m && m.getAttribute('content').indexOf('viewport-fit') === -1) {
      m.setAttribute('content', m.getAttribute('content') + ', viewport-fit=cover');
    }
  }

  @if($registerSw)
  if (!('serviceWorker' in navigator)) return;
  window.addEventListener('load', function () {
    var swUrl = '{{ url('/sw.js') }}?v={{ config('pwa.sw_version', '1') }}';
    navigator.serviceWorker.register(swUrl, { scope: '{{ $pwaScopePath }}' }).catch(function () {});
  });
  @endif
})();

/** Optional native bridge — extend `window.kediNative` from your shell if needed. */
window.kediNative = window.kediNative || {};
if (typeof window.kediNative.ready !== 'function') {
  window.kediNative.ready = function () {
    try {
      if (window.AndroidBridge && typeof window.AndroidBridge.onPageReady === 'function') {
        window.AndroidBridge.onPageReady();
      }
    } catch (e) {}
    try {
      if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.kedi) {
        window.webkit.messageHandlers.kedi.postMessage({ event: 'ready' });
      }
    } catch (e) {}
  };
}
document.addEventListener('DOMContentLoaded', function () {
  try {
    if (window.kediNative && typeof window.kediNative.ready === 'function') {
      window.kediNative.ready();
    }
  } catch (e) {}
});
</script>
