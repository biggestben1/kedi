<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register – {{ config('app.name') }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
    @include('partials.pwa-head')
    <link id="style" href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
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
                        <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img m-0" alt="{{ config('app.name') }}">
                    </div>
                </div>
                <div class="container-login100">
                    <div class="wrap-login100 p-6">
                        <form class="login100-form validate-form" method="POST" action="{{ route('register') }}">
                            @csrf
                            <span class="login100-form-title">Registration</span>

                            @if ($errors->any())
                                <div class="alert alert-danger mb-3">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="wrap-input100 validate-input input-group mb-3">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="mdi mdi-account" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 ms-0 form-control" type="text" name="name" placeholder="User name" value="{{ old('name') }}" required autofocus>
                            </div>
                            <div class="wrap-input100 validate-input input-group mb-3">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-email" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 ms-0 form-control" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                            </div>
                            <div class="wrap-input100 validate-input input-group mb-3" id="Password-toggle">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-eye" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 ms-0 form-control" type="password" name="password" placeholder="Password" required>
                            </div>
                            <div class="wrap-input100 validate-input input-group mb-3" id="Password-toggle2">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-eye" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 ms-0 form-control" type="password" name="password_confirmation" placeholder="Confirm Password" required>
                            </div>
                            <div class="container-login100-form-btn">
                                <button type="submit" class="login100-form-btn btn-primary w-100">Register</button>
                            </div>
                            <div class="text-center pt-3">
                                <p class="text-dark mb-0">Already have account? <a href="{{ route('login') }}" class="text-primary ms-1">Sign In</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('sash/assets/js/show-password.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/p-scroll/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
    @include('partials.pwa-scripts')
</body>

</html>
