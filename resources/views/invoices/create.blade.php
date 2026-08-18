<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Invoice – {{ config('app.name') }}</title>
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

        /* Floating totals (fixed like back-to-top button) */
        #invoice-floating-totals {
            position: fixed;
            top: 88px; /* below sticky header */
            right: 20px;
            z-index: 1040;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,.12);
            border-radius: .75rem;
            padding: .6rem .75rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            min-width: 220px;
        }
        #invoice-floating-totals .label { font-size: 11px; color: #6c757d; margin-bottom: 2px; }
        #invoice-floating-totals .value { font-weight: 700; }
        @media (max-width: 768px) {
            #invoice-floating-totals {
                left: 12px;
                right: 12px;
                top: 78px;
                min-width: 0;
            }
        }
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
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-invoices" aria-controls="navbarSupportedContent-invoices" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-invoices">
                                    <div class="d-flex order-lg-2">
                                        <div class="dropdown d-flex">
                                            <a class="nav-link icon theme-layout nav-link-bg layout-setting" href="javascript:void(0)">
                                                <span class="dark-layout"><i class="fe fe-moon"></i></span>
                                                <span class="light-layout"><i class="fe fe-sun"></i></span>
                                            </a>
                                        </div>
                                        <a class="nav-link icon text-center" href="{{ route('shop') }}">
                                            <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge">{{ $cartCount ?? 0 }}</span>
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
                                                <a class="dropdown-item" href="{{ route('orders.index') }}"><i class="dropdown-icon fe fe-package"></i> My Orders</a>
                                                <a class="dropdown-item" href="{{ route('invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> My Invoices</a>
                                                <a class="dropdown-item" href="{{ route('wallet.index') }}"><i class="dropdown-icon fe fe-dollar-sign"></i> Wallet</a>
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                                                @php
                                                    $createInvRole = auth()->user()->role?->name;
                                                    $createInvAdminLabel = match($createInvRole) {
                                                        'reseller' => 'Reseller',
                                                        'accountant' => 'Accountant Panel',
                                                        'dispatch' => 'Dispatch Panel',
                                                        'headquarters' => 'Admin Dashboard',
                                                        'branch' => 'Branch Admin',
                                                        'service_center' => 'Service Center Admin',
                                                        default => 'Admin',
                                                    };
                                                @endphp
                                                <a class="dropdown-item" href="{{ $createInvRole === 'headquarters' ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ $createInvAdminLabel }}</a>
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
                                <a class="side-menu__item" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                            @php
                                $createInvSideRole = auth()->user()->role?->name;
                                $createInvSideAdminLabel = match($createInvSideRole) {
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
                                <a class="side-menu__item" href="{{ in_array($createInvSideRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $createInvSideAdminLabel }}</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin.expenditures.index') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Expenditures</span></a>
                            </li>
                            @endif
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
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h1 class="page-title mb-0">Create Invoice</h1>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <div class="btn-group" role="group" aria-label="Invoice status (top)">
                                        @php($statusCounts = $statusCounts ?? [])
                                        <a href="{{ route('invoices.index', ['status' => 'draft']) }}" class="btn btn-outline-primary d-inline-flex align-items-center" title="Draft invoices">
                                            <span class="avatar avatar-xl bradius bg-secondary text-white fw-semibold" style="font-size:12px;">Draft</span>
                                            <span class="badge bg-secondary ms-2">{{ (int) ($statusCounts['draft'] ?? 0) }}</span>
                                        </a>
                                        <a href="{{ route('invoices.index', ['status' => 'sent']) }}" class="btn btn-outline-primary d-inline-flex align-items-center" title="Sent invoices">
                                            <span class="avatar avatar-xl bradius bg-warning text-white fw-semibold" style="font-size:12px;">Sent</span>
                                            <span class="badge bg-warning text-dark ms-2">{{ (int) ($statusCounts['sent'] ?? 0) }}</span>
                                        </a>
                                        <a href="{{ route('invoices.index', ['status' => 'paid']) }}" class="btn btn-outline-primary d-inline-flex align-items-center" title="Paid invoices">
                                            <span class="avatar avatar-xl bradius bg-success text-white fw-semibold" style="font-size:12px;">Paid</span>
                                            <span class="badge bg-success ms-2">{{ (int) ($statusCounts['paid'] ?? 0) }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h3 class="card-title mb-0">New Invoice</h3>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="javascript:void(0)" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#customerModal">
                                        Customer
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                        Payment
                                    </a>
                                    <button type="submit" form="invoice-form" class="btn btn-primary">
                                        Create Invoice
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
                                    @csrf

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                            @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                            <div class="btn-group w-100" role="group" aria-label="Invoice status">
                                                <input type="radio" class="btn-check" name="status" id="status_draft" value="draft" autocomplete="off" {{ old('status', 'draft') === 'draft' ? 'checked' : '' }} required>
                                                <label class="btn btn-outline-primary" for="status_draft">Draft</label>

                                                <input type="radio" class="btn-check" name="status" id="status_sent" value="sent" autocomplete="off" {{ old('status') === 'sent' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-primary" for="status_sent">Sent</label>

                                                <input type="radio" class="btn-check" name="status" id="status_paid" value="paid" autocomplete="off" {{ old('status') === 'paid' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-primary" for="status_paid">Paid</label>
                                            </div>
                                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <input type="hidden" name="use_product_quantities" value="{{ old('use_product_quantities', 0) ? 1 : 0 }}">
                                    <input type="hidden" name="use_sc_referral" id="use_sc_referral" value="{{ old('sc_referral_code') ? 1 : 0 }}">

                                    <div id="invoice-customer-fields">
                                        <div class="alert alert-info mb-4">
                                            Customer details are taken automatically from your account unless you enable Service Center Referral Code.
                                        </div>
                                    </div>

                                    <div id="invoice-sc-referral-fields" class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Service Center Referral Code</label>
                                            <input type="text" name="sc_referral_code" id="invoice_sc_referral_code" class="form-control @error('sc_referral_code') is-invalid @enderror" value="{{ old('sc_referral_code') }}" placeholder="Enter Service Center code">
                                            <div id="invoice_sc_referral_feedback" class="mt-1 small"></div>
                                            @error('sc_referral_code')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Buyer Name</label>
                                            <input type="text" name="buyer_name" class="form-control @error('buyer_name') is-invalid @enderror" value="{{ old('buyer_name') }}" placeholder="Enter buyer name">
                                            @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <div class="small text-muted">
                                                    Method: <span id="payment-summary-method">—</span>
                                                    <span class="mx-1">•</span>
                                                    POS: ₦<span id="payment-summary-pos">0.00</span>
                                                    <span class="mx-1">•</span>
                                                    Split: <span id="payment-summary-split">No</span>
                                                </div>
                                            </div>
                                            @error('payment_method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('pos_amount_paid')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            @error('split_payment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Coupon Code</label>
                                            <div class="input-group">
                                                <input
                                                    type="text"
                                                    name="coupon_code"
                                                    id="invoice_coupon_code"
                                                    class="form-control @error('coupon_code') is-invalid @enderror"
                                                    value="{{ old('coupon_code') }}"
                                                    placeholder="Enter coupon code (optional)"
                                                >
                                                <button type="button" class="btn btn-outline-primary" id="invoice_coupon_check_btn">Apply</button>
                                            </div>
                                            <div id="invoice_coupon_feedback" class="mt-1 small"></div>
                                            @error('coupon_code')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div style="display:none;">
                                        <select name="payment_method" id="payment_method_hidden">
                                            <option value="">Select payment method (optional)</option>
                                            <option value="wallet" {{ old('payment_method') === 'wallet' ? 'selected' : '' }}>Wallet</option>
                                            <option value="dpbv" {{ old('payment_method') === 'dpbv' ? 'selected' : '' }}>DPBV</option>
                                            <option value="kd_credit" {{ old('payment_method') === 'kd_credit' ? 'selected' : '' }}>KD Credit</option>
                                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                                            <option value="pay_on_delivery" {{ old('payment_method') === 'pay_on_delivery' ? 'selected' : '' }}>Pay on Delivery</option>
                                        </select>
                                        <input type="checkbox" id="split-payment-toggle" name="split_payment" value="1" {{ old('split_payment') ? 'checked' : '' }}>
                                        <input type="number" step="0.01" min="0" name="pos_amount_paid" id="pos_amount_paid_hidden" value="{{ old('pos_amount_paid') }}">
                                        <input type="number" step="0.01" min="0" name="bank_amount_paid" id="bank_amount_paid_hidden" value="{{ old('bank_amount_paid') }}">
                                    </div>

                                    <div id="split-payment-fields" class="row g-3 mb-4" style="display:none;">
                                        <div class="col-md-3">
                                            <div class="small text-muted mb-1">Balance: ₦<span id="split-wallet-available">0.00</span></div>
                                            <label class="form-label">Wallet Amount</label>
                                            <input type="number" step="0.01" min="0" name="split_wallet_amount" class="form-control @error('split_wallet_amount') is-invalid @enderror" value="{{ old('split_wallet_amount', 0) }}">
                                            @error('split_wallet_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted mb-1">Balance: ₦<span id="split-kd-credit-available">0.00</span></div>
                                            <label class="form-label">Credit Amount</label>
                                            <input type="number" step="0.01" min="0" name="split_kd_credit_amount" class="form-control @error('split_kd_credit_amount') is-invalid @enderror" value="{{ old('split_kd_credit_amount', 0) }}">
                                            @error('split_kd_credit_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cash Amount</label>
                                            <input type="number" step="0.01" min="0" name="split_cash_amount" class="form-control @error('split_cash_amount') is-invalid @enderror" value="{{ old('split_cash_amount', 0) }}">
                                            @error('split_cash_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted fw-semibold" style="font-size: 15px;">
                                                DPBV Available: <strong><span id="split-dpbv-total">0.00</span> DPBV</strong> (₦<span id="split-dpbv-naira">0.00</span>)
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted fw-semibold" style="font-size: 15px;">
                                                Total due: ₦<span id="split-total-due">0.00</span> • Split total: ₦<span id="split-total-entered">0.00</span> • Remaining: ₦<span id="split-remaining">0.00</span>
                                            </div>
                                            @error('split_payment')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div id="invoice-wallet-balance-wrap" class="mb-4" style="display:none;">
                                        <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
                                            <div>
                                                <div class="small text-muted">Wallet balance for</div>
                                                <div class="fw-semibold" id="invoice-wallet-owner-label">{{ $user->name }}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">Balance</div>
                                                <div class="fw-bold">₦<span id="invoice-wallet-balance">{{ number_format((float) ($user->wallet_balance ?? 0), 2) }}</span></div>
                                            </div>
                                        </div>
                                        <div id="invoice-wallet-balance-note" class="small text-muted mt-1"></div>
                                    </div>

                                    <div id="invoice-dpbv-balance-wrap" class="mb-4" style="display:none;">
                                        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0">
                                            <div>
                                                <div class="small text-muted">DPBV balance for</div>
                                                <div class="fw-semibold" id="invoice-dpbv-owner-label">Service Center</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">Available</div>
                                                <div class="fw-bold"><span id="invoice-dpbv-total">0.00</span> DPBV</div>
                                                <div class="small text-muted">₦<span id="invoice-dpbv-naira">0.00</span></div>
                                            </div>
                                        </div>
                                        <div id="invoice-dpbv-balance-note" class="small mt-1"></div>
                                    </div>

                                    <div id="invoice-kedi-credit-wrap" class="mb-4" style="display:none;">
                                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                                            <div>
                                                <div class="small text-muted">Kedi Credit balance for</div>
                                                <div class="fw-semibold" id="invoice-kedi-credit-owner-label">{{ $user->name }}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">Balance</div>
                                                <div class="fw-bold">₦<span id="invoice-kedi-credit-balance">{{ number_format((float) ($user->kedi_credit_balance ?? 0), 2) }}</span></div>
                                            </div>
                                        </div>
                                        <div id="invoice-kedi-credit-note" class="small mt-1"></div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="use-products-toggle" {{ old('use_product_quantities', 0) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="use-products-toggle">Use products from catalog</label>
                                        </div>
                                    </div>

                                    <div id="invoice-floating-totals" aria-live="polite">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="label mb-0">Subtotal</div>
                                                <div class="value">
                                                    ₦<span id="invoice-float-subtotal">0.00</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="label mb-0">Total</div>
                                                <div class="value">
                                                    ₦<span id="invoice-float-total">0.00</span>
                                                    <span class="text-muted fw-normal ms-1" style="font-size:11px;">
                                                        (<span id="invoice-float-pv">0.0</span> PV / <span id="invoice-float-bv">0.0</span> BV)
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <div id="products-section" style="{{ old('use_product_quantities', 0) ? '' : 'display:none;' }}">
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                            <label class="form-label mb-0">Products — enter quantity needed</label>
                                            <input type="search" id="product-search" class="form-control form-control-sm" placeholder="Search products..." style="max-width: 240px;" autocomplete="off">
                                        </div>
                                        <div class="table-responsive mb-2">
                                            <table class="table table-bordered" id="product-quantities-table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th style="width:80px">Unit</th>
                                                        <th style="width:120px" class="text-end">Unit Price</th>
                                                        <th style="width:120px">Quantity</th>
                                                        <th style="width:100px" class="text-end">PV</th>
                                                        <th style="width:100px" class="text-end">BV</th>
                                                        <th style="width:120px" class="text-end">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($products as $product): ?>
                                                        <?php
                                                            $unitPrice = $product->getPriceForUser($user);
                                                            $pv = (float) ($product->pv ?? 0);
                                                            $bv = (float) ($product->bv ?? 0);
                                                        ?>
                                                        <tr class="product-row" data-unit-price="{{ $unitPrice }}" data-pv="{{ $pv }}" data-bv="{{ $bv }}" data-search="{{ strtolower($product->name . ' ' . ($product->pack_size ?? '') . ' ' . ($product->item_code ?? '')) }}">
                                                            <td>{{ $product->display_name }}</td>
                                                            <td>{{ $product->pack_size ?? 'pcs' }}</td>
                                                            <td class="text-end">₦{{ number_format($unitPrice, 2) }}</td>
                                                            <td>
                                                                <input type="number" name="product_quantities[{{ $product->id }}]" class="form-control form-control-sm product-qty" value="{{ old('product_quantities.'.$product->id, $prefillQuantities[$product->id] ?? 0) }}" min="0" step="1" data-unit-price="{{ $unitPrice }}">
                                                            </td>
                                                            <td class="text-end">
                                                                <small class="text-muted d-block">Unit: {{ number_format($pv, 1) }}</small>
                                                                <span class="product-line-pv">0.0</span>
                                                            </td>
                                                            <td class="text-end">
                                                                <small class="text-muted d-block">Unit: {{ number_format($bv, 1) }}</small>
                                                                <span class="product-line-bv">0.0</span>
                                                            </td>
                                                            <td class="text-end"><span class="product-line-total">0.00</span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                                        <td class="text-end"><strong id="product-subtotal-pv">0</strong></td>
                                                        <td class="text-end"><strong id="product-subtotal-bv">0</strong></td>
                                                        <td class="text-end"><strong id="product-subtotal">0.00</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><label class="mb-0">Tax:</label></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input" style="width:100px; margin-left: auto;"></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><label class="mb-0">Extra Discount:</label></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input" style="width:100px; margin-left: auto;"></td>
                                                    </tr>
                                                    <tr id="coupon-discount-row" style="display:none;">
                                                        <td colspan="4" class="text-end"><strong>Coupon Discount:</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td class="text-end"><strong>-<span id="coupon-discount-amount">0.00</span></strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                        <td class="text-end"><strong id="product-total-pv">0</strong></td>
                                                        <td class="text-end"><strong id="product-total-bv">0</strong></td>
                                                        <td class="text-end"><strong id="product-total">0.00</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        @error('product_quantities')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>

                                    <div id="manual-items-section" style="{{ old('use_product_quantities', 0) ? 'display:none;' : '' }}">
                                        <datalist id="invoice-products-datalist">
                                            @foreach($products as $p)
                                                <option value="{{ $p->display_name }}"></option>
                                            @endforeach
                                        </datalist>
                                        <label class="form-label">Items <span class="text-danger">*</span></label>
                                        <div class="table-responsive mb-2">
                                            <table class="table table-bordered" id="invoice-items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Item Name</th>
                                                        <th style="width:100px">Quantity</th>
                                                        <th style="width:80px">Unit</th>
                                                        <th style="width:120px">Unit Price</th>
                                                        <th style="width:90px" class="text-end">PV</th>
                                                        <th style="width:90px" class="text-end">BV</th>
                                                        <th style="width:120px">Line Total</th>
                                                        <th style="width:80px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invoice-items-tbody">
                                                    <tr class="invoice-item-row">
                                                        <td><input type="text" name="items[0][item_name]" class="form-control form-control-sm" list="invoice-products-datalist" autocomplete="off"></td>
                                                        <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" step="1" min="1" value="1" required></td>
                                                        <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                                                        <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                                                        <td class="text-end">
                                                            <small class="text-muted d-block">Unit: <span class="manual-unit-pv">0.0</span></small>
                                                            <span class="manual-line-pv">0.0</span>
                                                        </td>
                                                        <td class="text-end">
                                                            <small class="text-muted d-block">Unit: <span class="manual-unit-bv">0.0</span></small>
                                                            <span class="manual-line-bv">0.0</span>
                                                        </td>
                                                        <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                                                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                                        <td class="text-end"><strong id="manual-subtotal-pv">0.0</strong></td>
                                                        <td class="text-end"><strong id="manual-subtotal-bv">0.0</strong></td>
                                                        <td><input type="text" class="form-control form-control-sm" id="subtotal-display" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6" class="text-end"><label class="mb-0">Tax:</label></td>
                                                        <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input-manual"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6" class="text-end"><label class="mb-0">Extra Discount:</label></td>
                                                        <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input-manual"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr id="coupon-discount-row-manual" style="display:none;">
                                                        <td colspan="6" class="text-end"><strong>Coupon Discount:</strong></td>
                                                        <td><input type="text" class="form-control form-control-sm text-end" id="coupon-discount-amount-manual" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                        <td class="text-end"><strong id="manual-total-pv">0.0</strong></td>
                                                        <td class="text-end"><strong id="manual-total-bv">0.0</strong></td>
                                                        <td><input type="text" class="form-control form-control-sm" id="total-display" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-row"><i class="fe fe-plus me-1"></i>Add row</button>
                                        @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>

                                    <hr class="my-4">
                                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                </form>
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
                                                <label class="form-label">Coupon Code</label>
                                                <div class="input-group">
                                                    <input type="text" id="payment_coupon_code" class="form-control" placeholder="Enter coupon code (optional)">
                                                    <button type="button" class="btn btn-outline-primary" id="payment_coupon_check_btn">Apply</button>
                                                </div>
                                                <div id="payment_coupon_feedback" class="mt-1 small"></div>
                                            </div>
                                            <div id="split_pos_bank_modal_wrap" class="row g-3" style="display:none;">
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <label class="form-label mb-1">POS Machine</label>
                                                        <select id="pos_machine_modal" class="form-select">
                                                            <option value="">Select POS machine (optional)</option>
                                                            @foreach(($posMachines ?? collect()) as $m)
                                                                <option
                                                                    value="{{ $m->id }}"
                                                                    data-bank="{{ $m->bank_name }}"
                                                                    data-account-name="{{ $m->account_name }}"
                                                                    data-account-number="{{ $m->account_number }}"
                                                                >
                                                                    {{ $m->bank_name ?: 'POS' }}{{ $m->account_number ? ' • '.$m->account_number : '' }}{{ $m->account_name ? ' • '.$m->account_name : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div id="pos_machine_modal_note" class="small text-muted mt-1"></div>
                                                    </div>
                                                    <div id="pos_amount_paid_modal_wrap" style="display:none;">
                                                        <input type="text" inputmode="decimal" id="pos_amount_paid_modal" class="form-control" placeholder="Enter amount (e.g. 1,000,000)">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-2">
                                                        <label class="form-label mb-1">Bank Account</label>
                                                        <select id="bank_account_modal" class="form-select">
                                                            <option value="">Select bank (optional)</option>
                                                            @foreach(($banks ?? collect()) as $b)
                                                                <option
                                                                    value="{{ $b->id }}"
                                                                    data-bank="{{ $b->name }}"
                                                                    data-account-name="{{ $b->account_name }}"
                                                                    data-account-number="{{ $b->account_number }}"
                                                                >
                                                                    {{ $b->name ?: 'Bank' }}{{ $b->account_number ? ' • '.$b->account_number : '' }}{{ $b->account_name ? ' • '.$b->account_name : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div id="bank_account_modal_note" class="small text-muted mt-1"></div>
                                                    </div>
                                                    <div id="bank_amount_paid_modal_wrap" style="display:none;">
                                                        <input type="text" inputmode="decimal" id="bank_amount_paid_modal" class="form-control" placeholder="Enter amount (e.g. 1,000,000)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="split_payment_modal">
                                                    <label class="form-check-label" for="split_payment_modal">Split payment (Wallet + Credit + Cash)</label>
                                                </div>
                                            </div>
                                            <div class="col-12" id="split_fields_modal" style="display:none;">
                                                <div class="row g-3">
                                                    <div class="col-md-3" id="split_wallet_amount_modal_wrap">
                                                        <div class="text-muted fw-semibold mb-1" style="font-size: 14px;">Balance: ₦<span id="split-wallet-available-modal">0.00</span></div>
                                                        <label class="form-label">Wallet Amount</label>
                                                        <input type="text" inputmode="decimal" id="split_wallet_amount_modal" class="form-control" value="" placeholder="0.00">
                                                    </div>
                                                    <div class="col-md-3" id="split_kd_credit_amount_modal_wrap">
                                                        <div class="text-muted fw-semibold mb-1" style="font-size: 14px;">Balance: ₦<span id="split-kd-credit-available-modal">0.00</span></div>
                                                        <label class="form-label">Credit Amount</label>
                                                        <input type="text" inputmode="decimal" id="split_kd_credit_amount_modal" class="form-control" value="" placeholder="0.00">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Cash Amount</label>
                                                    <input type="text" inputmode="decimal" id="split_cash_amount_modal" class="form-control" value="" placeholder="0.00">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted fw-semibold" style="font-size: 15px;">
                                                            DPBV Available: <strong><span id="split-dpbv-total-modal">0.00</span> DPBV</strong> (₦<span id="split-dpbv-naira-modal">0.00</span>)
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted fw-semibold" style="font-size: 15px;">
                                                            Total due: ₦<span id="split-total-due-modal">0.00</span> • Split total: ₦<span id="split-total-entered-modal">0.00</span> • Remaining: ₦<span id="split-remaining-modal">0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="payment_apply_btn" data-bs-dismiss="modal">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-md modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="customerModalLabel">Service Center Referral Code</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">Service Center Referral Code (code or name)</label>
                                        <input type="text" class="form-control" id="customer_sc_referral_code" placeholder="Enter Service Center code or name">
                                        <div id="customer_sc_referral_feedback" class="mt-1 small"></div>
                                        <div class="text-muted small mt-2">
                                            This will sync to the invoice form automatically.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidemenu/sidemenu.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidebar/sidebar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/sticky.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>

    <script>
    (function() {
        function formatPrice(num) {
            return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        function formatPvBv(num) {
            return parseFloat(num).toFixed(1).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Toggle between products and manual items
        var useProductsToggle = document.getElementById('use-products-toggle');
        var productsSection = document.getElementById('products-section');
        var manualItemsSection = document.getElementById('manual-items-section');
        var useProductQuantitiesInput = document.querySelector('input[name="use_product_quantities"]');

        function setManualItemsRequired(required) {
            manualItemsSection.querySelectorAll('input[required], select[required]').forEach(function(el) {
                if (required) el.setAttribute('required', 'required');
                else el.removeAttribute('required');
            });
        }

        function applyUseProducts() {
            var useProducts = !!useProductsToggle.checked;
            productsSection.style.display = useProducts ? 'block' : 'none';
            manualItemsSection.style.display = useProducts ? 'none' : 'block';
            useProductQuantitiesInput.value = useProducts ? '1' : '0';
            setManualItemsRequired(!useProducts);
        }

        useProductsToggle.addEventListener('change', applyUseProducts);
        applyUseProducts();

        // Coupon state (applies to both products + manual)
        var coupon = { percentage: 0, amount: 0 };
        var couponInput = document.getElementById('invoice_coupon_code');
        var couponFeedback = document.getElementById('invoice_coupon_feedback');
        var couponBtn = document.getElementById('invoice_coupon_check_btn');
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function setCoupon(pct, subtotal) {
            coupon.percentage = pct || 0;
            coupon.amount = (coupon.percentage > 0) ? ((coupon.percentage / 100) * (subtotal || 0)) : 0;
        }

        async function validateCoupon(code) {
            if (!couponFeedback) return null;
            if (!code) return null;
            couponFeedback.innerHTML = '<span class="text-muted"><i class="fe fe-refresh-cw fe-spin me-1"></i>Checking coupon...</span>';
            try {
                var controller = new AbortController();
                var timeout = setTimeout(function () { controller.abort(); }, 8000);
                var res = await fetch('{{ route("invoices.validate-coupon") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ code: code }),
                    signal: controller.signal,
                });
                clearTimeout(timeout);

                var contentType = (res.headers.get('content-type') || '').toLowerCase();
                if (!contentType.includes('application/json')) {
                    return { valid: false, message: 'Coupon check failed (non-JSON response). Please refresh and try again.' };
                }

                var data = await res.json();
                if (data && data.valid) return data;
                return { valid: false, message: data?.message || 'Invalid coupon code.' };
            } catch (e) {
                if (e && (e.name === 'AbortError' || String(e).includes('AbortError'))) {
                    return { valid: false, message: 'Coupon check timed out. Please try again.' };
                }
                return { valid: false, message: 'Error checking coupon.' };
            }
        }

        // Products section calculations
        var productTable = document.getElementById('product-quantities-table');
        if (productTable) {
            function updateProductTotals() {
                var subtotal = 0, subtotalPv = 0, subtotalBv = 0;
                productTable.querySelectorAll('.product-row').forEach(function(row) {
                    var qty = parseFloat(row.querySelector('.product-qty').value) || 0;
                    var price = parseFloat(row.querySelector('.product-qty').getAttribute('data-unit-price')) || 0;
                    var pv = parseFloat(row.getAttribute('data-pv')) || 0;
                    var bv = parseFloat(row.getAttribute('data-bv')) || 0;
                    var lineTotal = qty * price;
                    var linePv = qty * pv;
                    var lineBv = qty * bv;
                    row.querySelector('.product-line-total').textContent = formatPrice(lineTotal);
                    row.querySelector('.product-line-pv').textContent = formatPvBv(linePv);
                    row.querySelector('.product-line-bv').textContent = formatPvBv(lineBv);
                    subtotal += lineTotal;
                    subtotalPv += linePv;
                    subtotalBv += lineBv;
                });
                var tax = parseFloat(document.getElementById('tax-input').value) || 0;
                var discount = parseFloat(document.getElementById('discount-input').value) || 0;
                setCoupon(coupon.percentage, subtotal);
                var total = subtotal + tax - discount - coupon.amount;
                document.getElementById('product-subtotal').textContent = formatPrice(subtotal);
                document.getElementById('product-subtotal-pv').textContent = formatPvBv(subtotalPv);
                document.getElementById('product-subtotal-bv').textContent = formatPvBv(subtotalBv);
                document.getElementById('product-total').textContent = formatPrice(total);
                document.getElementById('product-total-pv').textContent = formatPvBv(subtotalPv);
                document.getElementById('product-total-bv').textContent = formatPvBv(subtotalBv);

                var couponRow = document.getElementById('coupon-discount-row');
                var couponAmtEl = document.getElementById('coupon-discount-amount');
                if (couponRow && couponAmtEl) {
                    if (coupon.amount > 0) {
                        couponRow.style.display = '';
                        couponAmtEl.textContent = formatPrice(coupon.amount);
                    } else {
                        couponRow.style.display = 'none';
                        couponAmtEl.textContent = '0.00';
                    }
                }

                // Floating totals (fixed)
                var floatSubtotal = document.getElementById('invoice-float-subtotal');
                var floatTotal = document.getElementById('invoice-float-total');
                var floatPv = document.getElementById('invoice-float-pv');
                var floatBv = document.getElementById('invoice-float-bv');
                if (floatSubtotal) floatSubtotal.textContent = formatPrice(subtotal);
                if (floatTotal) floatTotal.textContent = formatPrice(total);
                if (floatPv) floatPv.textContent = formatPvBv(subtotalPv);
                if (floatBv) floatBv.textContent = formatPvBv(subtotalBv);
            }
            productTable.querySelectorAll('.product-qty').forEach(function(input) {
                input.addEventListener('input', updateProductTotals);
            });
            document.getElementById('tax-input').addEventListener('input', updateProductTotals);
            document.getElementById('discount-input').addEventListener('input', updateProductTotals);

            var searchInput = document.getElementById('product-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var q = (this.value || '').trim().toLowerCase();
                    productTable.querySelectorAll('.product-row').forEach(function(row) {
                        var text = (row.getAttribute('data-search') || '').toLowerCase();
                        row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            }

            updateProductTotals();
        }

        // Manual items section
        var tbody = document.getElementById('invoice-items-tbody');
        if (tbody) {
            var rowIndex = 1;
            var template = document.createElement('template');
            template.innerHTML = `
                <tr class="invoice-item-row">
                    <td><input type="text" name="items[__INDEX__][item_name]" class="form-control form-control-sm" list="invoice-products-datalist" autocomplete="off"></td>
                    <td><input type="number" name="items[__INDEX__][quantity]" class="form-control form-control-sm item-qty" step="1" min="1" value="1" required></td>
                    <td><input type="text" name="items[__INDEX__][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                    <td><input type="number" name="items[__INDEX__][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                    <td class="text-end">
                        <small class="text-muted d-block">Unit: <span class="manual-unit-pv">0.0</span></small>
                        <span class="manual-line-pv">0.0</span>
                    </td>
                    <td class="text-end">
                        <small class="text-muted d-block">Unit: <span class="manual-unit-bv">0.0</span></small>
                        <span class="manual-line-bv">0.0</span>
                    </td>
                    <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                </tr>
            `;

            function updateTotals() {
                var subtotal = 0, subtotalPv = 0, subtotalBv = 0;
                document.querySelectorAll('.invoice-item-row').forEach(function(row) {
                    var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                    var price = parseFloat(row.querySelector('.item-price').value) || 0;
                    var pvUnit = parseFloat(row.getAttribute('data-pv-unit') || '0') || 0;
                    var bvUnit = parseFloat(row.getAttribute('data-bv-unit') || '0') || 0;
                    var lineTotal = qty * price;
                    var linePv = qty * pvUnit;
                    var lineBv = qty * bvUnit;
                    row.querySelector('.line-total').value = formatPrice(lineTotal);
                    var unitPvEl = row.querySelector('.manual-unit-pv');
                    var unitBvEl = row.querySelector('.manual-unit-bv');
                    var linePvEl = row.querySelector('.manual-line-pv');
                    var lineBvEl = row.querySelector('.manual-line-bv');
                    if (unitPvEl) unitPvEl.textContent = formatPvBv(pvUnit);
                    if (unitBvEl) unitBvEl.textContent = formatPvBv(bvUnit);
                    if (linePvEl) linePvEl.textContent = formatPvBv(linePv);
                    if (lineBvEl) lineBvEl.textContent = formatPvBv(lineBv);
                    subtotal += lineTotal;
                    subtotalPv += linePv;
                    subtotalBv += lineBv;
                });
                var tax = parseFloat(document.getElementById('tax-input-manual').value) || 0;
                var discount = parseFloat(document.getElementById('discount-input-manual').value) || 0;
                setCoupon(coupon.percentage, subtotal);
                var total = subtotal + tax - discount - coupon.amount;
                document.getElementById('subtotal-display').value = formatPrice(subtotal);
                document.getElementById('total-display').value = formatPrice(total);
                var subtotalPvEl = document.getElementById('manual-subtotal-pv');
                var subtotalBvEl = document.getElementById('manual-subtotal-bv');
                var totalPvEl = document.getElementById('manual-total-pv');
                var totalBvEl = document.getElementById('manual-total-bv');
                if (subtotalPvEl) subtotalPvEl.textContent = formatPvBv(subtotalPv);
                if (subtotalBvEl) subtotalBvEl.textContent = formatPvBv(subtotalBv);
                if (totalPvEl) totalPvEl.textContent = formatPvBv(subtotalPv);
                if (totalBvEl) totalBvEl.textContent = formatPvBv(subtotalBv);

                // Floating totals (fixed) for manual mode
                var floatSubtotal = document.getElementById('invoice-float-subtotal');
                var floatTotal = document.getElementById('invoice-float-total');
                var floatPv = document.getElementById('invoice-float-pv');
                var floatBv = document.getElementById('invoice-float-bv');
                if (floatSubtotal) floatSubtotal.textContent = formatPrice(subtotal);
                if (floatTotal) floatTotal.textContent = formatPrice(total);
                if (floatPv) floatPv.textContent = formatPvBv(subtotalPv);
                if (floatBv) floatBv.textContent = formatPvBv(subtotalBv);

                var couponRowM = document.getElementById('coupon-discount-row-manual');
                var couponAmtM = document.getElementById('coupon-discount-amount-manual');
                if (couponRowM && couponAmtM) {
                    if (coupon.amount > 0) {
                        couponRowM.style.display = '';
                        couponAmtM.value = formatPrice(coupon.amount);
                    } else {
                        couponRowM.style.display = 'none';
                        couponAmtM.value = '0.00';
                    }
                }
            }

            // Product lookup for manual mode (name -> unit, price, pv, bv)
            var productIndex = (function() {
                var arr = @json($manualProducts ?? []);
                var map = {};
                (arr || []).forEach(function(p) {
                    if (!p || !p.name) return;
                    map[String(p.name).trim().toLowerCase()] = p;
                });
                return map;
            })();

            function addManualRow() {
                var trHtml = String(template.innerHTML || '').replace(/__INDEX__/g, rowIndex++);
                tbody.insertAdjacentHTML('beforeend', trHtml);
                var tr = tbody.lastElementChild;
                if (!tr) return null;
                tr.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
                    input.addEventListener('input', updateTotals);
                });
                wireManualProductAutocomplete(tr);
                tr.querySelector('.remove-row')?.addEventListener('click', function() {
                    tr.remove();
                    updateTotals();
                });
                return tr;
            }

            function maybeAutoAddRow(currentRow) {
                if (!tbody) return;
                if (!currentRow) return;
                if (tbody.lastElementChild !== currentRow) return;
                var nameInput = currentRow.querySelector('input[name*="[item_name]"]');
                if (!nameInput) return;
                if ((nameInput.value || '').trim() === '') return;
                // if the last row already has a value, add a new blank row
                addManualRow();
            }

            function mergeDuplicateRows(currentRow) {
                if (!tbody || !currentRow) return;
                if (currentRow.dataset && currentRow.dataset.merged === '1') return;
                var nameInput = currentRow.querySelector('input[name*="[item_name]"]');
                if (!nameInput) return;
                var name = (nameInput.value || '').trim();
                if (name === '') return;
                var key = name.toLowerCase();

                var rows = Array.from(tbody.querySelectorAll('.invoice-item-row'));
                var first = rows.find(function(r) {
                    if (r === currentRow) return false;
                    var otherName = (r.querySelector('input[name*="[item_name]"]')?.value || '').trim().toLowerCase();
                    return otherName !== '' && otherName === key;
                });
                if (!first) return;

                var qtyInputCurrent = currentRow.querySelector('.item-qty');
                var qtyInputFirst = first.querySelector('.item-qty');
                var addQty = parseFloat(qtyInputCurrent?.value) || 0;
                var baseQty = parseFloat(qtyInputFirst?.value) || 0;
                if (qtyInputFirst) qtyInputFirst.value = String(Math.max(0, baseQty + addQty));

                // Prefer keeping first row's unit/unit_price; just remove duplicate row
                if (currentRow.dataset) currentRow.dataset.merged = '1';
                currentRow.remove();
                updateTotals();
            }

            function wireManualProductAutocomplete(row) {
                var nameInput = row.querySelector('input[name*="[item_name]"]');
                if (!nameInput) return;

                function applyIfMatch() {
                    var key = (nameInput.value || '').trim().toLowerCase();
                    if (row && row.dataset) {
                        if (key === '') {
                            row.dataset.lastKey = '';
                            row.dataset.merged = '0';
                        } else if (row.dataset.lastKey === key) {
                            return;
                        } else {
                            row.dataset.lastKey = key;
                            row.dataset.merged = '0';
                        }
                    }
                    var p = productIndex[key];
                    if (!p) {
                        mergeDuplicateRows(row);
                        return updateTotals();
                    }

                    var unitInput = row.querySelector('input[name*="[unit]"]');
                    var priceInput = row.querySelector('input[name*="[unit_price]"]');

                    if (unitInput && (!unitInput.value || unitInput.value.trim() === '')) unitInput.value = p.unit || 'pcs';
                    if (priceInput) priceInput.value = p.price || 0;
                    row.setAttribute('data-pv-unit', String(p.pv || 0));
                    row.setAttribute('data-bv-unit', String(p.bv || 0));
                    updateTotals();
                    mergeDuplicateRows(row);
                    maybeAutoAddRow(row);
                }

                nameInput.addEventListener('change', applyIfMatch);
                // blur can fire right after change in some browsers and double-merge; keep only change.
            }

            document.getElementById('add-row').addEventListener('click', function() {
                addManualRow();
            });

            document.querySelectorAll('.remove-row').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    this.closest('tr').remove();
                    updateTotals();
                });
            });

            document.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
                input.addEventListener('input', updateTotals);
            });
            // wire existing row (first row) for product selection autofill
            tbody.querySelectorAll('.invoice-item-row').forEach(function(row) {
                wireManualProductAutocomplete(row);
            });
            document.getElementById('tax-input-manual').addEventListener('input', updateTotals);
            document.getElementById('discount-input-manual').addEventListener('input', updateTotals);

            updateTotals();
        }

        // Sync tax/discount between sections
        var taxInput = document.getElementById('tax-input');
        var taxInputManual = document.getElementById('tax-input-manual');
        var discountInput = document.getElementById('discount-input');
        var discountInputManual = document.getElementById('discount-input-manual');

        if (taxInput && taxInputManual) {
            taxInput.addEventListener('input', function() {
                taxInputManual.value = this.value;
            });
            taxInputManual.addEventListener('input', function() {
                taxInput.value = this.value;
            });
        }
        if (discountInput && discountInputManual) {
            discountInput.addEventListener('input', function() {
                discountInputManual.value = this.value;
            });
            discountInputManual.addEventListener('input', function() {
                discountInput.value = this.value;
            });
        }

        // Service Center referral code live validation (same as checkout)
        (function() {
            function wireScCodeValidation(inputId, feedbackId) {
                var scInput = document.getElementById(inputId);
                var scFeedback = document.getElementById(feedbackId);
                var scTimeout = null;
                var lastResolved = '';
                if (!scInput || !scFeedback) return null;

                scInput.addEventListener('input', function() {
                    var code = scInput.value.trim();
                    scFeedback.innerHTML = '';
                    scInput.classList.remove('is-valid', 'is-invalid');

                    if (scTimeout) clearTimeout(scTimeout);
                    if (code.length === 0) return;

                    scTimeout = setTimeout(function() {
                        // Avoid infinite loops when we replace name -> code
                        if (lastResolved === code) return;

                        scFeedback.innerHTML = '<span class="text-muted"><i class="fe fe-refresh-cw fe-spin me-1"></i>Checking...</span>';
                        fetch('{{ route("service-center.resolve") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ query: code })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.valid) {
                                if (data.code && scInput.value.trim() !== data.code) {
                                    lastResolved = data.code;
                                    scInput.value = data.code;
                                }
                                scInput.classList.add('is-valid');
                                scFeedback.innerHTML = '<div class="text-success fw-semibold" style="font-size: 18px; line-height: 1.25;"><i class="fe fe-check-circle me-1"></i>Valid Service Center: <strong>' + data.name + '</strong></div>';
                            } else {
                                scInput.classList.add('is-invalid');
                                scFeedback.innerHTML = '<span class="text-danger"><i class="fe fe-x-circle me-1"></i>' + (data.message || 'Invalid Service Center code.') + '</span>';
                            }
                        })
                        .catch(function() {
                            scFeedback.innerHTML = '<span class="text-warning">Error checking code.</span>';
                        });
                    }, 500);
                });

                return scInput;
            }

            var scReferralInput = wireScCodeValidation('invoice_sc_referral_code', 'invoice_sc_referral_feedback');
            if (scReferralInput && scReferralInput.value.trim()) scReferralInput.dispatchEvent(new Event('input'));
        })();

        // Coupon check button + auto-check
        (function() {
            if (!couponInput || !couponBtn || !couponFeedback) return;

            function currentSubtotal() {
                // Always compute from inputs (display might be stale if JS previously errored or hasn’t recalculated yet)
                if (useProductsToggle && useProductsToggle.checked && productTable) {
                    var subtotal = 0;
                    productTable.querySelectorAll('.product-row').forEach(function(row) {
                        var qty = parseFloat(row.querySelector('.product-qty')?.value) || 0;
                        var price = parseFloat(row.querySelector('.product-qty')?.getAttribute('data-unit-price')) || 0;
                        subtotal += qty * price;
                    });
                    return subtotal;
                }

                // manual mode
                var subtotalM = 0;
                (tbody ? tbody.querySelectorAll('.invoice-item-row') : []).forEach(function(row) {
                    var name = (row.querySelector('input[name*="[item_name]"]')?.value || '').trim();
                    if (name === '') return;
                    var qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
                    var price = parseFloat(row.querySelector('.item-price')?.value) || 0;
                    subtotalM += qty * price;
                });
                return subtotalM;
            }

            function currentTaxDiscount() {
                if (useProductsToggle && useProductsToggle.checked) {
                    var tax = parseFloat(document.getElementById('tax-input')?.value || 0) || 0;
                    var discount = parseFloat(document.getElementById('discount-input')?.value || 0) || 0;
                    return { tax: tax, discount: discount };
                }
                var taxM = parseFloat(document.getElementById('tax-input-manual')?.value || 0) || 0;
                var discountM = parseFloat(document.getElementById('discount-input-manual')?.value || 0) || 0;
                return { tax: taxM, discount: discountM };
            }

            async function runCheck() {
                var code = (couponInput.value || '').trim();
                couponInput.classList.remove('is-valid', 'is-invalid');
                couponFeedback.innerHTML = '';
                coupon.percentage = 0;
                coupon.amount = 0;

                if (!code) {
                    if (productTable) productTable.querySelectorAll('.product-qty')[0]?.dispatchEvent(new Event('input', { bubbles: true }));
                    if (tbody) document.getElementById('tax-input-manual')?.dispatchEvent(new Event('input', { bubbles: true }));
                    return;
                }

                var data = await validateCoupon(code);
                if (data && data.valid) {
                    couponInput.classList.add('is-valid');
                    coupon.percentage = parseFloat(data.discount_percentage) || 0;
                    var subtotalNow = currentSubtotal();
                    var td = currentTaxDiscount();
                    setCoupon(coupon.percentage, subtotalNow);
                    var totalBefore = (subtotalNow || 0) + (td.tax || 0) - (td.discount || 0);
                    var totalAfter = totalBefore - (coupon.amount || 0);

                    couponFeedback.innerHTML =
                        '<span class="text-success"><i class="fe fe-check-circle me-1"></i>Coupon applied: <strong>' + coupon.percentage + '%</strong> off</span>' +
                        '<div class="text-muted mt-1">Works on subtotal: <strong>₦' + formatPrice(subtotalNow) + '</strong></div>' +
                        '<div class="text-muted">Old total: <span style="text-decoration: line-through;">₦' + formatPrice(totalBefore) + '</span></div>' +
                        '<div class="text-success fw-semibold">New total: ₦' + formatPrice(totalAfter) + ' <span class="text-muted fw-normal">(You save ₦' + formatPrice(coupon.amount) + ')</span></div>';
                } else {
                    couponInput.classList.add('is-invalid');
                    couponFeedback.innerHTML = '<span class="text-danger"><i class="fe fe-x-circle me-1"></i>' + (data?.message || 'Invalid coupon code.') + '</span>';
                }

                // Recalc totals (both sections compute coupon.amount off current subtotal)
                if (productTable) {
                    document.getElementById('tax-input')?.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (tbody) {
                    document.getElementById('tax-input-manual')?.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            couponBtn.addEventListener('click', runCheck);
            couponInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runCheck();
                }
            });

            if ((couponInput.value || '').trim()) {
                runCheck();
            }
        })();

        // Toggle: SC referral vs Customer details
        (function() {
            var toggle = document.getElementById('use-sc-referral-toggle');
            var customerWrap = document.getElementById('invoice-customer-fields');
            var scWrap = document.getElementById('invoice-sc-referral-fields');
            var scInput = document.getElementById('invoice_sc_referral_code');
            var useScHidden = document.getElementById('use_sc_referral');
            if (!toggle || !customerWrap || !scWrap) return;

            function setEnabled(container, enabled) {
                container.querySelectorAll('input, select, textarea').forEach(function(el) {
                    el.disabled = !enabled;
                });
            }

            function apply() {
                var useSc = !!toggle.checked;
                if (useScHidden) useScHidden.value = useSc ? '1' : '0';
                // Keep both sections visible; toggle only controls which is enabled.
                customerWrap.style.display = '';
                scWrap.style.display = '';
                setEnabled(customerWrap, !useSc);
                setEnabled(scWrap, useSc);

                // Trigger validation when enabling SC code section
                if (useSc && scInput && scInput.value.trim()) {
                    scInput.dispatchEvent(new Event('input'));
                }
            }

            toggle.addEventListener('change', apply);
            apply();
        })();

        // Customer modal: allow entering Service Center code
        (function() {
            var modalInput = document.getElementById('customer_sc_referral_code');
            var modalFeedback = document.getElementById('customer_sc_referral_feedback');

            var mainToggle = document.getElementById('use-sc-referral-toggle');
            var mainHidden = document.getElementById('use_sc_referral');
            var mainInput = document.getElementById('invoice_sc_referral_code');
            var mainFeedback = document.getElementById('invoice_sc_referral_feedback');

            if (!modalInput || !mainToggle || !mainInput) return;

            function syncFromMain() {
                modalInput.value = mainInput.value || '';
                if (modalFeedback && mainFeedback) modalFeedback.innerHTML = mainFeedback.innerHTML || '';
            }

            function enableAndSyncToMain() {
                if (!mainToggle.checked) {
                    mainToggle.checked = true;
                    mainToggle.dispatchEvent(new Event('change'));
                }
                if (mainHidden) mainHidden.value = '1';
                mainInput.value = modalInput.value || '';
                mainInput.dispatchEvent(new Event('input'));
            }

            modalInput.addEventListener('input', function() {
                enableAndSyncToMain();
                if (modalFeedback && mainFeedback) {
                    setTimeout(function() {
                        modalFeedback.innerHTML = mainFeedback.innerHTML || '';
                    }, 20);
                }
            });

            // When modal opens, mirror current state
            var modalEl = document.getElementById('customerModal');
            if (modalEl && window.bootstrap?.Modal) {
                modalEl.addEventListener('shown.bs.modal', function() {
                    syncFromMain();
                    modalInput.focus();
                });
            }
        })();

        // Payment modal: sync payment fields into hidden form inputs
        (function() {
            var hiddenMethod = document.getElementById('payment_method_hidden');
            var hiddenPos = document.getElementById('pos_amount_paid_hidden');
            var hiddenBankAmt = document.getElementById('bank_amount_paid_hidden');
            var hiddenSplit = document.getElementById('split-payment-toggle');

            var modalPos = document.getElementById('pos_amount_paid_modal');
            var modalSplit = document.getElementById('split_payment_modal');
            var modalSplitWrap = document.getElementById('split_fields_modal');
            var applyBtn = document.getElementById('payment_apply_btn');
            var modalPosMachine = document.getElementById('pos_machine_modal');
            var modalPosMachineNote = document.getElementById('pos_machine_modal_note');
            var modalBankAccount = document.getElementById('bank_account_modal');
            var modalBankAccountNote = document.getElementById('bank_account_modal_note');
            var modalBankAmount = document.getElementById('bank_amount_paid_modal');
            var modalSplitPosBankWrap = document.getElementById('split_pos_bank_modal_wrap');

            // Coupon sync (keep main coupon logic intact)
            var mainCoupon = document.getElementById('invoice_coupon_code');
            var mainCouponBtn = document.getElementById('invoice_coupon_check_btn');
            var mainCouponFeedback = document.getElementById('invoice_coupon_feedback');
            var modalCoupon = document.getElementById('payment_coupon_code');
            var modalCouponBtn = document.getElementById('payment_coupon_check_btn');
            var modalCouponFeedback = document.getElementById('payment_coupon_feedback');

            var modalWallet = document.getElementById('split_wallet_amount_modal');
            var modalKd = document.getElementById('split_kd_credit_amount_modal');
            var modalCash = document.getElementById('split_cash_amount_modal');
            var modalWalletWrap = document.getElementById('split_wallet_amount_modal_wrap');
            var modalKdWrap = document.getElementById('split_kd_credit_amount_modal_wrap');

            var hiddenWallet = document.querySelector('input[name="split_wallet_amount"]');
            var hiddenKd = document.querySelector('input[name="split_kd_credit_amount"]');
            var hiddenCash = document.querySelector('input[name="split_cash_amount"]');

            var sumMethod = document.getElementById('payment-summary-method');
            var sumPos = document.getElementById('payment-summary-pos');
            var sumSplit = document.getElementById('payment-summary-split');

            if (!hiddenMethod || !hiddenPos || !hiddenBankAmt || !hiddenSplit || !modalPos || !modalSplit || !applyBtn) return;

            function parseMoney(str) {
                let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                const firstDot = cleaned.indexOf('.');
                if (firstDot !== -1) {
                    cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                }
                const num = Number(cleaned);
                return Number.isFinite(num) ? num : 0;
            }

            function fmt(num) {
                var n = parseFloat(num || 0) || 0;
                return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function updateModalSplitTotals() {
                var dueEl = document.getElementById('split-total-due-modal');
                var enteredEl = document.getElementById('split-total-entered-modal');
                var remainingEl = document.getElementById('split-remaining-modal');
                if (!dueEl || !enteredEl || !remainingEl) return;

                var due = parseMoney(dueEl.textContent || '0');
                var walletAmt = parseMoney(modalWallet?.value);
                var kdAmt = parseMoney(modalKd?.value);
                var cashAmt = parseMoney(modalCash?.value);
                var posAmt = parseMoney(modalPos?.value);
                var bankAmt = parseMoney(modalBankAmount?.value);

                var sum = walletAmt + kdAmt + cashAmt + posAmt + bankAmt;
                var rem = due - sum;
                enteredEl.textContent = fmt(sum);
                remainingEl.textContent = fmt(rem);
            }

            function labelFor(method) {
                return ({
                    wallet: 'Wallet',
                    dpbv: 'DPBV',
                    kd_credit: 'KD Credit',
                    cash: 'Cash',
                    transfer: 'Transfer',
                    pay_on_delivery: 'Pay on Delivery'
                })[method] || '—';
            }

            function updateSummary() {
                if (sumMethod) sumMethod.textContent = labelFor(hiddenMethod.value);
                if (sumPos) sumPos.textContent = fmt(hiddenPos.value);
                if (sumSplit) sumSplit.textContent = hiddenSplit.checked ? 'Yes' : 'No';
            }

            function syncModalFromHidden() {
                modalPos.value = hiddenPos.value || '';
                modalSplit.checked = !!hiddenSplit.checked;
                if (modalSplitWrap) modalSplitWrap.style.display = modalSplit.checked ? '' : 'none';
                if (modalWallet && hiddenWallet) modalWallet.value = (parseFloat(hiddenWallet.value || 0) > 0) ? fmt(hiddenWallet.value) : '';
                if (modalKd && hiddenKd) modalKd.value = (parseFloat(hiddenKd.value || 0) > 0) ? fmt(hiddenKd.value) : '';
                if (modalCash && hiddenCash) modalCash.value = (parseFloat(hiddenCash.value || 0) > 0) ? fmt(hiddenCash.value) : '';

                // Coupon
                if (modalCoupon && mainCoupon) modalCoupon.value = mainCoupon.value || '';
                if (modalCouponFeedback && mainCouponFeedback) modalCouponFeedback.innerHTML = mainCouponFeedback.innerHTML || '';

                // Hide split inputs when balance is zero
                var walletAvail = parseMoney(document.getElementById('split-wallet-available-modal')?.textContent || '0');
                var kdAvail = parseMoney(document.getElementById('split-kd-credit-available-modal')?.textContent || '0');
                if (modalWalletWrap) modalWalletWrap.style.display = walletAvail > 0 ? '' : 'none';
                if (modalKdWrap) modalKdWrap.style.display = kdAvail > 0 ? '' : 'none';
                if (modalWallet) modalWallet.disabled = !(walletAvail > 0);
                if (modalKd) modalKd.disabled = !(kdAvail > 0);
                if (walletAvail <= 0 && modalWallet && hiddenWallet) { modalWallet.value = ''; hiddenWallet.value = '0'; }
                if (kdAvail <= 0 && modalKd && hiddenKd) { modalKd.value = ''; hiddenKd.value = '0'; }

                updateModalSplitTotals();
            }

            function applyToHidden() {
                // Payment method dropdown removed from modal: keep method empty; split payment covers multi-method cases.
                hiddenMethod.value = '';
                hiddenMethod.dispatchEvent(new Event('change'));

                hiddenPos.value = modalPos.value || '';
                hiddenPos.dispatchEvent(new Event('input'));

                hiddenSplit.checked = !!modalSplit.checked;
                hiddenSplit.dispatchEvent(new Event('change'));

                if (hiddenWallet && modalWallet) hiddenWallet.value = parseMoney(modalWallet.value || '0').toFixed(2);
                if (hiddenKd && modalKd) hiddenKd.value = parseMoney(modalKd.value || '0').toFixed(2);
                if (hiddenCash && modalCash) hiddenCash.value = parseMoney(modalCash.value || '0').toFixed(2);
                if (hiddenWallet) hiddenWallet.dispatchEvent(new Event('input', { bubbles: true }));
                if (hiddenKd) hiddenKd.dispatchEvent(new Event('input', { bubbles: true }));
                if (hiddenCash) hiddenCash.dispatchEvent(new Event('input', { bubbles: true }));

                updateSummary();
                updateModalSplitTotals();
            }

            modalSplit.addEventListener('change', function() {
                if (modalSplitWrap) modalSplitWrap.style.display = modalSplit.checked ? '' : 'none';
            });

            applyBtn.addEventListener('click', applyToHidden);

            // Live sync while user edits the modal (so balances update immediately)
            function liveSyncToHidden() {
                hiddenMethod.value = '';
                hiddenMethod.dispatchEvent(new Event('change'));

                hiddenPos.value = modalPos.value || '';
                hiddenPos.dispatchEvent(new Event('input'));

                hiddenSplit.checked = !!modalSplit.checked;
                hiddenSplit.dispatchEvent(new Event('change'));

                if (hiddenWallet && modalWallet) hiddenWallet.value = parseMoney(modalWallet.value || '0').toFixed(2);
                if (hiddenKd && modalKd) hiddenKd.value = parseMoney(modalKd.value || '0').toFixed(2);
                if (hiddenCash && modalCash) hiddenCash.value = parseMoney(modalCash.value || '0').toFixed(2);
                if (hiddenWallet) hiddenWallet.dispatchEvent(new Event('input', { bubbles: true }));
                if (hiddenKd) hiddenKd.dispatchEvent(new Event('input', { bubbles: true }));
                if (hiddenCash) hiddenCash.dispatchEvent(new Event('input', { bubbles: true }));

                updateSummary();
                updateModalSplitTotals();
            }

            // When the user changes payment method or split toggle, immediately sync and load balances.
            modalSplit.addEventListener('change', liveSyncToHidden);

            function wireMoneyInput(el) {
                if (!el) return;
                el.addEventListener('input', function () {
                    el.value = String(el.value || '').replace(/[^0-9.,]/g, '');
                    liveSyncToHidden();
                });
                el.addEventListener('blur', function () {
                    if ((el.value || '').trim() === '') return;
                    el.value = fmt(parseMoney(el.value));
                    liveSyncToHidden();
                });
            }
            wireMoneyInput(modalWallet);
            wireMoneyInput(modalKd);
            wireMoneyInput(modalCash);

            function applySplitPosBankVisibility() {
                if (!modalSplitPosBankWrap) return;
                modalSplitPosBankWrap.style.display = modalSplit.checked ? '' : 'none';
                if (!modalSplit.checked) {
                    // clear POS
                    if (modalPosMachine) modalPosMachine.value = '';
                    if (modalPosMachineNote) modalPosMachineNote.textContent = '';
                    if (modalPos) modalPos.value = '';
                    var posAmtWrap = document.getElementById('pos_amount_paid_modal_wrap');
                    if (posAmtWrap) posAmtWrap.style.display = 'none';
                    hiddenPos.value = '';
                    hiddenPos.dispatchEvent(new Event('input'));

                    // clear Bank
                    if (modalBankAccount) modalBankAccount.value = '';
                    if (modalBankAccountNote) modalBankAccountNote.textContent = '';
                    if (modalBankAmount) modalBankAmount.value = '';
                    var bankAmtWrap = document.getElementById('bank_amount_paid_modal_wrap');
                    if (bankAmtWrap) bankAmtWrap.style.display = 'none';
                    hiddenBankAmt.value = '';
                    hiddenBankAmt.dispatchEvent(new Event('input'));
                }
            }
            modalSplit.addEventListener('change', applySplitPosBankVisibility);

            var paymentModalEl = document.getElementById('paymentModal');
            if (paymentModalEl) {
                paymentModalEl.addEventListener('shown.bs.modal', function() {
                    syncModalFromHidden();
                    // Ensure balances are fetched immediately when opening the modal.
                    liveSyncToHidden();
                    applySplitPosBankVisibility();
                });
            }

            // POS machine helper (UI only)
            function updatePosMachineNote() {
                if (!modalPosMachine || !modalPosMachineNote) return;
                var opt = modalPosMachine.options[modalPosMachine.selectedIndex];
                var bank = opt?.getAttribute('data-bank') || '';
                var acctNo = opt?.getAttribute('data-account-number') || '';
                var acctName = opt?.getAttribute('data-account-name') || '';
                if (!modalPosMachine.value) {
                    modalPosMachineNote.textContent = '';
                    return;
                }
                modalPosMachineNote.textContent =
                    (bank ? bank : 'POS') +
                    (acctNo ? ' • ' + acctNo : '') +
                    (acctName ? ' • ' + acctName : '');
            }
            if (modalPosMachine) {
                var posAmtWrap = document.getElementById('pos_amount_paid_modal_wrap');

                function parseMoney(str) {
                    let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                    const firstDot = cleaned.indexOf('.');
                    if (firstDot !== -1) {
                        cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                    }
                    const num = Number(cleaned);
                    return Number.isFinite(num) ? num : 0;
                }

                function formatMoney(num) {
                    const n = Number(num || 0);
                    return n.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                }

                function applyPosAmountVisibility() {
                    if (!posAmtWrap || !modalPos) return;
                    var hasMachine = !!modalPosMachine.value;
                    posAmtWrap.style.display = hasMachine ? '' : 'none';
                    if (!hasMachine) {
                        modalPos.value = '';
                        // keep hidden field in sync
                        hiddenPos.value = '';
                        hiddenPos.dispatchEvent(new Event('input'));
                    }
                }

                modalPosMachine.addEventListener('change', function () {
                    updatePosMachineNote();
                    applyPosAmountVisibility();
                });

                if (modalPos) {
                    modalPos.addEventListener('input', function () {
                        const raw = String(modalPos.value || '').replace(/[^0-9.,]/g, '');
                        modalPos.value = raw;
                        const num = parseMoney(raw);
                        hiddenPos.value = (raw.trim() === '' ? '' : num.toFixed(2));
                        hiddenPos.dispatchEvent(new Event('input'));
                    });
                    modalPos.addEventListener('blur', function () {
                        const num = parseMoney(modalPos.value);
                        modalPos.value = (modalPos.value || '').trim() === '' ? '' : formatMoney(num);
                    });
                }

                // initialize
                updatePosMachineNote();
                applyPosAmountVisibility();
            }

            // Bank helper (UI only)
            if (modalBankAccount) {
                var bankAmtWrap = document.getElementById('bank_amount_paid_modal_wrap');

                function updateBankNote() {
                    if (!modalBankAccountNote) return;
                    var opt = modalBankAccount.options[modalBankAccount.selectedIndex];
                    var bank = opt?.getAttribute('data-bank') || '';
                    var acctNo = opt?.getAttribute('data-account-number') || '';
                    var acctName = opt?.getAttribute('data-account-name') || '';
                    if (!modalBankAccount.value) {
                        modalBankAccountNote.textContent = '';
                        return;
                    }
                    modalBankAccountNote.textContent =
                        (bank ? bank : 'Bank') +
                        (acctNo ? ' • ' + acctNo : '') +
                        (acctName ? ' • ' + acctName : '');
                }

                function applyBankAmountVisibility() {
                    if (!bankAmtWrap || !modalBankAmount) return;
                    var hasBank = !!modalBankAccount.value;
                    bankAmtWrap.style.display = hasBank ? '' : 'none';
                    if (!hasBank) {
                        modalBankAmount.value = '';
                        hiddenBankAmt.value = '';
                        hiddenBankAmt.dispatchEvent(new Event('input'));
                    }
                }

                modalBankAccount.addEventListener('change', function () {
                    updateBankNote();
                    applyBankAmountVisibility();
                });

                if (modalBankAmount) {
                    modalBankAmount.addEventListener('input', function () {
                        const raw = String(modalBankAmount.value || '').replace(/[^0-9.,]/g, '');
                        modalBankAmount.value = raw;
                        const num = (function (str) {
                            let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                            const firstDot = cleaned.indexOf('.');
                            if (firstDot !== -1) {
                                cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                            }
                            const n = Number(cleaned);
                            return Number.isFinite(n) ? n : 0;
                        })(raw);
                        hiddenBankAmt.value = (raw.trim() === '' ? '' : num.toFixed(2));
                        hiddenBankAmt.dispatchEvent(new Event('input'));
                    });
                    modalBankAmount.addEventListener('blur', function () {
                        const num = (function (str) {
                            let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                            const firstDot = cleaned.indexOf('.');
                            if (firstDot !== -1) {
                                cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                            }
                            const n = Number(cleaned);
                            return Number.isFinite(n) ? n : 0;
                        })(modalBankAmount.value);
                        modalBankAmount.value = (modalBankAmount.value || '').trim() === '' ? '' : num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    });
                }

                // initialize
                updateBankNote();
                applyBankAmountVisibility();
            }

            // Initialize summary on load
            updateSummary();

            // Coupon events (in modal)
            if (modalCoupon && mainCoupon) {
                modalCoupon.addEventListener('input', function() {
                    mainCoupon.value = modalCoupon.value || '';
                });
                modalCoupon.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        modalCouponBtn?.click();
                    }
                });
            }

            if (modalCouponBtn && mainCouponBtn && mainCoupon && modalCoupon && modalCouponFeedback && mainCouponFeedback) {
                modalCouponBtn.addEventListener('click', function() {
                    mainCoupon.value = modalCoupon.value || '';
                    mainCouponBtn.click();
                    setTimeout(function() {
                        modalCouponFeedback.innerHTML = mainCouponFeedback.innerHTML || '';
                    }, 50);
                });
            }
        })();

        // Payment method wallet balance display
        (function() {
            var paymentSelect = document.querySelector('select[name="payment_method"]');
            var splitToggle = document.getElementById('split-payment-toggle');
            var splitFields = document.getElementById('split-payment-fields');
            var wrap = document.getElementById('invoice-wallet-balance-wrap');
            var ownerLabel = document.getElementById('invoice-wallet-owner-label');
            var balanceEl = document.getElementById('invoice-wallet-balance');
            var noteEl = document.getElementById('invoice-wallet-balance-note');
            var dpbvWrap = document.getElementById('invoice-dpbv-balance-wrap');
            var dpbvOwnerLabel = document.getElementById('invoice-dpbv-owner-label');
            var dpbvTotalEl = document.getElementById('invoice-dpbv-total');
            var dpbvNairaEl = document.getElementById('invoice-dpbv-naira');
            var dpbvNoteEl = document.getElementById('invoice-dpbv-balance-note');
            var kdWrap = document.getElementById('invoice-kedi-credit-wrap');
            var kdOwnerLabel = document.getElementById('invoice-kedi-credit-owner-label');
            var kdBalanceEl = document.getElementById('invoice-kedi-credit-balance');
            var kdNoteEl = document.getElementById('invoice-kedi-credit-note');
            var splitWalletAvailEl = document.getElementById('split-wallet-available');
            var splitKdAvailEl = document.getElementById('split-kd-credit-available');
            var splitDpbvTotalEl = document.getElementById('split-dpbv-total');
            var splitDpbvNairaEl = document.getElementById('split-dpbv-naira');
            var useScToggle = document.getElementById('use-sc-referral-toggle');
            var scInput = document.getElementById('invoice_sc_referral_code');

            if (!paymentSelect || !wrap || !ownerLabel || !balanceEl) return;

            var myName = @json($user->name);
            var myWallet = parseFloat(@json((float) ($user->wallet_balance ?? 0))) || 0;
            var myKediCredit = parseFloat(@json((float) ($user->kedi_credit_balance ?? 0))) || 0;

            function fmt(num) {
                var n = parseFloat(num || 0);
                return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function currentInvoiceTotal() {
                // products mode
                if (useProductsToggle && useProductsToggle.checked) {
                    var t = document.getElementById('product-total')?.textContent || '0';
                    return parseFloat(String(t).replace(/,/g, '')) || 0;
                }
                // manual mode
                var mt = document.getElementById('total-display')?.value || '0';
                return parseFloat(String(mt).replace(/,/g, '')) || 0;
            }

            function setWalletDisplay(name, amount, note) {
                ownerLabel.textContent = name || '—';
                balanceEl.textContent = fmt(amount);
                if (noteEl) noteEl.textContent = note || '';
                if (splitWalletAvailEl) splitWalletAvailEl.textContent = fmt(amount);
                var modalSplitWalletAvailEl = document.getElementById('split-wallet-available-modal');
                if (modalSplitWalletAvailEl) modalSplitWalletAvailEl.textContent = fmt(amount);

                // Show/hide modal wallet input based on available balance
                var wrap = document.getElementById('split_wallet_amount_modal_wrap');
                var modalInp = document.getElementById('split_wallet_amount_modal');
                var has = (Number(amount || 0) > 0);
                if (wrap) wrap.style.display = has ? '' : 'none';
                if (modalInp) modalInp.disabled = !has;
                if (!has && modalInp) modalInp.value = '';
            }

            function setDpbvDisplay(name, dpbv, naira, note, ok) {
                if (!dpbvWrap || !dpbvTotalEl || !dpbvNairaEl || !dpbvOwnerLabel) return;
                dpbvOwnerLabel.textContent = name || 'Service Center';
                dpbvTotalEl.textContent = fmt(dpbv);
                dpbvNairaEl.textContent = fmt(naira);
                if (splitDpbvTotalEl) splitDpbvTotalEl.textContent = fmt(dpbv);
                if (splitDpbvNairaEl) splitDpbvNairaEl.textContent = fmt(naira);
                var modalSplitDpbvTotalEl = document.getElementById('split-dpbv-total-modal');
                var modalSplitDpbvNairaEl = document.getElementById('split-dpbv-naira-modal');
                if (modalSplitDpbvTotalEl) modalSplitDpbvTotalEl.textContent = fmt(dpbv);
                if (modalSplitDpbvNairaEl) modalSplitDpbvNairaEl.textContent = fmt(naira);
                if (dpbvNoteEl) {
                    dpbvNoteEl.className = 'small mt-1 ' + (ok ? 'text-success' : 'text-danger');
                    dpbvNoteEl.textContent = note || '';
                }
            }

            function setKediCreditDisplay(name, amount, note, ok) {
                if (!kdWrap || !kdOwnerLabel || !kdBalanceEl) return;
                kdOwnerLabel.textContent = name || '—';
                kdBalanceEl.textContent = fmt(amount);
                if (kdNoteEl) {
                    kdNoteEl.className = 'small mt-1 ' + (ok ? 'text-success' : 'text-danger');
                    kdNoteEl.textContent = note || '';
                }
                if (splitKdAvailEl) splitKdAvailEl.textContent = fmt(amount);
                var modalSplitKdAvailEl = document.getElementById('split-kd-credit-available-modal');
                if (modalSplitKdAvailEl) modalSplitKdAvailEl.textContent = fmt(amount);

                // Show/hide modal credit input based on available balance
                var wrap = document.getElementById('split_kd_credit_amount_modal_wrap');
                var modalInp = document.getElementById('split_kd_credit_amount_modal');
                var has = (Number(amount || 0) > 0);
                // Don't wipe the user's typing on slow network / reloads.
                var isEditing = !!(modalInp && (document.activeElement === modalInp));
                var hasUserValue = !!(modalInp && String(modalInp.value || '').trim() !== '');
                if (wrap) wrap.style.display = (has || isEditing || hasUserValue) ? '' : 'none';
                if (modalInp) modalInp.disabled = (!has && !isEditing && !hasUserValue);
                if (!has && modalInp && !isEditing && !hasUserValue) modalInp.value = '';
            }

            var scFetchTimer = null;
            function scheduleScFetch() {
                if (scFetchTimer) clearTimeout(scFetchTimer);
                scFetchTimer = setTimeout(function() {
                    loadScWalletIfNeeded();
                    loadScDpbvIfNeeded();
                    loadScKediCreditIfNeeded();
                }, 650);
            }

            async function loadScWalletIfNeeded() {
                var useSc = !!(useScToggle && useScToggle.checked);
                var code = (scInput ? scInput.value : '').trim();

                if (!useSc || !code) {
                    setWalletDisplay(myName, myWallet, '');
                    return;
                }

                if (noteEl) noteEl.textContent = 'Loading Service Center wallet...';
                try {
                    var res = await fetch('{{ route("service-center.balances") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ code: code })
                    });
                    var data = await res.json();
                    if (data && data.valid) {
                        setWalletDisplay((data.name || code) + ' (Service Center)', data.wallet_balance || 0, '');
                    } else {
                        setWalletDisplay(myName, myWallet, 'Service Center code is invalid — showing your wallet instead.');
                    }
                } catch (e) {
                    setWalletDisplay(myName, myWallet, 'Could not load Service Center wallet — showing your wallet instead.');
                }
            }

            async function loadScDpbvIfNeeded() {
                if (!dpbvWrap) return;
                var useSc = !!(useScToggle && useScToggle.checked);
                var code = (scInput ? scInput.value : '').trim();

                if (!useSc || !code) {
                    setDpbvDisplay('Service Center', 0, 0, 'Enter Service Center Referral Code to view DPBV.', false);
                    return;
                }

                setDpbvDisplay('Service Center', 0, 0, 'Loading Service Center DPBV...', true);
                try {
                    var res = await fetch('{{ route("service-center.balances") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ code: code })
                    });
                    var data = await res.json();
                    if (!data || !data.valid) {
                        setDpbvDisplay('Service Center', 0, 0, (data?.message || 'Invalid Service Center code.'), false);
                        return;
                    }

                    var total = currentInvoiceTotal();
                    var naira = parseFloat(data.dpbv_naira || 0) || 0;
                    var ok = (Math.round(naira * 100) >= Math.round(total * 100));
                    setDpbvDisplay(
                        (data.name || code) + ' (Service Center)',
                        parseFloat(data.dpbv || 0) || 0,
                        naira,
                        ok ? 'Enough DPBV to pay this invoice.' : ('Not enough DPBV. Need ₦' + fmt(total) + ' total.'),
                        ok
                    );
                } catch (e) {
                    setDpbvDisplay('Service Center', 0, 0, 'Network error loading DPBV.', false);
                }
            }

            async function loadScKediCreditIfNeeded() {
                if (!kdWrap) return;
                var useSc = !!(useScToggle && useScToggle.checked);
                var code = (scInput ? scInput.value : '').trim();

                if (!useSc || !code) {
                    var total = currentInvoiceTotal();
                    var okSelf = (Math.round(myKediCredit * 100) >= Math.round(total * 100));
                    setKediCreditDisplay(myName, myKediCredit, okSelf ? 'Enough Kedi Credit to pay this invoice.' : ('Not enough Kedi Credit. Need ₦' + fmt(total) + ' total.'), okSelf);
                    return;
                }

                setKediCreditDisplay('Service Center', 0, 'Loading Service Center Kedi Credit...', true);
                try {
                    var res = await fetch('{{ route("service-center.balances") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ code: code })
                    });
                    var data = await res.json();
                    if (!data || !data.valid) {
                        setKediCreditDisplay('Service Center', 0, (data?.message || 'Invalid Service Center code.'), false);
                        return;
                    }

                    var total = currentInvoiceTotal();
                    var bal = parseFloat(data.kedi_credit_balance || 0) || 0;
                    var ok = (Math.round(bal * 100) >= Math.round(total * 100));
                    setKediCreditDisplay(
                        (data.name || code) + ' (Service Center)',
                        bal,
                        ok ? 'Enough Kedi Credit to pay this invoice.' : ('Not enough Kedi Credit. Need ₦' + fmt(total) + ' total.'),
                        ok
                    );
                } catch (e) {
                    setKediCreditDisplay('Service Center', 0, 'Network error loading Kedi Credit.', false);
                }
            }

            function applyVisibility() {
                var split = !!(splitToggle && splitToggle.checked);
                if (splitFields) splitFields.style.display = split ? '' : 'none';
                paymentSelect.disabled = split;
                if (split) {
                    wrap.style.display = 'none';
                    if (dpbvWrap) dpbvWrap.style.display = 'none';
                    if (kdWrap) kdWrap.style.display = 'none';
                    // Still load balances so split section can show them
                    loadScWalletIfNeeded();
                    loadScDpbvIfNeeded();
                    loadScKediCreditIfNeeded();
                    return;
                }

                var isWallet = (paymentSelect.value === 'wallet');
                var isDpbv = (paymentSelect.value === 'dpbv');
                var isKdCredit = (paymentSelect.value === 'kd_credit');
                wrap.style.display = isWallet ? '' : 'none';
                if (dpbvWrap) dpbvWrap.style.display = isDpbv ? '' : 'none';
                if (kdWrap) kdWrap.style.display = isKdCredit ? '' : 'none';
                if (isWallet) loadScWalletIfNeeded();
                if (isDpbv) loadScDpbvIfNeeded();
                if (isKdCredit) loadScKediCreditIfNeeded();
            }

            paymentSelect.addEventListener('change', applyVisibility);
            if (splitToggle) splitToggle.addEventListener('change', applyVisibility);
            if (useScToggle) useScToggle.addEventListener('change', function() {
                if (paymentSelect.value === 'wallet') loadScWalletIfNeeded();
                if (paymentSelect.value === 'dpbv') loadScDpbvIfNeeded();
                if (paymentSelect.value === 'kd_credit') loadScKediCreditIfNeeded();
            });
            if (scInput) scInput.addEventListener('input', function() {
                if (paymentSelect.value === 'wallet' || paymentSelect.value === 'dpbv' || paymentSelect.value === 'kd_credit') scheduleScFetch();
            });

            // Re-check sufficiency when totals change
            var productTotalEl = document.getElementById('product-total');
            var totalDisplayEl = document.getElementById('total-display');
            var taxInputEl = document.getElementById('tax-input');
            var discountInputEl = document.getElementById('discount-input');
            var taxInputManualEl = document.getElementById('tax-input-manual');
            var discountInputManualEl = document.getElementById('discount-input-manual');
            var couponCheckBtn = document.getElementById('invoice_coupon_check_btn');

            function onTotalChange() {
                if (paymentSelect.value === 'dpbv') loadScDpbvIfNeeded();
                if (paymentSelect.value === 'kd_credit') loadScKediCreditIfNeeded();
            }

            if (productTable) {
                productTable.addEventListener('input', onTotalChange);
            }
            if (taxInputEl) taxInputEl.addEventListener('input', onTotalChange);
            if (discountInputEl) discountInputEl.addEventListener('input', onTotalChange);
            if (taxInputManualEl) taxInputManualEl.addEventListener('input', onTotalChange);
            if (discountInputManualEl) discountInputManualEl.addEventListener('input', onTotalChange);
            if (couponCheckBtn) couponCheckBtn.addEventListener('click', function() {
                setTimeout(onTotalChange, 50);
            });

            applyVisibility();
        })();

        // Split payment helper
        (function() {
            var splitToggle = document.getElementById('split-payment-toggle');
            var fields = document.getElementById('split-payment-fields');
            if (!splitToggle || !fields) return;

            var wallet = document.querySelector('input[name="split_wallet_amount"]');
            var kd = document.querySelector('input[name="split_kd_credit_amount"]');
            var cash = document.querySelector('input[name="split_cash_amount"]');

            var dueEl = document.getElementById('split-total-due');
            var enteredEl = document.getElementById('split-total-entered');
            var remainingEl = document.getElementById('split-remaining');
            var dueModalEl = document.getElementById('split-total-due-modal');
            var enteredModalEl = document.getElementById('split-total-entered-modal');
            var remainingModalEl = document.getElementById('split-remaining-modal');

            function parseNum(v) { return parseFloat(v || 0) || 0; }
            function fmt(n) { return (parseFloat(n || 0) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
            function parseMoneyText(v) { return parseFloat(String(v || '').replace(/,/g, '')) || 0; }

            function currentTotal() {
                // Compute from inputs so "Total due" is correct even if display totals are stale.
                var subtotal = 0;
                var tax = 0;
                var discount = 0;

                if (useProductsToggle && useProductsToggle.checked && productTable) {
                    productTable.querySelectorAll('.product-row').forEach(function(row) {
                        var qty = parseFloat(row.querySelector('.product-qty')?.value) || 0;
                        var price = parseFloat(row.querySelector('.product-qty')?.getAttribute('data-unit-price')) || 0;
                        subtotal += qty * price;
                    });
                    tax = parseFloat(document.getElementById('tax-input')?.value || 0) || 0;
                    discount = parseFloat(document.getElementById('discount-input')?.value || 0) || 0;
                } else {
                    (tbody ? tbody.querySelectorAll('.invoice-item-row') : []).forEach(function(row) {
                        var name = (row.querySelector('input[name*="[item_name]"]')?.value || '').trim();
                        if (name === '') return;
                        var qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
                        var price = parseFloat(row.querySelector('.item-price')?.value) || 0;
                        subtotal += qty * price;
                    });
                    tax = parseFloat(document.getElementById('tax-input-manual')?.value || 0) || 0;
                    discount = parseFloat(document.getElementById('discount-input-manual')?.value || 0) || 0;
                }

                var couponAmt = (coupon && coupon.percentage > 0) ? ((coupon.percentage / 100) * subtotal) : 0;
                return (subtotal + tax - discount - couponAmt);
            }

            function recalc() {
                var due = currentTotal();
                var posModal = document.getElementById('pos_amount_paid_modal');
                var bankModal = document.getElementById('bank_amount_paid_modal');
                var posAmt = parseMoneyText(posModal?.value);
                var bankAmt = parseMoneyText(bankModal?.value);

                // Prefer modal inputs when present (they are formatted with commas)
                var modalWallet = document.getElementById('split_wallet_amount_modal');
                var modalKd = document.getElementById('split_kd_credit_amount_modal');
                var modalCash = document.getElementById('split_cash_amount_modal');

                var walletAmt = (modalWallet && (modalWallet.value || '').trim() !== '') ? parseMoneyText(modalWallet.value) : parseNum(wallet?.value);
                var kdAmt = (modalKd && (modalKd.value || '').trim() !== '') ? parseMoneyText(modalKd.value) : parseNum(kd?.value);
                var cashAmt = (modalCash && (modalCash.value || '').trim() !== '') ? parseMoneyText(modalCash.value) : parseNum(cash?.value);

                var sum = walletAmt + kdAmt + cashAmt + posAmt + bankAmt;
                var rem = due - sum;
                if (dueEl) dueEl.textContent = fmt(due);
                if (enteredEl) enteredEl.textContent = fmt(sum);
                if (remainingEl) remainingEl.textContent = fmt(rem);
                if (dueModalEl) dueModalEl.textContent = fmt(due);
                if (enteredModalEl) enteredModalEl.textContent = fmt(sum);
                if (remainingModalEl) remainingModalEl.textContent = fmt(rem);
            }

            [wallet, kd, cash].forEach(function(inp) {
                if (inp) inp.addEventListener('input', recalc);
            });
            ['pos_amount_paid_modal', 'bank_amount_paid_modal'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', recalc);
            });
            ['split_wallet_amount_modal', 'split_kd_credit_amount_modal', 'split_cash_amount_modal'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', recalc);
            });

            // when totals change
            document.addEventListener('input', function(e) {
                if (!splitToggle.checked) return;
                if (!e.target) return;
                if (e.target.id === 'tax-input' || e.target.id === 'discount-input' || e.target.classList?.contains('product-qty') || e.target.id === 'tax-input-manual' || e.target.id === 'discount-input-manual') {
                    recalc();
                }
            });

            // initial
            setTimeout(recalc, 50);
            splitToggle.addEventListener('change', function() {
                if (splitToggle.checked) setTimeout(recalc, 50);
            });
        })();
    })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
