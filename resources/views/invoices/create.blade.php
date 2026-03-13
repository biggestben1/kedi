<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Invoice – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
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
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-invoices" aria-controls="navbarSupportedContent-invoices" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-invoices">
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
                                <a class="side-menu__item" href="{{ url('/') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
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
                            <h1 class="page-title">Create Invoice</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">My Invoices</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Create Invoice</li>
                                </ol>
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
                            <div class="card-header"><h3 class="card-title">New Invoice</h3></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
                                    @csrf

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label">Invoice Number</label>
                                            <p class="form-control-plaintext text-muted mb-0 small">Auto-generated when you save</p>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                            @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Due Date</label>
                                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                                            @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                            <select name="status" class="form-select" required>
                                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="sent" {{ old('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                                                <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="overdue" {{ old('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <input type="hidden" name="use_product_quantities" value="1">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Customer</label>
                                            <p class="form-control-plaintext mb-0">{{ $user->name }} ({{ $user->email }})</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Customer Name</label>
                                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $user->name) }}">
                                            @error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Customer Email</label>
                                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $user->email) }}">
                                            @error('customer_email')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Phone</label>
                                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $user->phone) }}">
                                            @error('customer_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Address</label>
                                            <textarea name="customer_address" class="form-control" rows="2">{{ old('customer_address') }}</textarea>
                                            @error('customer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="use-products-toggle" checked>
                                            <label class="form-check-label" for="use-products-toggle">Use products from catalog</label>
                                        </div>
                                    </div>

                                    <div id="products-section">
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
                                                    @foreach($products as $product)
                                                        @php
                                                            $unitPrice = $product->getPriceForUser($user);
                                                            $pv = (float) ($product->pv ?? 0);
                                                            $bv = (float) ($product->bv ?? 0);
                                                        @endphp
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
                                                    @endforeach
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
                                                        <td colspan="4" class="text-end"><label class="mb-0">Discount:</label></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input" style="width:100px; margin-left: auto;"></td>
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

                                    <div id="manual-items-section" style="display: none;">
                                        <label class="form-label">Items <span class="text-danger">*</span></label>
                                        <div class="table-responsive mb-2">
                                            <table class="table table-bordered" id="invoice-items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Item Name</th>
                                                        <th>Description</th>
                                                        <th style="width:100px">Quantity</th>
                                                        <th style="width:80px">Unit</th>
                                                        <th style="width:120px">Unit Price</th>
                                                        <th style="width:120px">Line Total</th>
                                                        <th style="width:80px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invoice-items-tbody">
                                                    <tr class="invoice-item-row">
                                                        <td><input type="text" name="items[0][item_name]" class="form-control form-control-sm" required></td>
                                                        <td><input type="text" name="items[0][description]" class="form-control form-control-sm"></td>
                                                        <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>
                                                        <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                                                        <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                                                        <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                                                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                        <td><input type="text" class="form-control form-control-sm" id="subtotal-display" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end"><label class="mb-0">Tax:</label></td>
                                                        <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input-manual"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end"><label class="mb-0">Discount:</label></td>
                                                        <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input-manual"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end"><strong>Total:</strong></td>
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

        useProductsToggle.addEventListener('change', function() {
            if (this.checked) {
                productsSection.style.display = 'block';
                manualItemsSection.style.display = 'none';
                useProductQuantitiesInput.value = '1';
                setManualItemsRequired(false);
            } else {
                productsSection.style.display = 'none';
                manualItemsSection.style.display = 'block';
                useProductQuantitiesInput.value = '0';
                setManualItemsRequired(true);
            }
        });

        setManualItemsRequired(false);

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
                var total = subtotal + tax - discount;
                document.getElementById('product-subtotal').textContent = formatPrice(subtotal);
                document.getElementById('product-subtotal-pv').textContent = formatPvBv(subtotalPv);
                document.getElementById('product-subtotal-bv').textContent = formatPvBv(subtotalBv);
                document.getElementById('product-total').textContent = formatPrice(total);
                document.getElementById('product-total-pv').textContent = formatPvBv(subtotalPv);
                document.getElementById('product-total-bv').textContent = formatPvBv(subtotalBv);
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
                    <td><input type="text" name="items[__INDEX__][item_name]" class="form-control form-control-sm" required></td>
                    <td><input type="text" name="items[__INDEX__][description]" class="form-control form-control-sm"></td>
                    <td><input type="number" name="items[__INDEX__][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>
                    <td><input type="text" name="items[__INDEX__][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                    <td><input type="number" name="items[__INDEX__][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                    <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                </tr>
            `;

            function updateTotals() {
                var subtotal = 0;
                document.querySelectorAll('.invoice-item-row').forEach(function(row) {
                    var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                    var price = parseFloat(row.querySelector('.item-price').value) || 0;
                    var lineTotal = qty * price;
                    row.querySelector('.line-total').value = formatPrice(lineTotal);
                    subtotal += lineTotal;
                });
                var tax = parseFloat(document.getElementById('tax-input-manual').value) || 0;
                var discount = parseFloat(document.getElementById('discount-input-manual').value) || 0;
                var total = subtotal + tax - discount;
                document.getElementById('subtotal-display').value = formatPrice(subtotal);
                document.getElementById('total-display').value = formatPrice(total);
            }

            document.getElementById('add-row').addEventListener('click', function() {
                var newRow = template.content.cloneNode(true);
                var html = newRow.innerHTML.replace(/__INDEX__/g, rowIndex++);
                var tr = document.createElement('tr');
                tr.className = 'invoice-item-row';
                tr.innerHTML = html;
                tbody.appendChild(tr);
                tr.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
                    input.addEventListener('input', updateTotals);
                });
                tr.querySelector('.remove-row').addEventListener('click', function() {
                    tr.remove();
                    updateTotals();
                });
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
    })();
    </script>
</body>
</html>
