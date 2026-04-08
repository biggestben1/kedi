<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hybrid / native WebView mode
    |--------------------------------------------------------------------------
    |
    | When true, App\Support\HybridApp::detect() runs. Use this for Capacitor,
    | Android WebView, or iOS WKWebView shells so you can adjust UI (safe area,
    | optional service worker, JS bridge).
    |
    */

    'enabled' => env('HYBRID_ENABLED', false),

    /*
    | Query string: ?hybrid=1 — useful for testing hybrid styles without a native app.
    */
    'query_param' => env('HYBRID_QUERY_PARAM', 'hybrid'),

    /*
    | Native apps should send this header on every request (e.g. X-Kedi-Client: hybrid).
    */
    'header_name' => env('HYBRID_HEADER_NAME', 'X-Kedi-Client'),

    'header_value' => env('HYBRID_HEADER_VALUE', 'hybrid'),

    /*
    | Heuristic: treat common WebView user agents as hybrid. Can cause false positives;
    | prefer header-based detection in production.
    */
    'detect_webview_user_agent' => env('HYBRID_DETECT_WEBVIEW_UA', false),

    /*
    | Register the PWA service worker when hybrid mode is active. Set false if the
    | native shell handles caching or SW conflicts with WebView.
    */
    'register_service_worker_in_hybrid' => env('HYBRID_REGISTER_SW', true),

];
