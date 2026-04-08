@php($pwa = config('pwa'))
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="{{ $pwa['theme_color'] ?? '#5b2c83' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="{{ $pwa['short_name'] ?? config('app.name') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ ($isHybridApp ?? false) ? 'black-translucent' : 'default' }}">
<meta name="apple-mobile-web-app-title" content="{{ $pwa['short_name'] ?? config('app.name') }}">
<link rel="apple-touch-icon" href="{{ asset($pwa['icon'] ?? 'images/logo.png') }}?v=3">
@if($isHybridApp ?? false)
<meta name="format-detection" content="telephone=no">
<meta name="msapplication-tap-highlight" content="no">
<style>
  /* Safe area for notched devices in WebView / hybrid */
  body {
    padding-top: env(safe-area-inset-top, 0);
    padding-right: env(safe-area-inset-right, 0);
    padding-bottom: env(safe-area-inset-bottom, 0);
    padding-left: env(safe-area-inset-left, 0);
  }
</style>
@endif
