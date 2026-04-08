@php
    $cartCount = $cartCount ?? 0;
    $pageTitle = $pageTitle ?? 'Blog';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    @include('partials.blog-rich-content-css')
    @include('partials.blog-details-sash-css')
    @stack('styles')
</head>
<body class="app sidebar-mini ltr public-blog-page">
    <div id="global-loader">
        <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>
    <div class="page">
        <div class="page-main">
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex align-items-center w-100 py-2">
                        <a class="me-3" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" alt="{{ config('app.name') }}" style="max-height: 44px;">
                        </a>
                        <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-primary">Community blog</a>
                            <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary">Shop</a>
                            @auth
                                <a href="{{ route('my-blog.index') }}" class="btn btn-sm btn-outline-secondary">My blog</a>
                                <a class="nav-link icon text-center" href="{{ url('/') }}">
                                    <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge">{{ $cartCount }}</span>
                                </a>
                                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Login</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid py-4">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center flex-row-reverse">
                <div class="col-md-12 col-sm-12 text-center">
                    Copyright © <span id="year"></span> <a href="{{ url('/') }}">KEDI</a>. All rights reserved.
                </div>
            </div>
        </div>
        @include('partials.cloud-footer')
    </footer>
    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
    @include('partials.pwa-scripts')
    @stack('scripts')
</body>
</html>
