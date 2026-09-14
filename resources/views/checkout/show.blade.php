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
    <div class="page">
        <div class="page-main">
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex">
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                        <a class="logo-horizontal" href="{{ route('shop') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                        </a>
                        <div class="main-header-center ms-3 d-none d-lg-block">
                            <a href="{{ route('shop') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                        </div>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4" aria-controls="navbarSupportedContent-4" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                                    <div class="d-flex order-lg-2">
                                        <a class="nav-link icon text-center" href="{{ route('shop') }}">
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
                                <a class="side-menu__item" href="{{ route('shop') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('checkout.show') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Checkout</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('collection-centers.index') }}"><i class="side-menu__icon fe fe-map-pin"></i><span class="side-menu__label">Collection Center</span></a>
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
                                    <div style="display:none;">
                                        <input type="hidden" name="payment_method" id="payment_method_hidden" value="{{ old('payment_method', 'pay_on_delivery') }}">
                                        <input type="checkbox" id="split-payment-toggle" name="split_payment" value="1" {{ old('split_payment') ? 'checked' : '' }}>
                                        <input type="number" step="0.01" min="0" name="pos_amount_paid" id="pos_amount_paid_hidden" value="{{ old('pos_amount_paid') }}">
                                        <input type="number" step="0.01" min="0" name="bank_amount_paid" id="bank_amount_paid_hidden" value="{{ old('bank_amount_paid') }}">
                                        <input type="hidden" name="pos_machine_id" id="pos_machine_id_hidden" value="{{ old('pos_machine_id') }}">
                                        <input type="hidden" name="bank_account_id" id="bank_account_id_hidden" value="{{ old('bank_account_id') }}">
                                        <input type="number" step="0.01" min="0" name="split_wallet_amount" value="{{ old('split_wallet_amount', 0) }}">
                                        <input type="number" step="0.01" min="0" name="split_kd_credit_amount" value="{{ old('split_kd_credit_amount', 0) }}">
                                        <input type="number" step="0.01" min="0" name="split_cash_amount" value="{{ old('split_cash_amount', 0) }}">
                                        <input type="number" step="0.01" min="0" name="split_cheque_amount" value="{{ old('split_cheque_amount', 0) }}">
                                        <input type="number" step="0.01" min="0" name="split_dpbv_amount" value="{{ old('split_dpbv_amount', 0) }}">
                                        <input type="hidden" name="collection_branch_id" value="{{ old('collection_branch_id', $collectionBranch->id ?? '') }}">
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
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <h3 class="card-title mb-0">Payment</h3>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('collection-centers.index') }}" class="btn btn-outline-info btn-sm">Collection Center</a>
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">Payment</button>
                                        </div>
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

                                        @if($collectionBranch)
                                        <div class="alert alert-info text-start py-2">
                                            Collect at <strong>{{ $collectionBranch->name }}</strong>.
                                            Stock is removed from that branch when they mark it collected.
                                            <form method="POST" action="{{ route('collection-centers.clear') }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-link btn-sm p-0">Clear</button>
                                            </form>
                                        </div>
                                        @endif
                                        <div class="mb-3 text-start">
                                            <p class="mb-2"><i class="fe fe-wallet me-2"></i> <strong>Wallet balance:</strong> ₦{{ number_format($walletBalance, 2) }}</p>
                                            <p class="mb-2"><i class="fe fe-award me-2"></i> <strong>DPBV balance:</strong> {{ number_format($totalDpbv ?? 0, 2) }} DPBV = ₦{{ number_format($dpbvNairaEquivalent ?? 0, 2) }}</p>
                                            <p class="mb-2" id="kd_credit_balance_display" style="{{ ($kdId && $kdCreditBalance > 0) ? '' : 'display:none;' }}"><i class="fe fe-credit-card me-2"></i> <strong>KD Credit balance:</strong> ₦<span id="kd-credit-balance-text">{{ number_format($kdCreditBalance, 2) }}</span></p>
                                            <div class="small text-muted">
                                                Method: <span id="payment-summary-method">{{ old('split_payment') ? 'Split' : 'Pay on Delivery' }}</span>
                                                <span class="mx-1">•</span>
                                                POS: ₦<span id="payment-summary-pos">{{ number_format((float) old('pos_amount_paid', 0), 2) }}</span>
                                                <span class="mx-1">•</span>
                                                Split: <span id="payment-summary-split">{{ old('split_payment') ? 'Yes' : 'No' }}</span>
                                            </div>
                                            @error('payment_method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('split_payment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('split_wallet_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('split_kd_credit_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('split_dpbv_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="text-start text-muted fw-semibold mb-2" style="font-size: 15px;">
                                            DPBV Available: <strong><span id="split-dpbv-total">{{ number_format($totalDpbv ?? 0, 2) }}</span> DPBV</strong> (₦<span id="split-dpbv-naira">{{ number_format($dpbvNairaEquivalent ?? 0, 2) }}</span>)
                                        </div>
                                        <div class="text-start text-muted fw-semibold mb-4" style="font-size: 15px;">
                                            Total due: ₦<span id="split-total-due">{{ number_format($cartTotal, 2) }}</span> • Split total: ₦<span id="split-total-entered">0.00</span> • Remaining: ₦<span id="split-remaining">{{ number_format($cartTotal, 2) }}</span>
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

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-muted fw-semibold" style="font-size: 15px;">
                                DPBV Available: <strong><span id="split-dpbv-total-modal">{{ number_format($totalDpbv ?? 0, 2) }}</span> DPBV</strong> (₦<span id="split-dpbv-naira-modal">{{ number_format($dpbvNairaEquivalent ?? 0, 2) }}</span>)
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted fw-semibold" style="font-size: 15px;">
                                Total due: ₦<span id="split-total-due-modal">{{ number_format($cartTotal, 2) }}</span> • Split total: ₦<span id="split-total-entered-modal">0.00</span> • Remaining: ₦<span id="split-remaining-modal">{{ number_format($cartTotal, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Coupon Code</label>
                            @if(session('coupon_code'))
                                <div class="alert alert-success py-2 mb-0">Applied: <strong>{{ session('coupon_code') }}</strong></div>
                            @else
                                <form action="{{ route('cart.apply-coupon') }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    <input type="text" name="code" class="form-control" placeholder="Enter coupon code (optional)">
                                    <button type="submit" class="btn btn-outline-primary">Apply</button>
                                </form>
                            @endif
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="split_payment_modal" {{ old('split_payment') ? 'checked' : '' }}>
                                <label class="form-check-label" for="split_payment_modal">Split payment (Wallet + Credit + DPBV + Cash + Cheque + POS + Bank)</label>
                            </div>
                        </div>
                        <div class="col-12" id="split_fields_modal" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-4" id="split_wallet_amount_modal_wrap" style="{{ $walletBalance > 0 ? '' : 'display:none;' }}">
                                    <div class="text-muted fw-semibold mb-1" style="font-size: 14px;">Balance: ₦<span id="split-wallet-available-modal">{{ number_format($walletBalance, 2) }}</span></div>
                                    <label class="form-label">Wallet Amount</label>
                                    <input type="text" inputmode="decimal" id="split_wallet_amount_modal" class="form-control" value="{{ old('split_wallet_amount') }}" placeholder="0.00">
                                </div>
                                <div class="col-md-4" id="split_kd_credit_amount_modal_wrap" style="{{ $kdCreditBalance > 0 ? '' : 'display:none;' }}">
                                    <div class="text-muted fw-semibold mb-1" style="font-size: 14px;">Balance: ₦<span id="split-kd-credit-available-modal">{{ number_format($kdCreditBalance, 2) }}</span></div>
                                    <label class="form-label">Credit Amount</label>
                                    <input type="text" inputmode="decimal" id="split_kd_credit_amount_modal" class="form-control" value="{{ old('split_kd_credit_amount') }}" placeholder="0.00">
                                </div>
                                <div class="col-md-4" id="split_dpbv_amount_modal_wrap" style="{{ ($dpbvNairaEquivalent ?? 0) > 0 ? '' : 'display:none;' }}">
                                    <div class="text-muted fw-semibold mb-1" style="font-size: 14px;">Balance: ₦<span id="split-dpbv-available-modal">{{ number_format($dpbvNairaEquivalent ?? 0, 2) }}</span></div>
                                    <label class="form-label">DPBV Amount</label>
                                    <input type="text" inputmode="decimal" id="split_dpbv_amount_modal" class="form-control" value="{{ old('split_dpbv_amount') }}" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Cash Amount</label>
                                    <input type="text" inputmode="decimal" id="split_cash_amount_modal" class="form-control" value="{{ old('split_cash_amount') }}" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Cheque Amount</label>
                                    <input type="text" inputmode="decimal" id="split_cheque_amount_modal" class="form-control" value="{{ old('split_cheque_amount') }}" placeholder="0.00">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label mb-1">POS Machine</label>
                                    <select id="pos_machine_modal" class="form-select">
                                        <option value="">Select POS machine (optional)</option>
                                        @foreach(($posMachines ?? collect()) as $m)
                                            <option value="{{ $m->id }}" data-bank="{{ $m->bank_name }}" data-account-name="{{ $m->account_name }}" data-account-number="{{ $m->account_number }}" {{ (string) old('pos_machine_id') === (string) $m->id ? 'selected' : '' }}>
                                                {{ $m->bank_name ?: 'POS' }}{{ $m->account_number ? ' • '.$m->account_number : '' }}{{ $m->account_name ? ' • '.$m->account_name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="pos_machine_modal_note" class="small text-muted mt-1"></div>
                                    <div id="pos_amount_paid_modal_wrap" class="mt-2" style="display:none;">
                                        <input type="text" inputmode="decimal" id="pos_amount_paid_modal" class="form-control" value="{{ old('pos_amount_paid') }}" placeholder="Enter amount (e.g. 1,000,000)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Bank Account</label>
                                    <select id="bank_account_modal" class="form-select">
                                        <option value="">Select bank (optional)</option>
                                        @foreach(($banks ?? collect()) as $b)
                                            <option value="{{ $b->id }}" data-bank="{{ $b->name }}" data-account-name="{{ $b->account_name }}" data-account-number="{{ $b->account_number }}" {{ (string) old('bank_account_id') === (string) $b->id ? 'selected' : '' }}>
                                                {{ $b->name ?: 'Bank' }}{{ $b->account_number ? ' • '.$b->account_number : '' }}{{ $b->account_name ? ' • '.$b->account_name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="bank_account_modal_note" class="small text-muted mt-1"></div>
                                    <div id="bank_amount_paid_modal_wrap" class="mt-2" style="display:none;">
                                        <input type="text" inputmode="decimal" id="bank_amount_paid_modal" class="form-control" value="{{ old('bank_amount_paid') }}" placeholder="Enter amount (e.g. 1,000,000)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="payment_apply_btn">Apply</button>
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
            
            function moneyText(balance) {
                return parseFloat(balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            function showKdCreditOption(balance) {
                var text = document.getElementById('kd-credit-balance-text');
                var display = document.getElementById('kd_credit_balance_display');
                var modalAvail = document.getElementById('split-kd-credit-available-modal');
                var wrap = document.getElementById('split_kd_credit_amount_modal_wrap');
                if (text) text.textContent = moneyText(balance);
                if (display) display.style.display = '';
                if (modalAvail) modalAvail.textContent = moneyText(balance);
                if (wrap) wrap.style.display = parseFloat(balance) > 0 ? '' : 'none';
            }
            
            function hideKdCreditOption() {
                var display = document.getElementById('kd_credit_balance_display');
                var modalAvail = document.getElementById('split-kd-credit-available-modal');
                var wrap = document.getElementById('split_kd_credit_amount_modal_wrap');
                var modalInput = document.getElementById('split_kd_credit_amount_modal');
                if (display) display.style.display = 'none';
                if (modalAvail) modalAvail.textContent = '0.00';
                if (wrap) wrap.style.display = 'none';
                if (modalInput) modalInput.value = '';
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

            if (scCollectionInput) scCollectionInput.required = true;

            if (kdInput.value.trim()) {
                checkKdCreditBalance(kdInput.value.trim());
            }

            // Check SC referral code on page load if existing
            if (scReferralInput && scReferralInput.value.trim()) scReferralInput.dispatchEvent(new Event('input'));
            if (scCollectionInput && scCollectionInput.value.trim()) scCollectionInput.dispatchEvent(new Event('input'));
        })();

        (function () {
            var due = {{ json_encode((float) $cartTotal) }};
            var hiddenMethod = document.getElementById('payment_method_hidden');
            var hiddenPos = document.getElementById('pos_amount_paid_hidden');
            var hiddenBankAmt = document.getElementById('bank_amount_paid_hidden');
            var hiddenPosMachine = document.getElementById('pos_machine_id_hidden');
            var hiddenBankAccount = document.getElementById('bank_account_id_hidden');
            var hiddenSplit = document.getElementById('split-payment-toggle');
            var hiddenWallet = document.querySelector('input[name="split_wallet_amount"]');
            var hiddenKd = document.querySelector('input[name="split_kd_credit_amount"]');
            var hiddenCash = document.querySelector('input[name="split_cash_amount"]');
            var hiddenCheque = document.querySelector('input[name="split_cheque_amount"]');
            var hiddenDpbv = document.querySelector('input[name="split_dpbv_amount"]');
            var modalSplit = document.getElementById('split_payment_modal');
            var modalSplitWrap = document.getElementById('split_fields_modal');
            var modalWallet = document.getElementById('split_wallet_amount_modal');
            var modalKd = document.getElementById('split_kd_credit_amount_modal');
            var modalCash = document.getElementById('split_cash_amount_modal');
            var modalCheque = document.getElementById('split_cheque_amount_modal');
            var modalDpbv = document.getElementById('split_dpbv_amount_modal');
            var modalPos = document.getElementById('pos_amount_paid_modal');
            var modalBankAmount = document.getElementById('bank_amount_paid_modal');
            var modalPosMachine = document.getElementById('pos_machine_modal');
            var modalBankAccount = document.getElementById('bank_account_modal');
            var applyBtn = document.getElementById('payment_apply_btn');
            if (!hiddenMethod || !modalSplit || !applyBtn) return;

            function parseMoney(str) {
                var cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                var firstDot = cleaned.indexOf('.');
                if (firstDot !== -1) {
                    cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                }
                var num = Number(cleaned);
                return Number.isFinite(num) ? num : 0;
            }
            function fmt(num) {
                return (parseFloat(num || 0) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            function updateTotals() {
                var sum = parseMoney(modalWallet && modalWallet.value) + parseMoney(modalKd && modalKd.value) + parseMoney(modalCash && modalCash.value) + parseMoney(modalCheque && modalCheque.value) + parseMoney(modalDpbv && modalDpbv.value) + parseMoney(modalPos && modalPos.value) + parseMoney(modalBankAmount && modalBankAmount.value);
                var rem = due - sum;
                ['split-total-due', 'split-total-due-modal'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = fmt(due);
                });
                ['split-total-entered', 'split-total-entered-modal'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = fmt(sum);
                });
                ['split-remaining', 'split-remaining-modal'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = fmt(rem);
                });
                return { sum: sum, rem: rem };
            }
            function accountNote(select, noteEl, fallback) {
                if (!select || !noteEl) return;
                var opt = select.options[select.selectedIndex];
                if (!select.value) {
                    noteEl.textContent = '';
                    return;
                }
                noteEl.textContent = (opt.getAttribute('data-bank') || fallback) + (opt.getAttribute('data-account-number') ? ' • ' + opt.getAttribute('data-account-number') : '') + (opt.getAttribute('data-account-name') ? ' • ' + opt.getAttribute('data-account-name') : '');
            }
            function toggleMachineAmount(select, wrap, input) {
                if (!wrap) return;
                var on = !!(select && select.value);
                wrap.style.display = on ? '' : 'none';
                if (!on && input) input.value = '';
            }
            function applySplitVisibility() {
                if (modalSplitWrap) modalSplitWrap.style.display = modalSplit.checked ? '' : 'none';
                toggleMachineAmount(modalPosMachine, document.getElementById('pos_amount_paid_modal_wrap'), modalPos);
                toggleMachineAmount(modalBankAccount, document.getElementById('bank_amount_paid_modal_wrap'), modalBankAmount);
                accountNote(modalPosMachine, document.getElementById('pos_machine_modal_note'), 'POS');
                accountNote(modalBankAccount, document.getElementById('bank_account_modal_note'), 'Bank');
                updateTotals();
            }
            function syncFromHidden() {
                modalSplit.checked = !!hiddenSplit.checked;
                if (modalWallet && hiddenWallet) modalWallet.value = parseFloat(hiddenWallet.value || 0) > 0 ? fmt(hiddenWallet.value) : '';
                if (modalKd && hiddenKd) modalKd.value = parseFloat(hiddenKd.value || 0) > 0 ? fmt(hiddenKd.value) : '';
                if (modalCash && hiddenCash) modalCash.value = parseFloat(hiddenCash.value || 0) > 0 ? fmt(hiddenCash.value) : '';
                if (modalCheque && hiddenCheque) modalCheque.value = parseFloat(hiddenCheque.value || 0) > 0 ? fmt(hiddenCheque.value) : '';
                if (modalDpbv && hiddenDpbv) modalDpbv.value = parseFloat(hiddenDpbv.value || 0) > 0 ? fmt(hiddenDpbv.value) : '';
                if (modalPos && hiddenPos) modalPos.value = hiddenPos.value ? fmt(hiddenPos.value) : '';
                if (modalBankAmount && hiddenBankAmt) modalBankAmount.value = hiddenBankAmt.value ? fmt(hiddenBankAmt.value) : '';
                if (modalPosMachine && hiddenPosMachine) modalPosMachine.value = hiddenPosMachine.value || '';
                if (modalBankAccount && hiddenBankAccount) modalBankAccount.value = hiddenBankAccount.value || '';
                applySplitVisibility();
            }
            function applyToHidden() {
                var totals = updateTotals();
                if (modalSplit.checked && Math.abs(totals.rem) > 0.009) {
                    alert('Payment amounts must add up to the order total. Remaining: ₦' + fmt(totals.rem));
                    return false;
                }
                hiddenSplit.checked = !!modalSplit.checked;
                hiddenMethod.value = modalSplit.checked ? 'split' : 'pay_on_delivery';
                if (hiddenWallet && modalWallet) hiddenWallet.value = parseMoney(modalWallet.value).toFixed(2);
                if (hiddenKd && modalKd) hiddenKd.value = parseMoney(modalKd.value).toFixed(2);
                if (hiddenCash && modalCash) hiddenCash.value = parseMoney(modalCash.value).toFixed(2);
                if (hiddenCheque && modalCheque) hiddenCheque.value = parseMoney(modalCheque.value).toFixed(2);
                if (hiddenDpbv && modalDpbv) hiddenDpbv.value = parseMoney(modalDpbv.value).toFixed(2);
                if (hiddenPos && modalPos) hiddenPos.value = modalPos.value.trim() === '' ? '' : parseMoney(modalPos.value).toFixed(2);
                if (hiddenBankAmt && modalBankAmount) hiddenBankAmt.value = modalBankAmount.value.trim() === '' ? '' : parseMoney(modalBankAmount.value).toFixed(2);
                if (hiddenPosMachine && modalPosMachine) hiddenPosMachine.value = modalPosMachine.value || '';
                if (hiddenBankAccount && modalBankAccount) hiddenBankAccount.value = modalBankAccount.value || '';
                var sumMethod = document.getElementById('payment-summary-method');
                var sumPos = document.getElementById('payment-summary-pos');
                var sumSplit = document.getElementById('payment-summary-split');
                if (sumMethod) sumMethod.textContent = hiddenMethod.value === 'split' ? 'Split' : 'Pay on Delivery';
                if (sumPos) sumPos.textContent = fmt(hiddenPos.value);
                if (sumSplit) sumSplit.textContent = hiddenSplit.checked ? 'Yes' : 'No';
                return true;
            }
            function wireMoney(el) {
                if (!el) return;
                el.addEventListener('input', function () {
                    el.value = String(el.value || '').replace(/[^0-9.,]/g, '');
                    updateTotals();
                });
                el.addEventListener('blur', function () {
                    if ((el.value || '').trim() === '') return;
                    el.value = fmt(parseMoney(el.value));
                    updateTotals();
                });
            }
            [modalWallet, modalKd, modalCash, modalCheque, modalDpbv, modalPos, modalBankAmount].forEach(wireMoney);
            modalSplit.addEventListener('change', applySplitVisibility);
            if (modalPosMachine) modalPosMachine.addEventListener('change', applySplitVisibility);
            if (modalBankAccount) modalBankAccount.addEventListener('change', applySplitVisibility);
            applyBtn.addEventListener('click', function () {
                if (!applyToHidden()) return;
                var modalEl = document.getElementById('paymentModal');
                if (modalEl && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
            });
            var paymentModalEl = document.getElementById('paymentModal');
            if (paymentModalEl) {
                paymentModalEl.addEventListener('shown.bs.modal', syncFromHidden);
            }
            var checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function (e) {
                    var submitter = e.submitter;
                    if (submitter && submitter.getAttribute('formaction')) return;
                    if (hiddenSplit.checked) {
                        syncFromHidden();
                        var totals = updateTotals();
                        if (Math.abs(totals.rem) > 0.009) {
                            e.preventDefault();
                            alert('Open Payment and make the amounts add up to the order total. Remaining: ₦' + fmt(totals.rem));
                        }
                    }
                });
            }
            syncFromHidden();
        })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
