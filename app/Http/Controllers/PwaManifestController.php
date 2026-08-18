<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pwa = config('pwa');
        $iconFallback = $pwa['icon'] ?? 'images/logo.png';
        $icon192Path = $pwa['icon_192'] ?? $iconFallback;
        $icon512Path = $pwa['icon_512'] ?? $iconFallback;
        $icon192Url = asset($icon192Path);
        $icon512Url = asset($icon512Path);

        $root = url('/');
        $scopePath = parse_url($root, PHP_URL_PATH);
        $scopePath = ($scopePath === null || $scopePath === '') ? '/' : rtrim($scopePath, '/').'/';

        $shortcuts = [];
        foreach ($pwa['shortcuts'] ?? [] as $sc) {
            if (empty($sc['name']) || empty($sc['path'])) {
                continue;
            }
            $shortcuts[] = [
                'name' => $sc['name'],
                'short_name' => $sc['short_name'] ?? $sc['name'],
                'description' => $sc['description'] ?? '',
                'url' => url($sc['path']),
                'icons' => [
                    [
                        'src' => $icon192Url,
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ],
                ],
            ];
        }

        $data = [
            'id' => $root,
            'name' => $pwa['name'] ?? config('app.name'),
            'short_name' => $pwa['short_name'] ?? config('app.name'),
            'description' => $pwa['description'] ?? '',
            'lang' => str_replace('_', '-', app()->getLocale()),
            'dir' => 'ltr',
            'start_url' => $root,
            'scope' => $scopePath,
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui', 'browser'],
            'orientation' => 'portrait-primary',
            'background_color' => $pwa['background_color'] ?? '#ffffff',
            'theme_color' => $pwa['theme_color'] ?? '#5b2c83',
            'icons' => [
                [
                    'src' => $icon192Url,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ];

        if ($shortcuts !== []) {
            $data['shortcuts'] = $shortcuts;
        }

        return response()
            ->json($data, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
