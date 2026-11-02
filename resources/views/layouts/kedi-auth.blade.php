<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account') – {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}?v=3">
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link href="{{ asset('css/kedi-pos.css') }}?v=3" rel="stylesheet">
</head>
<body class="kedi-pos kedi-auth">
    <main class="kedi-auth-panel">
        <div class="kedi-auth-logo">
            <img src="{{ asset('images/logo.png') }}?v=3" alt="{{ config('app.name') }}">
        </div>
        <div class="kedi-brand" style="justify-content:center; height:auto; padding:0;">
            <div style="text-align:center;">
                <strong>KEDI POS</strong>
                <span>HEALTHCARE</span>
            </div>
        </div>
        @yield('content')
    </main>
    @include('partials.pwa-scripts')
</body>
</html>
