<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        .app-header .logo-horizontal { display: none !important; }
        .app-header .logo-horizontal img { max-height: 52px; max-width: 200px; width: auto; height: auto; object-fit: contain; display: block !important; visibility: visible !important; }
        .app-sidebar .side-header .header-brand-img { max-height: 58px; max-width: 100%; display: block !important; visibility: visible !important; background-color: #fff !important; }
    </style>
</head>
<body class="app sidebar-mini ltr">
    <div id="global-loader">
        <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>

    <div class="page">
        <div class="page-main">
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex">
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                        <a class="logo-horizontal" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                        </a>
                        <div class="main-header-center ms-3 d-none d-lg-block">
                            <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                        </div>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4" aria-controls="navbarSupportedContent-4" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                                    <div class="d-flex order-lg-2">
                                        <a class="nav-link icon text-center" href="{{ url('/') }}">
                                            <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge">{{ $cartCount }}</span>
                                        </a>
                                        <div class="dropdown d-flex profile-1">
                                            <a href="javascript:void(0)" data-bs-toggle="dropdown" class="nav-link leading-none d-flex">
                                                <span class="avatar profile-user brround cover-image bg-primary text-white d-flex align-items-center justify-content-center">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                                <div class="drop-heading">
                                                    <div class="text-center">
                                                        <h5 class="text-dark mb-0 fs-14 fw-semibold">{{ auth()->user()->name }}</h5>
                                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                                    </div>
                                                </div>
                                                <div class="dropdown-divider m-0"></div>
                                                <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                                <a class="dropdown-item" href="{{ route('wallet.index') }}"><i class="dropdown-icon fe fe-dollar-sign"></i> Wallet</a>
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center')
                                                @php
                                                    $coRole = auth()->user()->role?->name;
                                                    $coAdminLabel = match($coRole) {
                                                        'reseller' => 'Reseller',
                                                        'accountant' => 'Accountant Panel',
                                                        'dispatch' => 'Dispatch Panel',
                                                        'headquarters' => 'Admin Dashboard',
                                                        'branch' => 'Branch Admin',
                                                        'service_center' => 'Service Center Admin',
                                                        'annex' => 'Annex Admin',
                                                        default => 'Admin',
                                                    };
                                                @endphp
                                                <a class="dropdown-item" href="{{ in_array($coRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ $coAdminLabel }}</a>
                                                @endif
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><i class="dropdown-icon fe fe-log-out"></i> Sign out</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky">
                <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
                <div class="app-sidebar">
                    <div class="side-header">
                        <a class="header-brand1" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                        </a>
                    </div>
                    <div class="main-sidemenu">
                        <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"/></svg></div>
                        <ul class="side-menu">
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ url('/') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('checkout.show') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Checkout</span></a>
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
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center')
                            @php
                                $coSideRole = auth()->user()->role?->name;
                                $coSideAdminLabel = match($coSideRole) {
                                    'reseller' => 'Reseller',
                                    'accountant' => 'Accountant Panel',
                                    'dispatch' => 'Dispatch Panel',
                                    'headquarters' => 'Admin Dashboard',
                                    'branch' => 'Branch Admin',
                                    'service_center' => 'Service Center Admin',
                                    'annex' => 'Annex Admin',
                                    default => 'Admin',
                                };
                            @endphp
                            <li class="slide">
                                <a class="side-menu__item" href="{{ in_array($coSideRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $coSideAdminLabel }}</span></a>
                            </li>
                            @endif
                            <li class="sub-category"><h3>Account</h3></li>
                            <li class="slide">
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
                                </form>
                            </li>
                        </ul>
                        <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"/></svg></div>
                    </div>
                </div>
            </div>

            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        <div class="page-header">
                            <h1 class="page-title">Checkout</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Shop</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                                </ol>
                            </div>
                        </div>

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-lg-8">
                                <form action="{{ route('checkout.place') }}" method="POST" id="checkout-form">
                                    @csrf
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fe fe-user me-2"></i>KD NO & Customer Name <span class="text-muted small fw-normal">(optional – add later if you don't have them)</span></h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">KD NO</label>
                                                    <div class="input-group">
                                                        <input type="text" name="kd_id" id="checkout_kd_id" class="form-control @error('kd_id') is-invalid @enderror" value="{{ old('kd_id', $kdId ?? '') }}" placeholder="Enter your KD number">
                                                        <button type="button" class="btn btn-outline-secondary checkout-kd-auto-gen" title="Auto-generate and save to your account"><i class="fe fe-zap me-1"></i>Auto Generate</button>
                                                    </div>
                                                    @error('kd_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Customer Name</label>
                                                    <input type="text" name="customer_name" id="checkout_customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name', $customerName ?? '') }}" placeholder="Enter customer name">
                                                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Service Center Referral Code</label>
                                                    <input type="text" name="sc_referral_code" id="checkout_sc_referral_code" class="form-control @error('sc_referral_code') is-invalid @enderror" value="{{ old('sc_referral_code') }}" placeholder="Enter Service Center code">
                                                    <div id="sc_referral_feedback" class="mt-1 small"></div>
                                                    @error('sc_referral_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                @if(auth()->user()->role?->name === 'distributor')
                                                <div class="col-md-12">
                                                    <label class="form-label">Service Center Code for Collection <span class="text-danger">*</span></label>
                                                    <input type="text" name="sc_collection_code" id="checkout_sc_collection_code" class="form-control @error('sc_collection_code') is-invalid @enderror" value="{{ old('sc_collection_code') }}" placeholder="Enter Service Center code for collection">
                                                    <div id="sc_collection_feedback" class="mt-1 small"></div>
                                                    @error('sc_collection_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fe fe-map-pin me-2"></i>Delivery</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-4">
                                                <label class="form-label">Delivery type</label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="delivery_type" id="delivery_ship" value="ship" form="checkout-form" {{ old('delivery_type', 'ship') === 'ship' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="delivery_ship"><i class="fe fe-truck me-1"></i> Ship (Delivery)</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="delivery_type" id="delivery_walk_in" value="walk_in" form="checkout-form" {{ old('delivery_type') === 'walk_in' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="delivery_walk_in"><i class="fe fe-user me-1"></i> Walk-in (Pick up)</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="shipping-address-fields">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label">Street address <span class="text-danger">*</span></label>
                                                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="House number, street, area">{{ old('shipping_address') }}</textarea>
                                                        @error('shipping_address')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">City <span class="text-danger">*</span></label>
                                                        <input type="text" name="shipping_city" class="form-control" value="{{ old('shipping_city') }}" placeholder="City">
                                                        @error('shipping_city')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">State / Region</label>
                                                        <input type="text" name="shipping_state" class="form-control" value="{{ old('shipping_state') }}" placeholder="State or region">
                                                        @error('shipping_state')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Postal code</label>
                                                        <input type="text" name="shipping_postal_code" class="form-control" value="{{ old('shipping_postal_code') }}" placeholder="Postal / ZIP code">
                                                        @error('shipping_postal_code')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                                                        <input type="text" name="shipping_phone" class="form-control" value="{{ old('shipping_phone', auth()->user()->phone) }}" placeholder="Contact phone for delivery">
                                                        @error('shipping_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fe fe-align-left me-2"></i>Order Note</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Note (optional)</label>
                                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Add any special instructions or notes for this order">{{ old('notes') }}</textarea>
                                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Order Summary</h3>
                                        <a href="{{ url('/') }}" class="btn btn-sm btn-outline-primary">Edit Cart</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">PV</th>
                                                        <th class="text-center">BV</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Price</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cartItems as $item)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $item->product->item_code }}</strong><br>
                                                            <small class="text-muted">{{ $item->product->name }}</small>
                                                        </td>
                                                        <td class="text-center">{{ $item->product->pv }}</td>
                                                        <td class="text-center">{{ $item->product->bv }}</td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td class="text-end">₦{{ number_format($item->unit_price, 0) }}</td>
                                                        <td class="text-end">₦{{ number_format($item->line_total, 0) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fe fe-tag me-2"></i>Coupon Code</h3>
                                    </div>
                                    <div class="card-body">
                                        @if(session('coupon_code'))
                                            <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                                                <span>Applied: <strong>{{ session('coupon_code') }}</strong> ({{ number_format($coupon->discount_percentage, 0) }}% off)</span>
                                                <form action="{{ route('cart.remove-coupon') }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            </div>
                                        @else
                                            <form action="{{ route('cart.apply-coupon') }}" method="POST">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="text" name="code" class="form-control" placeholder="Enter coupon code" required>
                                                    <button type="submit" class="btn btn-outline-primary">Apply</button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Payment</h3>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="p-3 bg-light rounded mb-3">
                                            <p class="mb-1 text-muted">Subtotal</p>
                                            <h4 class="mb-0">₦{{ number_format($cartSubtotal, 0) }}</h4>
                                        </div>
                                        @if($discountAmount > 0)
                                        <div class="p-3 bg-light rounded mb-3 border border-success">
                                            <p class="mb-1 text-success">Discount ({{ number_format($coupon->discount_percentage, 0) }}%)</p>
                                            <h4 class="mb-0 text-success">- ₦{{ number_format($discountAmount, 0) }}</h4>
                                        </div>
                                        @endif
                                        <div class="p-3 bg-primary text-white rounded mb-4">
                                            <p class="mb-1 opacity-75">Order Total</p>
                                            <h2 class="mb-0 fw-bold">₦{{ number_format($cartTotal, 0) }}</h2>
                                        </div>

                                        <div class="mb-4 text-start">
                                            <p class="mb-2"><i class="fe fe-wallet me-2"></i> <strong>Wallet balance:</strong> ₦{{ number_format($walletBalance, 0) }}</p>
                                            <p class="mb-2"><i class="fe fe-award me-2"></i> <strong>DPBV balance:</strong> {{ number_format($totalDpbv ?? 0, 2) }} DPBV = ₦{{ number_format($dpbvNairaEquivalent ?? 0, 2) }}</p>
                                            @if($kdId && $kdCreditBalance > 0)
                                            <p class="mb-0" id="kd_credit_balance_display"><i class="fe fe-credit-card me-2"></i> <strong>KD Credit balance:</strong> ₦{{ number_format($kdCreditBalance, 2) }}</p>
                                            @endif
                                        </div>

                                            <div class="mb-3">
                                                <label class="form-label">Payment method</label>
                                            @if($kdId && $kdCreditBalance > 0)
                                            <div class="form-check" id="pay_kd_credit_option">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_kd_credit" value="kd_credit" form="checkout-form" {{ $canPayWithCredit ? '' : 'disabled' }}>
                                                    <label class="form-check-label" for="pay_kd_credit">
                                                        Pay with KD Credit (₦{{ number_format($kdCreditBalance, 2) }} available)
                                                        @if(!$canPayWithCredit)
                                                            <span class="text-muted">(insufficient balance)</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                @endif
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_wallet" value="wallet" form="checkout-form" {{ $canPayWithWallet ? '' : 'disabled' }}>
                                                    <label class="form-check-label" for="pay_wallet">
                                                        Pay with Wallet
                                                        @if(!$canPayWithWallet)
                                                            <span class="text-muted">(insufficient balance – <a href="{{ route('wallet.index') }}">top up</a>)</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_dpbv" value="dpbv" form="checkout-form" {{ ($canPayWithDpbv ?? false) ? '' : 'disabled' }}>
                                                    <label class="form-check-label" for="pay_dpbv">
                                                        Pay with DPBV (₦{{ number_format($dpbvNairaEquivalent ?? 0, 2) }} available)
                                                        @if(!($canPayWithDpbv ?? false))
                                                            <span class="text-muted">(insufficient balance)</span>
                                                        @elseif(!($dpbvProductsAllowed ?? true))
                                                            <span class="text-muted">(some items not eligible for DPBV)</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_delivery" value="pay_on_delivery" form="checkout-form" checked>
                                                    <label class="form-check-label" for="pay_delivery">Pay on Delivery</label>
                                                </div>
                                            </div>
                                            <button type="submit" form="checkout-form" class="btn btn-primary btn-lg w-100 mb-2"><i class="fe fe-check-circle me-2"></i>Place Order</button>
                                            <button type="submit" form="checkout-form" formaction="{{ route('checkout.save-draft') }}" formmethod="POST" class="btn btn-outline-secondary w-100" formnovalidate><i class="fe fe-save me-2"></i>Save to Draft</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        (function() {
            var ship = document.getElementById('delivery_ship');
            var walkIn = document.getElementById('delivery_walk_in');
            var fields = document.getElementById('shipping-address-fields');
            function toggle() {
                var isShip = ship && ship.checked;
                fields.style.display = isShip ? '' : 'none';
                [].slice.call(document.querySelectorAll('#shipping-address-fields [name]')).forEach(function(el) {
                    el.required = isShip;
                });
            }
            if (ship) ship.addEventListener('change', toggle);
            if (walkIn) walkIn.addEventListener('change', toggle);
            toggle();
        })();
        (function() {
            var btn = document.querySelector('.checkout-kd-auto-gen');
            if (!btn) return;
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            btn.addEventListener('click', async function() {
                btn.disabled = true;
                btn.innerHTML = '<i class="fe fe-loader me-1"></i>Generating...';
                try {
                    var res = await fetch('{{ route("kd-info.auto-generate") }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: '_token=' + encodeURIComponent(csrf)
                    });
                    var data = await res.json().catch(function() { return {}; });
                    if (data.kd_id || data.customer_name) {
                        var kdIn = document.getElementById('checkout_kd_id');
                        var nameIn = document.getElementById('checkout_customer_name');
                        if (kdIn) kdIn.value = data.kd_id || '';
                        if (nameIn) nameIn.value = data.customer_name || '';
                        // Check credit balance after auto-generating KD NO
                        if (kdIn && kdIn.value) {
                            checkKdCreditBalance(kdIn.value);
                        }
                    }
                    if (data.error) alert(data.error);
                } catch (e) { alert('Failed to generate.'); }
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-zap me-1"></i>Auto Generate';
            });
        })();
        
        // Check KD Credit Balance when KD NO is entered
        (function() {
            var kdInput = document.getElementById('checkout_kd_id');
            if (!kdInput) return;
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var checkTimeout;
            
            kdInput.addEventListener('input', function() {
                clearTimeout(checkTimeout);
                var kdNo = this.value.trim();
                if (kdNo.length >= 3) {
                    checkTimeout = setTimeout(function() {
                        checkKdCreditBalance(kdNo);
                    }, 500);
                } else {
                    // Hide credit option if KD NO is cleared
                    hideKdCreditOption();
                }
            });
            
            function checkKdCreditBalance(kdNo) {
                fetch('{{ route("checkout.check-kd-credit") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ kd_no: kdNo })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.has_credit && data.balance > 0) {
                        showKdCreditOption(data.balance, data.can_pay);
                    } else {
                        hideKdCreditOption();
                    }
                })
                .catch(function() {
                    hideKdCreditOption();
                });
            }
            
            function showKdCreditOption(balance, canPay) {
                var creditOption = document.getElementById('pay_kd_credit_option');
                var creditInput = document.getElementById('pay_kd_credit');
                
                if (!creditOption) {
                    // Credit option doesn't exist, create it
                    // DPBV option removed; insert KD Credit inside the same payment-method container as Wallet.
                    var paymentMethods = document.getElementById('pay_wallet')?.closest('.mb-3') || document.querySelector('.mb-3');
                    if (paymentMethods) {
                        var creditHtml = '<div class="form-check" id="pay_kd_credit_option">' +
                            '<input class="form-check-input" type="radio" name="payment_method" id="pay_kd_credit" value="kd_credit" form="checkout-form"' + (canPay ? '' : ' disabled') + '>' +
                            '<label class="form-check-label" for="pay_kd_credit">' +
                            'Pay with KD Credit (₦' + parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' available)' +
                            (canPay ? '' : ' <span class="text-muted">(insufficient balance)</span>') +
                            '</label></div>';
                        paymentMethods.insertAdjacentHTML('afterbegin', creditHtml);
                    }
                } else {
                    // Credit option exists, update it
                    if (creditInput) {
                        creditInput.disabled = !canPay;
                        var creditLabel = creditInput.nextElementSibling;
                        if (creditLabel) {
                            creditLabel.innerHTML = 'Pay with KD Credit (₦' + parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' available)' +
                                (canPay ? '' : ' <span class="text-muted">(insufficient balance)</span>');
                        }
                    }
                }
                
                // Update credit balance display
                var balanceDisplay = document.getElementById('kd_credit_balance_display');
                if (!balanceDisplay) {
                    var balanceContainer = document.querySelector('.mb-3.p-3.bg-light.rounded');
                    if (balanceContainer) {
                        var balanceHtml = '<p class="mb-0" id="kd_credit_balance_display"><i class="fe fe-credit-card me-2"></i> <strong>KD Credit balance:</strong> ₦' + parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</p>';
                        balanceContainer.insertAdjacentHTML('beforeend', balanceHtml);
                    }
                } else {
                    balanceDisplay.innerHTML = '<i class="fe fe-credit-card me-2"></i> <strong>KD Credit balance:</strong> ₦' + parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }
            
            function hideKdCreditOption() {
                var creditOption = document.getElementById('pay_kd_credit_option');
                if (creditOption) {
                    creditOption.remove();
                }
                var balanceDisplay = document.getElementById('kd_credit_balance_display');
                if (balanceDisplay) {
                    balanceDisplay.remove();
                }
            }
            
            // Service Center code validation helper (for referral + distributor collection code)
            function wireScCodeValidation(inputId, feedbackId) {
                var scInput = document.getElementById(inputId);
                var scFeedback = document.getElementById(feedbackId);
                var scTimeout = null;
                if (!scInput || !scFeedback) return null;

                scInput.addEventListener('input', function() {
                    var code = scInput.value.trim();
                    scFeedback.innerHTML = '';
                    scInput.classList.remove('is-valid', 'is-invalid');

                    if (scTimeout) clearTimeout(scTimeout);
                    if (code.length === 0) return;

                    scTimeout = setTimeout(function() {
                        scFeedback.innerHTML = '<span class="text-muted"><i class="fe fe-refresh-cw fe-spin me-1"></i>Checking...</span>';
                        fetch('{{ route("checkout.validate-sc-code") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ code: code })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.valid) {
                                scInput.classList.add('is-valid');
                                scFeedback.innerHTML = '<span class="text-success"><i class="fe fe-check-circle me-1"></i>Valid Service Center: <strong>' + data.name + '</strong></span>';
                            } else {
                                scInput.classList.add('is-invalid');
                                scFeedback.innerHTML = '<span class="text-danger"><i class="fe fe-x-circle me-1"></i>' + data.message + '</span>';
                            }
                        })
                        .catch(function() {
                            scFeedback.innerHTML = '<span class="text-warning">Error checking code.</span>';
                        });
                    }, 500);
                });

                return scInput;
            }

            var scReferralInput = wireScCodeValidation('checkout_sc_referral_code', 'sc_referral_feedback');
            var scCollectionInput = wireScCodeValidation('checkout_sc_collection_code', 'sc_collection_feedback');

            function toggleDistributorScRequired() {
                if (!scCollectionInput) return;
                var payWallet = document.getElementById('pay_wallet');
                var isWallet = payWallet ? !!payWallet.checked : false;
                scCollectionInput.required = isWallet;
            }

            var payWallet = document.getElementById('pay_wallet');
            var payDpbv = document.getElementById('pay_dpbv');
            var payDelivery = document.getElementById('pay_delivery');
            var payKdCredit = document.getElementById('pay_kd_credit');
            if (payWallet) payWallet.addEventListener('change', toggleDistributorScRequired);
            if (payDpbv) payDpbv.addEventListener('change', toggleDistributorScRequired);
            if (payDelivery) payDelivery.addEventListener('change', toggleDistributorScRequired);
            if (payKdCredit) payKdCredit.addEventListener('change', toggleDistributorScRequired);
            toggleDistributorScRequired();

            // Check on page load if KD NO is already filled (but only if credit option doesn't already exist from server-side)
            var existingCreditOption = document.getElementById('pay_kd_credit_option');
            if (kdInput.value.trim() && !existingCreditOption) {
                checkKdCreditBalance(kdInput.value.trim());
            }

            // Check SC referral code on page load if existing
            if (scReferralInput && scReferralInput.value.trim()) scReferralInput.dispatchEvent(new Event('input'));
            if (scCollectionInput && scCollectionInput.value.trim()) scCollectionInput.dispatchEvent(new Event('input'));
        })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
