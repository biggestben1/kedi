<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KEDI Shop – {{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" />
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        /* Override Sash theme: logo-horizontal and header logos are hidden by default */
        .app-header .logo-horizontal {
            display: block !important;
            min-width: 120px;
        }
        .app-header .logo-horizontal img {
            max-height: 52px;
            max-width: 200px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block !important;
            visibility: visible !important;
        }
        .app-sidebar .side-header .header-brand-img {
            max-height: 58px;
            max-width: 100%;
            display: block !important;
            visibility: visible !important;
        }
        /* Slightly bigger font for Categories block on shop home */
        .kedi-categories-heading { font-size: 1.1rem !important; }
        .kedi-categories-list, .kedi-categories-list a { font-size: 1rem !important; }
    </style>
</head>
<body class="app sidebar-mini ltr">
    <div id="global-loader">
        <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>

    <div class="page">
        <div class="page-main">
            <!-- app-Header -->
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex">
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                        <a class="logo-horizontal" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}';">
                        </a>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4" aria-controls="navbarSupportedContent-4" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                                    <div class="d-flex order-lg-2">
                                        <div class="d-flex country">
                                            <a class="nav-link icon theme-layout nav-link-bg layout-setting">
                                                <span class="dark-layout"><i class="fe fe-moon"></i></span>
                                                <span class="light-layout"><i class="fe fe-sun"></i></span>
                                            </a>
                                        </div>
                                        <div class="dropdown d-flex shopping-cart">
                                            <a class="nav-link icon text-center" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge" id="headerCartCount">{{ $cartCount }}</span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                                <div class="drop-heading border-bottom">
                                                    <div class="d-flex">
                                                        <h6 class="mt-1 mb-0 fs-16 fw-semibold text-dark">My Shopping Cart</h6>
                                                    </div>
                                                </div>
                                                <div class="header-dropdown-list message-menu">
                                                    <div id="cartDropdownItems">
                                                        @forelse($cartItems as $item)
                                                        <div class="dropdown-item d-flex p-4">
                                                            <div class="wd-50p">
                                                                <h5 class="mb-1">{{ $item->product->item_code }} – {{ $item->product->name }}</h5>
                                                                @if($item->product->category)
                                                                <small class="text-muted">Category: {{ $item->product->category->name }}</small>
                                                                @endif
                                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary js-cart-dec" data-item-code="{{ $item->product->item_code }}">-</button>
                                                                    <span class="fs-13 text-muted mb-0">Qty: <strong class="js-cart-qty" data-item-code="{{ $item->product->item_code }}">{{ $item->quantity }}</strong></span>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary js-cart-inc" data-item-code="{{ $item->product->item_code }}">+</button>
                                                                </div>
                                                            </div>
                                                            <div class="ms-auto text-end d-flex fs-16 align-items-center">
                                                                <span class="fs-16 text-dark d-none d-sm-block px-4 js-cart-line-total" data-item-code="{{ $item->product->item_code }}">₦{{ number_format($item->line_total, 0) }}</span>
                                                                <button type="button" class="fs-16 btn p-0 cart-trash border-0 bg-transparent js-cart-remove" data-item-code="{{ $item->product->item_code }}"><i class="fe fe-trash-2 border text-danger brround d-block p-2"></i></button>
                                                            </div>
                                                        </div>
                                                        @empty
                                                        <div class="dropdown-item d-flex p-4">
                                                            <p class="text-muted mb-0">Your cart is empty.</p>
                                                        </div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                <div class="dropdown-divider m-0" id="cartDropdownDivider" style="{{ count($cartItems) > 0 ? '' : 'display:none;' }}"></div>
                                                <div class="dropdown-footer" id="cartDropdownFooter" style="{{ count($cartItems) > 0 ? '' : 'display:none;' }}">
                                                    @auth
                                                    <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-pill btn-sm py-2"><i class="fe fe-credit-card me-1"></i> Checkout</a>
                                                    @else
                                                    <a href="{{ route('login') }}" class="btn btn-primary btn-pill btn-sm py-2">Login to Checkout</a>
                                                    @endauth
                                                    <span class="float-end p-2 fs-17 fw-semibold">Total: <span id="cartDropdownTotal">₦{{ number_format($cartSubtotal, 0) }}</span></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown d-flex profile-1">
                                            <a href="javascript:void(0)" data-bs-toggle="dropdown" class="nav-link leading-none d-flex">
                                                <span class="avatar profile-user brround cover-image bg-primary text-white d-flex align-items-center justify-content-center">{{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : '?' }}</span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                                @auth
                                                <div class="drop-heading">
                                                    <div class="text-center">
                                                        <h5 class="text-dark mb-0 fs-14 fw-semibold">{{ auth()->user()->name }}</h5>
                                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                                    </div>
                                                </div>
                                                <div class="dropdown-divider m-0"></div>
                                                <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="dropdown-icon fe fe-user"></i> Dashboard</a>
                                                <a class="dropdown-item" href="{{ route('orders.index') }}"><i class="dropdown-icon fe fe-package"></i> My Orders</a>
                                                <a class="dropdown-item" href="{{ route('invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> My Invoices</a>
                                                <a class="dropdown-item" href="{{ route('wallet.index') }}"><i class="dropdown-icon fe fe-dollar-sign"></i> Wallet</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ auth()->user()->role?->name === 'reseller' ? 'Go to Reseller' : (auth()->user()->role?->name === 'accountant' ? 'Go to Accountant Panel' : (auth()->user()->role?->name === 'dispatch' ? 'Go to Dispatch Panel' : 'Go to Admin')) }}</a>
                                                @endif
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><i class="dropdown-icon fe fe-log-out"></i> Sign out</button>
                                                </form>
                                                @else
                                                <a class="dropdown-item" href="{{ route('login') }}"><i class="dropdown-icon fe fe-log-in"></i> Login</a>
                                                <a class="dropdown-item" href="{{ route('register') }}"><i class="dropdown-icon fe fe-user-plus"></i> Register</a>
                                                @endauth
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /app-Header -->

            <!-- APP-SIDEBAR -->
            <div class="sticky">
                <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
                <div class="app-sidebar">
                    <div class="side-header">
                        <a class="header-brand1" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}';">
                        </a>
                    </div>
                    <div class="main-sidemenu">
                        <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"/></svg></div>
                        <ul class="side-menu">
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ url('/') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
                            </li>
                            @auth
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch')
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ auth()->user()->role?->name === 'reseller' ? 'Reseller' : (auth()->user()->role?->name === 'accountant' ? 'Accountant Panel' : (auth()->user()->role?->name === 'dispatch' ? 'Dispatch Panel' : 'Admin')) }}</span></a>
                            </li>
                            @endif
                            @endauth
                            <li class="sub-category"><h3>Account</h3></li>
                            @guest
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('login') }}"><i class="side-menu__icon fe fe-log-in"></i><span class="side-menu__label">Login</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('register') }}"><i class="side-menu__icon fe fe-user-plus"></i><span class="side-menu__label">Register</span></a>
                            </li>
                            @else
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-user"></i><span class="side-menu__label">Profile</span></a>
                            </li>
                            <li class="slide">
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
                                </form>
                            </li>
                            @endguest
                        </ul>
                        <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"/></svg></div>
                    </div>
                </div>
            </div>
            <!--/APP-SIDEBAR-->

            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        <div class="page-header">
                            <h1 class="page-title">Shop</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">E-Commerce</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Shop</li>
                                </ol>
                            </div>
                        </div>

                        @if(session('message'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row row-cards">
                            <div class="col-xl-3 col-lg-4">
                                <div class="row">
                                    <div class="col-md-12 col-lg-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="card-title">KEDI Products</div>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small mb-3">Browse and add items to your cart. Use the search above to filter by code, name, or pack size.</p>
                                                <p class="fw-semibold mb-2 kedi-categories-heading">Categories</p>
                                                <ul class="list-unstyled mb-0 kedi-categories-list">
                                                    <li class="mb-1">
                                                        <a href="{{ url('/') }}" class="{{ !request()->filled('category_id') ? 'fw-semibold text-primary' : 'text-muted' }}">All</a>
                                                    </li>
                                                    @foreach($categories ?? [] as $cat)
                                                    <li class="mb-1">
                                                        <a href="{{ url('/') }}?category_id={{ $cat->id }}" class="{{ (request()->query('category_id') == $cat->id) ? 'fw-semibold text-primary' : 'text-muted' }}">{{ $cat->name }}</a>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                                @auth
                                                <hr class="my-3">
                                                <a href="{{ route('orders.index') }}" class="btn btn-outline-primary btn-sm w-100"><i class="fe fe-package me-1"></i> My Orders</a>
                                                <p class="text-muted small mb-0 mt-2">View your orders and tracking numbers.</p>
                                                @endauth
                                            </div>
                                        </div>
                                        @if(count($cartItems) > 0)
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="card-title">Cart Summary</div>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Items:</strong> <span id="cartSummaryCount">{{ $cartCount }}</span></p>
                                                <p class="mb-1"><strong>Subtotal:</strong> <span id="cartSummarySubtotal">₦{{ number_format($cartSubtotal, 0) }}</span></p>
                                                <p class="mb-2 small text-muted">BV: <span id="cartSummaryBv">{{ number_format($cartBv, 1) }}</span> &nbsp; PV: <span id="cartSummaryPv">{{ number_format($cartPv, 1) }}</span></p>
                                                @auth
                                                <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-sm w-100 mb-2"><i class="fe fe-credit-card me-1"></i> Checkout</a>
                                                @else
                                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm w-100 mb-2">Login to Checkout</a>
                                                @endauth
                                                <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Clear cart?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Clear Cart</button>
                                                </form>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-9 col-lg-8">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div class="card p-0">
                                            <div class="card-body p-4">
                                                <div class="row">
                                                    <div class="col-xl-5 col-lg-8 col-md-8 col-sm-8">
                                                        <div class="input-group d-flex w-100 float-start">
                                                            <input type="text" id="productSearch" class="form-control border-end-0 my-2" placeholder="Search by code, name, pack size..." autocomplete="off">
                                                            <button class="btn input-group-text bg-transparent border-start-0 text-muted my-2">
                                                                <i class="fe fe-search text-muted" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-4">
                                                        <ul class="nav item2-gl-menu float-end my-2">
                                                            <li class="border-end"><a href="#tab-grid" class="show active" data-bs-toggle="tab" title="Grid"><i class="fa fa-th"></i></a></li>
                                                            <li><a href="#tab-list" data-bs-toggle="tab" title="List"><i class="fa fa-list"></i></a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="productSearchNoResults" class="alert alert-warning mb-3" style="display: none;">No products match your search.</div>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="tab-grid">
                                                <div class="row" id="productList">
                                                    @forelse($products as $product)
                                                    <div class="col-md-6 col-xl-4 col-sm-6 product-item" data-item-code="{{ strtolower($product->item_code) }}" data-name="{{ strtolower($product->name) }}" data-pack-size="{{ strtolower($product->pack_size ?? '') }}" data-display-name="{{ strtolower($product->display_name) }}">
                                                        <div class="card">
                                                            <div class="product-grid6">
                                                                <div class="product-image6 p-5">
                                                                    <a href="javascript:void(0)" class="bg-light d-block text-center py-4">
                                                                        <span class="badge bg-primary fs-14">{{ $product->item_code }}</span>
                                                                    </a>
                                                                </div>
                                                                <div class="card-body pt-0">
                                                                    <div class="product-content text-center">
                                                                        <h1 class="title fw-bold fs-20"><a href="javascript:void(0)" class="text-dark">{{ $product->display_name }}</a></h1>
                                                                        <p class="text-muted small mb-2">BV: {{ $product->bv }} &nbsp; PV: {{ $product->pv }}</p>
                                                                        <div class="price">{{ $product->formatted_display_price }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-footer text-center">
                                                                    <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form">
                                                                        @csrf
                                                                        <input type="hidden" name="item_code" value="{{ $product->item_code }}">
                                                                        <input type="hidden" name="quantity" value="1">
                                                                        <button type="submit" class="btn btn-primary mb-1 w-100"><i class="fe fe-shopping-cart me-2"></i>Add to cart</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @empty
                                                    <div class="col-12">
                                                        <div class="alert alert-warning mb-0">No products available.</div>
                                                    </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                            <div class="tab-pane" id="tab-list">
                                                <div class="row" id="productListList">
                                                    @foreach($products as $product)
                                                    <div class="col-12 product-item mb-3" data-item-code="{{ strtolower($product->item_code) }}" data-name="{{ strtolower($product->name) }}" data-pack-size="{{ strtolower($product->pack_size ?? '') }}" data-display-name="{{ strtolower($product->display_name) }}">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="row align-items-center">
                                                                    <div class="col-auto">
                                                                        <span class="badge bg-primary fs-14">{{ $product->item_code }}</span>
                                                                    </div>
                                                                    <div class="col">
                                                                        <h5 class="mb-1">{{ $product->display_name }}</h5>
                                                                        <p class="text-muted small mb-0">BV: {{ $product->bv }} &nbsp; PV: {{ $product->pv }}</p>
                                                                    </div>
                                                                    <div class="col-auto text-end">
                                                                        <strong class="text-primary">{{ $product->formatted_display_price }}</strong>
                                                                        <br><span class="text-muted small">Retail: {{ $product->formatted_retail_price }}</span>
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form d-inline">
                                                                            @csrf
                                                                            <input type="hidden" name="item_code" value="{{ $product->item_code }}">
                                                                            <input type="hidden" name="quantity" value="1">
                                                                            <button type="submit" class="btn btn-primary btn-sm"><i class="fe fe-shopping-cart me-1"></i>Add to cart</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart offcanvas (mobile) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Cart (<span id="cartOffcanvasCount">{{ $cartCount }}</span> items)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            @if(count($cartItems) > 0)
                <div class="list-group list-group-flush" id="cartOffcanvasItems">
                    @foreach($cartItems as $item)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="me-2">
                                    <strong>{{ $item->product->item_code }}</strong> {{ $item->product->name }}<br>
                                    @if($item->product->category)
                                    <small class="text-muted">Category: {{ $item->product->category->name }}</small><br>
                                    @endif
                                    <small class="text-muted">Line: <span class="js-cart-line-total" data-item-code="{{ $item->product->item_code }}">₦{{ number_format($item->line_total, 0) }}</span></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger js-cart-remove" data-item-code="{{ $item->product->item_code }}">&times;</button>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-cart-dec" data-item-code="{{ $item->product->item_code }}">-</button>
                                <span>Qty: <strong class="js-cart-qty" data-item-code="{{ $item->product->item_code }}">{{ $item->quantity }}</strong></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-cart-inc" data-item-code="{{ $item->product->item_code }}">+</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
                <p><strong>Subtotal:</strong> <span id="cartOffcanvasSubtotal">₦{{ number_format($cartSubtotal, 0) }}</span></p>
                <p class="small text-muted">BV: <span id="cartOffcanvasBv">{{ number_format($cartBv, 1) }}</span> &nbsp; PV: <span id="cartOffcanvasPv">{{ number_format($cartPv, 1) }}</span></p>
                @auth
                <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-sm w-100 mb-2">Checkout</a>
                @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm w-100 mb-2">Login to Checkout</a>
                @endauth
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 js-cart-clear">Clear Cart</button>
            @else
                <p class="text-muted">Your cart is empty.</p>
            @endif
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
    </footer>
    <a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>

    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidemenu/sidemenu.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidebar/sidebar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/sticky.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        const CSRF = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '';

        function money(n) {
            try { return '₦' + Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }); } catch (e) { return '₦' + (n || 0); }
        }

        async function cartFetch(url, options) {
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    ...(options && options.headers ? options.headers : {}),
                },
                ...options,
            });

            const contentType = res.headers.get('content-type') || '';
            let data = null;
            if (contentType.includes('application/json')) {
                data = await res.json().catch(() => null);
            } else {
                const text = await res.text().catch(() => '');
                data = { error: text ? text.slice(0, 400) : null };
            }

            if (!res.ok) {
                throw { status: res.status, ...(data || {}) };
            }
            return data || {};
        }

        function renderCartDropdown(items) {
            const el = document.getElementById('cartDropdownItems');
            if (!el) return;
            if (!items || items.length === 0) {
                el.innerHTML = '<div class=\"dropdown-item d-flex p-4\"><p class=\"text-muted mb-0\">Your cart is empty.</p></div>';
                return;
            }
            el.innerHTML = items.map((it) => {
                const code = it.product.item_code;
                const name = it.product.name;
                const categoryName = it.product.category && it.product.category.name ? it.product.category.name : '';
                const qty = it.quantity;
                const line = it.line_total;
                const categoryHtml = categoryName ? `<small class=\"text-muted\">Category: ${categoryName}</small>` : '';
                return `
                    <div class=\"dropdown-item d-flex p-4\">
                        <div class=\"wd-50p\">
                            <h5 class=\"mb-1\">${code} – ${name}</h5>
                            ${categoryHtml}
                            <div class=\"d-flex align-items-center gap-2 mt-2\">
                                <button type=\"button\" class=\"btn btn-sm btn-outline-secondary js-cart-dec\" data-item-code=\"${code}\">-</button>
                                <span class=\"fs-13 text-muted mb-0\">Qty: <strong class=\"js-cart-qty\" data-item-code=\"${code}\">${qty}</strong></span>
                                <button type=\"button\" class=\"btn btn-sm btn-outline-secondary js-cart-inc\" data-item-code=\"${code}\">+</button>
                            </div>
                        </div>
                        <div class=\"ms-auto text-end d-flex fs-16 align-items-center\">
                            <span class=\"fs-16 text-dark d-none d-sm-block px-4 js-cart-line-total\" data-item-code=\"${code}\">${money(line)}</span>
                            <button type=\"button\" class=\"fs-16 btn p-0 cart-trash border-0 bg-transparent js-cart-remove\" data-item-code=\"${code}\"><i class=\"fe fe-trash-2 border text-danger brround d-block p-2\"></i></button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderCartOffcanvas(items) {
            const el = document.getElementById('cartOffcanvasItems');
            if (!el) return;
            if (!items || items.length === 0) {
                el.innerHTML = '<p class=\"text-muted\">Your cart is empty.</p>';
                return;
            }
            el.innerHTML = items.map((it) => {
                const code = it.product.item_code;
                const name = it.product.name;
                const categoryName = it.product.category && it.product.category.name ? it.product.category.name : '';
                const qty = it.quantity;
                const line = it.line_total;
                const categoryHtml = categoryName ? `<small class=\"text-muted\">Category: ${categoryName}</small><br>` : '';
                return `
                    <div class=\"list-group-item\">
                        <div class=\"d-flex justify-content-between align-items-start\">
                            <div class=\"me-2\">
                                <strong>${code}</strong> ${name}<br>
                                ${categoryHtml}
                                <small class=\"text-muted\">Line: <span class=\"js-cart-line-total\" data-item-code=\"${code}\">${money(line)}</span></small>
                            </div>
                            <button type=\"button\" class=\"btn btn-sm btn-outline-danger js-cart-remove\" data-item-code=\"${code}\">&times;</button>
                        </div>
                        <div class=\"d-flex align-items-center gap-2 mt-2\">
                            <button type=\"button\" class=\"btn btn-sm btn-outline-secondary js-cart-dec\" data-item-code=\"${code}\">-</button>
                            <span>Qty: <strong class=\"js-cart-qty\" data-item-code=\"${code}\">${qty}</strong></span>
                            <button type=\"button\" class=\"btn btn-sm btn-outline-secondary js-cart-inc\" data-item-code=\"${code}\">+</button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function applyCartTotals(data) {
            const count = Number(data.count || 0);
            const subtotal = Number(data.subtotal || 0);
            const bv = Number(data.total_bv || 0);
            const pv = Number(data.total_pv || 0);

            const headerCount = document.getElementById('headerCartCount');
            if (headerCount) headerCount.textContent = String(count);

            const ddTotal = document.getElementById('cartDropdownTotal');
            if (ddTotal) ddTotal.textContent = money(subtotal);

            const ddFooter = document.getElementById('cartDropdownFooter');
            const ddDivider = document.getElementById('cartDropdownDivider');
            if (ddFooter) ddFooter.style.display = count > 0 ? '' : 'none';
            if (ddDivider) ddDivider.style.display = count > 0 ? '' : 'none';

            const sumCount = document.getElementById('cartSummaryCount');
            if (sumCount) sumCount.textContent = String(count);
            const sumSubtotal = document.getElementById('cartSummarySubtotal');
            if (sumSubtotal) sumSubtotal.textContent = money(subtotal);
            const sumBv = document.getElementById('cartSummaryBv');
            if (sumBv) sumBv.textContent = bv.toFixed(1);
            const sumPv = document.getElementById('cartSummaryPv');
            if (sumPv) sumPv.textContent = pv.toFixed(1);

            const offCount = document.getElementById('cartOffcanvasCount');
            if (offCount) offCount.textContent = String(count);
            const offSubtotal = document.getElementById('cartOffcanvasSubtotal');
            if (offSubtotal) offSubtotal.textContent = money(subtotal);
            const offBv = document.getElementById('cartOffcanvasBv');
            if (offBv) offBv.textContent = bv.toFixed(1);
            const offPv = document.getElementById('cartOffcanvasPv');
            if (offPv) offPv.textContent = pv.toFixed(1);
        }

        async function refreshCart() {
            const data = await cartFetch('{{ route('cart.index') }}', { method: 'GET' });
            renderCartDropdown(data.items || []);
            renderCartOffcanvas(data.items || []);
            applyCartTotals(data);
            return data;
        }

        async function cartAdd(itemCode, quantity) {
            const body = new URLSearchParams();
            body.set('_token', CSRF);
            body.set('item_code', itemCode);
            body.set('quantity', String(quantity || 1));
            await cartFetch('{{ route('cart.add') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
            });
            return refreshCart();
        }

        async function cartUpdate(itemCode, quantity) {
            const body = new URLSearchParams();
            body.set('_token', CSRF);
            body.set('item_code', itemCode);
            body.set('quantity', String(quantity));
            await cartFetch('{{ route('cart.update') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
            });
            return refreshCart();
        }

        async function cartRemove(itemCode) {
            const url = '{{ url('/cart/remove') }}/' + encodeURIComponent(itemCode);
            await cartFetch(url, { method: 'DELETE' });
            return refreshCart();
        }

        async function cartClear() {
            await cartFetch('{{ route('cart.clear') }}', { method: 'DELETE' });
            return refreshCart();
        }

        // AJAX add-to-cart
        document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type=\"submit\"]');
                const itemCode = this.querySelector('input[name=\"item_code\"]')?.value;
                const qty = Number(this.querySelector('input[name=\"quantity\"]')?.value || 1);
                if (!itemCode) return;

                if (btn) { btn.disabled = true; btn.innerHTML = '<i class=\"fe fe-loader me-2\"></i>Adding...'; }
                try {
                    await cartAdd(itemCode, qty);
                    if (btn) { btn.innerHTML = '<i class=\"fe fe-check me-2\"></i>Added'; }
                    setTimeout(() => { if (btn) { btn.disabled = false; btn.innerHTML = '<i class=\"fe fe-shopping-cart me-2\"></i>Add to cart'; } }, 700);
                } catch (err) {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class=\"fe fe-shopping-cart me-2\"></i>Add to cart'; }
                    const msg = err?.message || err?.error || (err?.status ? ('Request failed (HTTP ' + err.status + ')') : null) || 'Failed to add to cart';
                    alert(msg);
                }
            });
        });

        // Cart + / - / remove / clear (event delegation)
        document.addEventListener('click', async function(e) {
            const inc = e.target.closest('.js-cart-inc');
            const dec = e.target.closest('.js-cart-dec');
            const rem = e.target.closest('.js-cart-remove');
            const clr = e.target.closest('.js-cart-clear');
            if (!inc && !dec && !rem && !clr) return;

            e.preventDefault();

            try {
                if (clr) {
                    if (!confirm('Clear cart?')) return;
                    await cartClear();
                    return;
                }

                const code = (inc || dec || rem).getAttribute('data-item-code');
                if (!code) return;

                if (rem) {
                    await cartRemove(code);
                    return;
                }

                const qtyEl = document.querySelector('.js-cart-qty[data-item-code=\"' + CSS.escape(code) + '\"]');
                const current = Number(qtyEl?.textContent || 0);
                const next = inc ? current + 1 : current - 1;
                await cartUpdate(code, Math.max(0, next));
            } catch (err) {
                const msg = err?.message || err?.error || (err?.status ? ('Request failed (HTTP ' + err.status + ')') : null) || 'Cart update failed';
                alert(msg);
            }
        });

        refreshCart().catch(() => {});
        (function() {
            var searchInput = document.getElementById('productSearch');
            var productItems = document.querySelectorAll('.product-item');
            var noResults = document.getElementById('productSearchNoResults');
            function filterProducts() {
                var q = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
                var visible = 0;
                productItems.forEach(function(el) {
                    var show = !q ||
                        (el.getAttribute('data-item-code') || '').indexOf(q) !== -1 ||
                        (el.getAttribute('data-name') || '').indexOf(q) !== -1 ||
                        (el.getAttribute('data-pack-size') || '').indexOf(q) !== -1 ||
                        (el.getAttribute('data-display-name') || '').indexOf(q) !== -1;
                    el.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                if (noResults) noResults.style.display = (productItems.length > 0 && visible === 0) ? 'block' : 'none';
            }
            if (searchInput) { searchInput.addEventListener('input', filterProducts); searchInput.addEventListener('keyup', filterProducts); }
            filterProducts();
        })();
    </script>
</body>
</html>
