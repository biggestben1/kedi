<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Detect when the site runs inside a native/hybrid shell (WebView, Capacitor, custom header).
 */
class HybridApp
{
    public static function detect(?Request $request = null): bool
    {
        if (! config('hybrid.enabled', true)) {
            return false;
        }

        $request ??= request();

        $param = config('hybrid.query_param', 'hybrid');
        if ($param !== null && $param !== '' && $request->boolean($param)) {
            return true;
        }

        $name = config('hybrid.header_name');
        $value = config('hybrid.header_value');
        if ($name !== null && $name !== '' && $value !== null && $request->header($name) === $value) {
            return true;
        }

        if (! config('hybrid.detect_webview_user_agent', false)) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());

        // Android System WebView
        if (str_contains($ua, '; wv')) {
            return true;
        }

        // Generic WebView hints (noisy — keep behind config flag)
        return str_contains($ua, 'webview')
            || str_contains($ua, 'capacitor')
            || str_contains($ua, 'cordova');
    }
}
