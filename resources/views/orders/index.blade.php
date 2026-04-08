<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Orders – {{ config('app.name') }}</title>
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
        .table .col-items, .table .col-total { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .table .col-total { min-width: 100px; }
        /* Sidebar: fixed height and scrollable menu */
        .app-sidebar {
            height: 100vh;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        .app-sidebar .side-header {
            flex-shrink: 0;
        }
        .app-sidebar .main-sidemenu {
            flex: 1;
            min-height: 0;
            overflow-y: auto !important;
            overflow-x: hidden;
        }
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
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-orders" aria-controls="navbarSupportedContent-orders" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-orders">
                                    <div class="d-flex order-lg-2">
                                        <a class="nav-link icon text-center" href="{{ url('/') }}">
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
                                                <a class="dropdown-item" href="{{ route('dpbv.index') }}"><i class="dropdown-icon fe fe-award"></i> My DPBV</a>
                                                <a class="dropdown-item" href="{{ route('promo.index') }}"><i class="dropdown-icon fe fe-gift"></i> My Promo</a>
                                                <a class="dropdown-item" href="{{ route('bonus.index') }}"><i class="dropdown-icon fe fe-trending-up"></i> My Bonus</a>
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                                                @php
                                                    $ordRole = auth()->user()->role?->name;
                                                    $ordAdminLabel = match($ordRole) {
                                                        'reseller' => 'Reseller',
                                                        'accountant' => 'Accountant Panel',
                                                        'dispatch' => 'Dispatch Panel',
                                                        'headquarters' => 'Admin Dashboard',
                                                        'branch' => 'Branch Admin',
                                                        'service_center' => 'Service Center Admin',
                                                        default => 'Admin',
                                                    };
                                                @endphp
                                                <a class="dropdown-item" href="{{ $ordRole === 'headquarters' ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ $ordAdminLabel }}</a>
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
                                <a class="side-menu__item active" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
                            </li>
                            @if(auth()->user()->role?->name === 'service_center')
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referral Orders</span></a>
                            </li>
                            @endif
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dpbv.index') }}"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">My DPBV</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                            @php
                                $ordSideRole = auth()->user()->role?->name;
                                $ordSideAdminLabel = match($ordSideRole) {
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
                                <a class="side-menu__item" href="{{ in_array($ordSideRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $ordSideAdminLabel }}</span></a>
                            </li>
                            @endif
                            <li class="sub-category"><h3>Account</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-user"></i><span class="side-menu__label">Profile</span></a>
                            </li>
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
                            <h1 class="page-title">My Orders</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">My Orders</li>
                                </ol>
                            </div>
                        </div>

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

                        @if(session('added_items') && count(session('added_items', [])) > 0)
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success-transparent">
                                <h5 class="card-title mb-0 text-success"><i class="fe fe-package me-1"></i>Items for supply</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Product Name</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(session('added_items', []) as $row)
                                            <tr>
                                                <td><code>{{ $row['item_code'] }}</code></td>
                                                <td>{{ $row['product_name'] }}</td>
                                                <td class="text-end">{{ $row['quantity'] }}</td>
                                                <td class="text-end">₦{{ number_format($row['unit_price'] ?? 0, 0) }}</td>
                                                <td class="text-end">₦{{ number_format($row['line_total'] ?? 0, 0) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Total</strong></td>
                                                <td class="text-end"><strong>₦{{ number_format(collect(session('added_items', []))->sum('line_total'), 0) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('invoices.create') }}" class="btn btn-success"><i class="fe fe-file-text me-1"></i>Create Invoice</a>
                                    <a href="{{ route('orders.added-items.pdf') }}" class="btn btn-outline-danger" target="_blank"><i class="fe fe-download me-1"></i>Create PDF</a>
                                    <form action="{{ route('orders.added-items.clear') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary"><i class="fe fe-trash-2 me-1"></i>Clear</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(($statusFilter ?? '') === 'draft' && !$orders->isEmpty())
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <p class="mb-0"><strong>Total of all drafts:</strong> <span class="text-primary fs-4">₦{{ number_format($draftTotal ?? 0, 0) }}</span></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-0"><strong>Balance in wallet:</strong> <span class="text-success fs-4">₦{{ number_format($walletBalance ?? 0, 0) }}</span></p>
                                        <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-outline-primary mt-1">Top up wallet</a>
                                    </div>
                                    @if(($walletBalance ?? 0) >= ($draftTotal ?? 0) && ($draftTotal ?? 0) > 0)
                                    <div class="col-md-3 text-end">
                                        <form action="{{ route('orders.place-all-drafts-wallet') }}" method="POST" class="d-inline" onsubmit="return confirm('Place ALL {{ $draftCount }} draft(s) and deduct ₦{{ number_format($draftTotal ?? 0, 0) }} from wallet?');">
                                            @csrf
                                            <button type="submit" class="btn btn-success"><i class="fe fe-check-circle me-1"></i> Place All (Wallet)</button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h3 class="card-title mb-0">{{ ($statusFilter ?? '') === 'draft' ? 'Draft Orders' : 'Order History' }}</h3>
                                <div class="d-flex gap-2 align-items-center flex-wrap">
                                    @if(($statusFilter ?? '') !== 'draft')
                                    <form id="form-receipt-pdf" action="{{ route('orders.receipt.pdf.selected') }}" method="POST" class="d-inline-flex align-items-center" target="_blank">
                                        @csrf
                                        <input type="hidden" name="order_ids" id="input-order-ids" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center" id="btn-create-pdf" disabled><i class="fe fe-download me-1"></i>Create PDF (0 selected)</button>
                                    </form>
                                    <form id="form-export-csv" action="{{ route('orders.export.csv') }}" method="POST" class="d-inline-flex align-items-center" target="_blank">
                                        @csrf
                                        <input type="hidden" name="order_ids" id="input-order-ids-csv" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center" id="btn-download-csv" disabled><i class="fe fe-download me-1"></i>Download CSV (0 selected)</button>
                                    </form>
                                    <form id="form-add-supply" action="{{ route('orders.add-for-supply') }}" method="POST" class="d-inline-flex align-items-center">
                                        @csrf
                                        <input type="hidden" name="order_ids" id="input-order-ids-supply" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center" id="btn-add-supply" disabled><i class="fe fe-shopping-cart me-1"></i>Add for supply (0 selected)</button>
                                    </form>
                                    @endif
                                    @if(($statusFilter ?? '') === 'draft')
                                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fe fe-list me-1"></i> All Orders</a>
                                    @elseif(($draftCount ?? 0) > 0)
                                    <a href="{{ route('orders.index', ['status' => 'draft']) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-file-text me-1"></i> See All Drafts ({{ $draftCount }})</a>
                                    @endif
                                    <a href="{{ url('/') }}" class="btn btn-sm btn-primary"><i class="fe fe-shopping-bag me-1"></i> Back to Shop</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                @if($orders->isEmpty())
                                    <p class="text-muted p-4 mb-0">
                                        @if(($statusFilter ?? '') === 'draft')
                                            You have no draft orders. <a href="{{ route('orders.index') }}">View all orders</a> or <a href="{{ url('/') }}">start shopping</a>.
                                        @else
                                            You have not placed any orders yet. <a href="{{ url('/') }}">Browse products</a> and add items to your cart to checkout.
                                        @endif
                                    </p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    @if(($statusFilter ?? '') !== 'draft')
                                                    <th class="align-middle" style="width: 36px;"><input type="checkbox" id="select-all-orders" class="form-check-input" title="Select all"></th>
                                                    @endif
                                                    <th class="align-middle">Date</th>
                                                    <th class="align-middle">Tracking #</th>
                                                    <th class="align-middle">Status</th>
                                                    <th class="align-middle">Delivery</th>
                                                    <th class="align-middle col-items">Items</th>
                                                    <th class="align-middle col-total">Total</th>
                                                    <th class="align-middle"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($orders as $order)
                                                <tr>
                                                    @if(($statusFilter ?? '') !== 'draft')
                                                    <td class="align-middle">
                                                        @if($order->status !== 'draft')
                                                        <input type="checkbox" class="form-check-input js-order-checkbox" value="{{ $order->id }}" data-order-id="{{ $order->id }}">
                                                        @else
                                                        <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    @endif
                                                    <td class="align-middle">{{ $order->created_at->format('M d, Y H:i') }}</td>
                                                    <td class="align-middle"><code class="bg-light px-2 py-1 rounded">{{ $order->tracking_number }}</code></td>
                                                    <td class="align-middle">
                                                        @if($order->status === 'draft')
                                                            <span class="badge bg-secondary">Draft</span>
                                                        @elseif($order->status === 'completed' || $order->status === 'delivered')
                                                            <span class="badge bg-success">Delivered</span>
                                                        @elseif($order->status === 'shipped')
                                                            <span class="badge bg-warning text-dark">Shipped</span>
                                                        @elseif($order->status === 'packed')
                                                            <span class="badge bg-primary">Packed</span>
                                                        @elseif($order->status === 'paid')
                                                            <span class="badge bg-info">Paid</span>
                                                        @elseif($order->status === 'cancelled')
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        @else
                                                            <span class="badge bg-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        @if($order->status === 'draft')
                                                            <span class="badge bg-secondary">—</span>
                                                        @elseif($order->delivered_at)
                                                            <span class="badge bg-success">Delivered</span>
                                                        @else
                                                            <span class="badge bg-secondary">Not delivered</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle col-items">{{ $order->items->sum('quantity') }}</td>
                                                    <td class="align-middle col-total">₦{{ number_format($order->subtotal, 0) }}</td>
                                                    <td class="align-middle">
                                                        @if($order->status === 'draft')
                                                            <div class="d-flex gap-1 flex-wrap">
                                                                <button type="button" class="btn btn-sm btn-outline-secondary js-toggle-products" data-order-id="{{ $order->id }}"><i class="fe fe-list me-1"></i> Products</button>
                                                                @if(($walletBalance ?? 0) >= $order->subtotal)
                                                                <form action="{{ route('orders.place-draft-wallet', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Place order and deduct ₦{{ number_format($order->subtotal, 0) }} from wallet?');">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fe fe-credit-card me-1"></i> Place Order (Wallet)</button>
                                                                </form>
                                                                @endif
                                                                <form action="{{ route('orders.restore-draft', $order) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-primary">Complete</button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <div class="d-flex gap-1">
                                                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                                <a href="{{ route('orders.receipt.pdf', $order) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Download Receipt"><i class="fe fe-download"></i></a>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if($order->status === 'draft')
                                                <tr class="draft-products-row" id="products-{{ $order->id }}" style="display: none;">
                                                    <td colspan="{{ ($statusFilter ?? '') !== 'draft' ? 8 : 7 }}" class="bg-light p-3">
                                                        <div class="small">
                                                            <p class="mb-2"><strong>KD NO:</strong> {{ $order->kd_id ?? '—' }} &nbsp; <strong>Customer Name:</strong> {{ $order->customer_name ?? '—' }}</p>
                                                            <strong>Products in this draft:</strong>
                                                            <ul class="list-unstyled mb-0 mt-2">
                                                                @foreach($order->items as $item)
                                                                <li class="py-1 border-bottom border-light">
                                                                    <strong>{{ $item->item_code }}</strong> – {{ $item->product_name }} × {{ $item->quantity }} = ₦{{ number_format($item->line_total, 0) }}
                                                                </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            @if(!$orders->isEmpty() && $orders->hasPages())
                                <div class="card-footer">{{ $orders->links() }}</div>
                            @endif
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
    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
        document.querySelectorAll('.js-toggle-products').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-order-id');
                var row = document.getElementById('products-' + id);
                if (row) {
                    row.style.display = row.style.display === 'none' ? '' : 'none';
                    this.innerHTML = row.style.display === 'none' ? '<i class="fe fe-list me-1"></i> Products' : '<i class="fe fe-chevron-up me-1"></i> Hide';
                }
            });
        });

        (function() {
            var formPdf = document.getElementById('form-receipt-pdf');
            var formCsv = document.getElementById('form-export-csv');
            var formSupply = document.getElementById('form-add-supply');
            var btnPdf = document.getElementById('btn-create-pdf');
            var btnCsv = document.getElementById('btn-download-csv');
            var btnSupply = document.getElementById('btn-add-supply');
            var inputPdf = document.getElementById('input-order-ids');
            var inputCsv = document.getElementById('input-order-ids-csv');
            var inputSupply = document.getElementById('input-order-ids-supply');
            var selectAll = document.getElementById('select-all-orders');
            if (!formPdf || !inputPdf) return;

            function updateSelectionState() {
                var checked = document.querySelectorAll('.js-order-checkbox:checked');
                var ids = Array.from(checked).map(function(cb) { return cb.value; });
                var idsStr = ids.join(',');
                inputPdf.value = idsStr;
                if (inputCsv) inputCsv.value = idsStr;
                if (inputSupply) inputSupply.value = idsStr;
                if (btnPdf) {
                    btnPdf.disabled = ids.length === 0;
                    btnPdf.innerHTML = '<i class="fe fe-download me-1"></i>Create PDF (' + ids.length + ' selected)';
                }
                if (btnCsv) {
                    btnCsv.disabled = ids.length === 0;
                    btnCsv.innerHTML = '<i class="fe fe-download me-1"></i>Download CSV (' + ids.length + ' selected)';
                }
                if (btnSupply) {
                    btnSupply.disabled = ids.length === 0;
                    btnSupply.innerHTML = '<i class="fe fe-shopping-cart me-1"></i>Add for supply (' + ids.length + ' selected)';
                }
                if (selectAll) {
                    var all = document.querySelectorAll('.js-order-checkbox');
                    var allCount = all.length;
                    var checkedCount = checked.length;
                    selectAll.checked = allCount > 0 && checkedCount === allCount;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < allCount;
                }
            }

            document.querySelectorAll('.js-order-checkbox').forEach(function(cb) {
                cb.addEventListener('change', updateSelectionState);
            });
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.js-order-checkbox').forEach(function(cb) {
                        cb.checked = selectAll.checked;
                    });
                    updateSelectionState();
                });
            }
            updateSelectionState();
        })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
