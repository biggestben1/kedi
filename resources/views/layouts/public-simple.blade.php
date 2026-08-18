<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
</head>
<body class="bg-light">
    <div class="page">
        <div class="page-main">
            <header class="border-bottom bg-white py-3 mb-4">
                <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none text-dark">
                        <img src="{{ asset('images/logo.png') }}?v=3" alt="{{ config('app.name') }}" style="max-height: 48px; width: auto;" onerror="this.style.display='none'">
                        <span class="ms-2 fw-semibold">{{ config('app.name') }}</span>
                    </a>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('questionnaires.index') }}" class="btn btn-sm btn-outline-primary">Questionnaires</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Log in</a>
                        @endauth
                    </div>
                </div>
            </header>
            <main class="container pb-5" style="max-width: 720px;">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
