<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contact Us – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    <link id="style" href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        .contact-logo-wrap {
            background-color: #fff !important;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .contact-logo-wrap img {
            max-width: 200px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
    </style>
</head>
<body class="app sidebar-mini ltr">
    <div class="login-img">
        <div id="global-loader">
            <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
        </div>
        <div class="page">
            <div class="container-login100">
                <div class="wrap-login100 p-6" style="max-width: 560px;">
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="d-inline-block contact-logo-wrap">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" alt="{{ config('app.name') }}">
                        </a>
                    </div>
                    <form class="login100-form validate-form" method="POST" action="{{ route('contact.store') }}">
                        @csrf
                        <span class="login100-form-title pb-4">Contact Us</span>

                        @if (session('success'))
                            <div class="alert alert-success mb-3">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-muted mb-4">Have a question or feedback? Send us a message and we'll get back to you soon.</p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="name" placeholder="Your name" value="{{ $name }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input class="form-control" type="email" name="email" placeholder="your@email.com" value="{{ $email }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input class="form-control" type="text" name="phone" placeholder="Phone number (optional)" value="{{ old('phone') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="subject" placeholder="What is this about?" value="{{ old('subject') }}" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="4" placeholder="Your message..." required>{{ old('message') }}</textarea>
                        </div>
                        <div class="container-login100-form-btn">
                            <button type="submit" class="login100-form-btn btn-primary w-100">Send Message</button>
                        </div>
                        <div class="text-center pt-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="text-primary">Back to Dashboard</a>
                            @else
                                <a href="{{ url('/') }}" class="text-primary me-3">Back to Shop</a>
                                <a href="{{ route('login') }}" class="text-primary">Sign In</a>
                            @endauth
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/p-scroll/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
</body>
</html>
