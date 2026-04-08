<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') – {{ config('app.name') }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    <link id="style" href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        .col-login .header-brand-img {
            background-color: #fff !important;
            max-width: 200px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
    </style>
</head>

<body class="app sidebar-mini ltr">
    <div class="login-img">
        <div id="global-loader">
            <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
        </div>

        <div class="page">
            <div class="">
                <div class="col col-login mx-auto mt-7">
                    <div class="text-center">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img" alt="{{ config('app.name') }}">
                        </a>
                    </div>
                </div>

                <div class="container py-5">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    @yield('footer')

    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/p-scroll/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
</body>

</html>
