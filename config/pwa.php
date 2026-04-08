<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web App Manifest
    |--------------------------------------------------------------------------
    |
    | Used by /manifest.webmanifest for installable PWA metadata.
    |
    */

    'name' => env('PWA_NAME', env('APP_NAME', 'KEDI')),

    'short_name' => env('PWA_SHORT_NAME', env('APP_NAME', 'KEDI')),

    'description' => env('PWA_DESCRIPTION', 'KEDI shop and member portal'),

    'theme_color' => env('PWA_THEME_COLOR', '#5b2c83'),

    'background_color' => env('PWA_BACKGROUND_COLOR', '#ffffff'),

    /*
    | Icon paths are under public/ (same as asset()).
    */
    'icon' => env('PWA_ICON', 'images/logo.png'),

    /*
    | Service worker cache version — bump when you change public/sw.js
    */
    'sw_version' => env('PWA_SW_VERSION', '4'),

    /*
    | Optional manifest shortcuts (launcher / long-press on icon).
    | Each item: name, short_name, path (relative to APP_URL), optional description.
    */
    'shortcuts' => [
        [
            'name' => 'Shop',
            'short_name' => 'Shop',
            'path' => '/',
            'description' => 'Browse the store',
        ],
        [
            'name' => 'Dashboard',
            'short_name' => 'Home',
            'path' => '/dashboard',
            'description' => 'Member dashboard',
        ],
    ],

];
