<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Invoice {{ $invoice->invoice_number }} – {{ config('app.name') }}</title>
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

        #invoice-floating-totals {
            position: fixed;
            top: 88px;
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
                        <div class="navbar navbar-collapse responsive-navbar p-0">
                            <div class="d-flex order-lg-2">
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

        <div class="sticky">
            <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
            <div class="app-sidebar">
                <div class="side-header">
                    <a class="header-brand1" href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                    </a>
                </div>
                <div class="main-sidemenu">
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
                        <li class="sub-category"><h3>Account</h3></li>
                        <li class="slide">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content app-content mt-0">
            <div class="side-app">
                <div class="main-container container-fluid">
                    <div class="page-header">
                        <h1 class="page-title">Edit Invoice</h1>
                        <div>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">My Invoices</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h3 class="card-title mb-0">Invoice {{ $invoice->invoice_number }}</h3>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0)" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#customerModal">
                                    Customer
                                </a>
                                <a href="javascript:void(0)" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    Payment
                                </a>
                                <button type="submit" form="invoice-form" class="btn btn-primary">
                                    Update Invoice
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('invoices.update', $invoice) }}" id="invoice-form">
                                @csrf
                                @method('PUT')

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                                        @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="sent" {{ old('status', $invoice->status) === 'sent' ? 'selected' : '' }}>Sent</option>
                                            <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                            <option value="cancelled" {{ old('status', $invoice->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <input type="hidden" name="use_product_quantities" value="{{ old('use_product_quantities', 0) ? 1 : 0 }}">
                                <input type="hidden" name="use_sc_referral" id="use_sc_referral" value="{{ old('sc_referral_code', $invoice->sc_referral_code) ? 1 : 0 }}">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="use-sc-referral-toggle" {{ old('sc_referral_code', $invoice->sc_referral_code) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use-sc-referral-toggle">Use Service Center Referral Code (instead of Customer details)</label>
                                    </div>
                                    <small class="text-muted d-block">When enabled, customer fields will be hidden/disabled.</small>
                                </div>

                                <div id="invoice-customer-fields">
                                    <div class="alert alert-info mb-4">
                                        Customer details are taken automatically from your account unless you enable Service Center Referral Code.
                                    </div>
                                </div>

                                <div id="invoice-sc-referral-fields" class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Service Center Referral Code</label>
                                        <input type="text" name="sc_referral_code" id="invoice_sc_referral_code" class="form-control @error('sc_referral_code') is-invalid @enderror" value="{{ old('sc_referral_code', $invoice->sc_referral_code) }}" placeholder="Enter Service Center code">
                                        <div id="invoice_sc_referral_feedback" class="mt-1 small"></div>
                                        @error('sc_referral_code')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="use-products-toggle" {{ old('use_product_quantities', 0) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use-products-toggle">Use products from catalog</label>
                                    </div>
                                </div>

                                <div style="display:none;">
                                    <input type="number" step="0.01" min="0" name="pos_amount_paid" id="pos_amount_paid_hidden" value="{{ old('pos_amount_paid', $invoice->pos_amount_paid) }}">
                                    <input type="number" step="0.01" min="0" name="bank_amount_paid" id="bank_amount_paid_hidden" value="{{ old('bank_amount_paid') }}">
                                    <input type="checkbox" id="split-payment-toggle" name="split_payment" value="1" {{ old('split_payment') ? 'checked' : '' }}>
                                    <input type="number" step="0.01" min="0" name="split_wallet_amount" value="{{ old('split_wallet_amount', $invoice->payment_breakdown['wallet'] ?? 0) }}">
                                    <input type="number" step="0.01" min="0" name="split_kd_credit_amount" value="{{ old('split_kd_credit_amount', $invoice->payment_breakdown['kd_credit'] ?? 0) }}">
                                    <input type="number" step="0.01" min="0" name="split_cash_amount" value="{{ old('split_cash_amount', $invoice->payment_breakdown['cash'] ?? 0) }}">
                                    <input type="number" step="0.01" min="0" name="split_cheque_amount" value="{{ old('split_cheque_amount', $invoice->payment_breakdown['cheque'] ?? 0) }}">
                                </div>

                                <div id="products-section">
                                    <div id="invoice-floating-totals" aria-live="polite">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="label mb-0">Subtotal</div>
                                            <div class="value">₦<span id="invoice-float-subtotal">0.00</span></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="label mb-0">Total</div>
                                            <div class="value">₦<span id="invoice-float-total">0.00</span></div>
                                        </div>
                                    </div>

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
                                                <th style="width:120px" class="text-end">Line Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($products as $product)
                                                @php $unitPrice = $product->getPriceForUser($user); @endphp
                                                <tr class="product-row" data-unit-price="{{ $unitPrice }}" data-search="{{ strtolower($product->name . ' ' . ($product->pack_size ?? '') . ' ' . ($product->item_code ?? '')) }}">
                                                    <td>{{ $product->display_name }}</td>
                                                    <td>{{ $product->pack_size ?? 'pcs' }}</td>
                                                    <td class="text-end">₦{{ number_format($unitPrice, 2) }}</td>
                                                    <td>
                                                        <input type="number" name="product_quantities[{{ $product->id }}]" class="form-control form-control-sm product-qty" value="{{ old('product_quantities.'.$product->id, $prefillQuantities[$product->id] ?? 0) }}" min="0" step="1" data-unit-price="{{ $unitPrice }}">
                                                    </td>
                                                    <td class="text-end"><span class="product-line-total">0.00</span></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end"><strong id="product-subtotal">0.00</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end"><label class="mb-0">Tax:</label></td>
                                                <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', $invoice->tax ?? 0) }}" id="tax-input" style="width:100px; margin-left: auto;"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end"><label class="mb-0">Discount:</label></td>
                                                <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', $invoice->discount ?? 0) }}" id="discount-input" style="width:100px; margin-left: auto;"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                <td class="text-end"><strong id="product-total">0.00</strong></td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    @error('product_quantities')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div id="manual-items-section" style="display:none;">
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
                                                <th>Description</th>
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
                                            @foreach($invoice->items as $index => $item)
                                                <tr class="invoice-item-row">
                                                    <td><input type="text" name="items[{{ $index }}][item_name]" class="form-control form-control-sm" list="invoice-products-datalist" autocomplete="off" value="{{ old("items.{$index}.item_name", $item->item_name) }}"></td>
                                                    <td><input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm" value="{{ old("items.{$index}.description", $item->description) }}"></td>
                                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm item-qty" step="1" min="1" value="{{ old("items.{$index}.quantity", $item->quantity) }}" required></td>
                                                    <td><input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm" value="{{ old("items.{$index}.unit", $item->unit) }}" placeholder="pcs"></td>
                                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="{{ old("items.{$index}.unit_price", $item->unit_price) }}" required></td>
                                                    <td class="text-end">
                                                        <small class="text-muted d-block">Unit: <span class="manual-unit-pv">0.0</span></small>
                                                        <span class="manual-line-pv">0.0</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small class="text-muted d-block">Unit: <span class="manual-unit-bv">0.0</span></small>
                                                        <span class="manual-line-bv">0.0</span>
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm line-total" readonly value="{{ number_format($item->line_total, 2) }}"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end"><strong id="manual-subtotal-pv">0.0</strong></td>
                                                <td class="text-end"><strong id="manual-subtotal-bv">0.0</strong></td>
                                                <td><input type="text" class="form-control form-control-sm" id="subtotal-display" readonly value="{{ number_format($invoice->subtotal ?? 0, 2) }}"></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="7" class="text-end"><label class="mb-0">Tax:</label></td>
                                                <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', $invoice->tax ?? 0) }}" id="tax-input-manual"></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="7" class="text-end"><label class="mb-0">Discount:</label></td>
                                                <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', $invoice->discount ?? 0) }}" id="discount-input-manual"></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                                <td class="text-end"><strong id="manual-total-pv">0.0</strong></td>
                                                <td class="text-end"><strong id="manual-total-bv">0.0</strong></td>
                                                <td><input type="text" class="form-control form-control-sm" id="total-display" readonly value="{{ number_format($invoice->total ?? 0, 2) }}"></td>
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
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                                    @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>

                                <hr class="my-4">
                                <button type="submit" class="btn btn-primary">Update Invoice</button>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary">Cancel</a>
                            </form>
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
                                    <p class="text-muted small mb-2">Enter the Service Center <strong>code or name</strong> in the main form to load balances for split payments.</p>
                                    <div class="alert alert-info mb-0">
                                        Field: <strong>Service Center Referral Code (code or name)</strong>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
                                                DPBV Available: <strong><span id="split-dpbv-total-modal">0.00</span> DPBV</strong> (₦<span id="split-dpbv-naira-modal">0.00</span>)
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted fw-semibold" style="font-size: 15px;">
                                                Total due: ₦<span id="split-total-due-modal">0.00</span> • Split total: ₦<span id="split-total-entered-modal">0.00</span> • Remaining: ₦<span id="split-remaining-modal">0.00</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="split_payment_modal">
                                                <label class="form-check-label" for="split_payment_modal">Split payment (Wallet + Credit + Cash + Cheque)</label>
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
                                                <div class="col-md-3">
                                                    <label class="form-label">Cheque Amount</label>
                                                    <input type="text" inputmode="decimal" id="split_cheque_amount_modal" class="form-control" value="" placeholder="0.00">
                                                </div>
                                                <div class="col-md-6" id="split_pos_bank_modal_wrap">
                                                    <div class="mb-2">
                                                        <label class="form-label mb-1">POS Machine</label>
                                                        <select id="pos_machine_modal" class="form-select">
                                                            <option value="">Select POS machine (optional)</option>
                                                            @foreach(($posMachines ?? collect()) as $m)
                                                                <option value="{{ $m->id }}" data-bank="{{ $m->bank_name }}" data-account-name="{{ $m->account_name }}" data-account-number="{{ $m->account_number }}">
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
                                                                <option value="{{ $b->id }}" data-bank="{{ $b->name }}" data-account-name="{{ $b->account_name }}" data-account-number="{{ $b->account_number }}">
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
        if (useProductsToggle.checked) {
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
    }
    useProductsToggle.addEventListener('change', function() {
        applyUseProducts();
        updateProductTotals();
        updateManualTotals();
    });
    setManualItemsRequired(false);

    // Products section calculations
    var productTable = document.getElementById('product-quantities-table');
    function updateProductTotals() {
        if (!productTable) return;
        var subtotal = 0;
        productTable.querySelectorAll('.product-row').forEach(function(row) {
            var qty = parseFloat(row.querySelector('.product-qty').value) || 0;
            var price = parseFloat(row.querySelector('.product-qty').getAttribute('data-unit-price')) || 0;
            var lineTotal = qty * price;
            row.querySelector('.product-line-total').textContent = formatPrice(lineTotal);
            subtotal += lineTotal;
        });
        var tax = parseFloat(document.getElementById('tax-input').value) || 0;
        var discount = parseFloat(document.getElementById('discount-input').value) || 0;
        var total = subtotal + tax - discount;
        document.getElementById('product-subtotal').textContent = formatPrice(subtotal);
        document.getElementById('product-total').textContent = formatPrice(total);

        var floatSubtotal = document.getElementById('invoice-float-subtotal');
        var floatTotal = document.getElementById('invoice-float-total');
        if (floatSubtotal) floatSubtotal.textContent = formatPrice(subtotal);
        if (floatTotal) floatTotal.textContent = formatPrice(total);
    }

    if (productTable) {
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
    }

    // Manual items section
    var tbody = document.getElementById('invoice-items-tbody');
    var rowIndex = {{ max(1, (int) ($invoice->items?->count() ?? 1)) }};
    var invoiceForm = document.getElementById('invoice-form');
    var template = document.createElement('template');
    template.innerHTML = `
        <tr class="invoice-item-row">
            <td><input type="text" name="items[__INDEX__][item_name]" class="form-control form-control-sm" list="invoice-products-datalist" autocomplete="off"></td>
            <td><input type="text" name="items[__INDEX__][description]" class="form-control form-control-sm"></td>
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

    function updateManualTotals() {
        if (!tbody) return;
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
            if (unitPvEl) unitPvEl.textContent = formatPrice(pvUnit);
            if (unitBvEl) unitBvEl.textContent = formatPrice(bvUnit);
            if (linePvEl) linePvEl.textContent = formatPrice(linePv);
            if (lineBvEl) lineBvEl.textContent = formatPrice(lineBv);
            subtotal += lineTotal;
            subtotalPv += linePv;
            subtotalBv += lineBv;
        });
        var tax = parseFloat(document.getElementById('tax-input-manual').value) || 0;
        var discount = parseFloat(document.getElementById('discount-input-manual').value) || 0;
        var total = subtotal + tax - discount;
        document.getElementById('subtotal-display').value = formatPrice(subtotal);
        document.getElementById('total-display').value = formatPrice(total);

        var subtotalPvEl = document.getElementById('manual-subtotal-pv');
        var subtotalBvEl = document.getElementById('manual-subtotal-bv');
        var totalPvEl = document.getElementById('manual-total-pv');
        var totalBvEl = document.getElementById('manual-total-bv');
        if (subtotalPvEl) subtotalPvEl.textContent = formatPrice(subtotalPv);
        if (subtotalBvEl) subtotalBvEl.textContent = formatPrice(subtotalBv);
        if (totalPvEl) totalPvEl.textContent = formatPrice(subtotalPv);
        if (totalBvEl) totalBvEl.textContent = formatPrice(subtotalBv);
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
        var trHtml = template.innerHTML.replace(/__INDEX__/g, rowIndex++);
        tbody.insertAdjacentHTML('beforeend', trHtml);
        wireManualRowEvents();
        updateManualTotals();
        return tbody.lastElementChild;
    }

    function maybeAutoAddRow(currentRow) {
        if (!tbody) return;
        if (!currentRow) return;
        if (tbody.lastElementChild !== currentRow) return;
        var nameInput = currentRow.querySelector('input[name*="[item_name]"]');
        if (!nameInput) return;
        if ((nameInput.value || '').trim() === '') return;
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

        if (currentRow.dataset) currentRow.dataset.merged = '1';
        currentRow.remove();
        updateManualTotals();
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
                return updateManualTotals();
            }

            var unitInput = row.querySelector('input[name*="[unit]"]');
            var priceInput = row.querySelector('input[name*="[unit_price]"]');
            if (unitInput && (!unitInput.value || unitInput.value.trim() === '')) unitInput.value = p.unit || 'pcs';
            if (priceInput) priceInput.value = p.price || 0;
            row.setAttribute('data-pv-unit', String(p.pv || 0));
            row.setAttribute('data-bv-unit', String(p.bv || 0));
            updateManualTotals();
            mergeDuplicateRows(row);
            maybeAutoAddRow(row);
        }

        nameInput.addEventListener('change', applyIfMatch);
        // blur can fire right after change in some browsers and double-merge; keep only change.
    }

    if (tbody) {
        var addBtn = document.getElementById('add-row');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                addManualRow();
            });
        }

        // Allow submitting even when the last row is blank:
        // disable all inputs in rows with empty item_name so the browser doesn't block on `required`.
        if (invoiceForm) {
            invoiceForm.addEventListener('submit', function () {
                if (useProductsToggle && useProductsToggle.checked) return;
                tbody.querySelectorAll('.invoice-item-row').forEach(function (row) {
                    var nameInput = row.querySelector('input[name*="[item_name]"]');
                    var name = (nameInput?.value || '').trim();
                    if (name !== '') return;
                    row.querySelectorAll('input, select, textarea').forEach(function (el) {
                        el.disabled = true;
                        el.removeAttribute('required');
                    });
                });
            });
        }

        function wireManualRowEvents() {
            tbody.querySelectorAll('.invoice-item-row').forEach(function(row) {
                row.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
                    input.removeEventListener('input', updateManualTotals);
                    input.addEventListener('input', updateManualTotals);
                });
                wireManualProductAutocomplete(row);
            });
            tbody.querySelectorAll('.remove-row').forEach(function(btn) {
                btn.onclick = function() {
                    var rows = tbody.querySelectorAll('.invoice-item-row');
                    if (rows.length <= 1) return alert('At least one item is required.');
                    btn.closest('tr').remove();
                    updateManualTotals();
                };
            });
        }

        wireManualRowEvents();
        document.getElementById('tax-input-manual').addEventListener('input', updateManualTotals);
        document.getElementById('discount-input-manual').addEventListener('input', updateManualTotals);
    }

    // Sync tax/discount between sections
    var taxInput = document.getElementById('tax-input');
    var taxInputManual = document.getElementById('tax-input-manual');
    var discountInput = document.getElementById('discount-input');
    var discountInputManual = document.getElementById('discount-input-manual');
    if (taxInput && taxInputManual) {
        taxInput.addEventListener('input', function() { taxInputManual.value = this.value; updateManualTotals(); });
        taxInputManual.addEventListener('input', function() { taxInput.value = this.value; updateProductTotals(); });
    }
    if (discountInput && discountInputManual) {
        discountInput.addEventListener('input', function() { discountInputManual.value = this.value; updateManualTotals(); });
        discountInputManual.addEventListener('input', function() { discountInput.value = this.value; updateProductTotals(); });
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
                    if (lastResolved === code) return;
                    scFeedback.innerHTML = '<span class="text-muted"><i class="fe fe-refresh-cw fe-spin me-1"></i>Checking...</span>';
                    fetch('{{ route("service-center.resolve") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
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
                            scFeedback.innerHTML = '<span class="text-success fw-semibold" style="font-size: 18px; line-height: 1.25;"><i class="fe fe-check-circle me-1"></i>Valid Service Center: <strong>' + data.name + '</strong></span>';
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
            customerWrap.style.display = useSc ? 'none' : '';
            scWrap.style.display = useSc ? '' : 'none';
            setEnabled(customerWrap, !useSc);
            setEnabled(scWrap, useSc);
            if (useSc && scInput && scInput.value.trim()) scInput.dispatchEvent(new Event('input'));
        }

        toggle.addEventListener('change', apply);
        apply();
    })();

    // initial compute + section toggles
    applyUseProducts();
    updateProductTotals();
    updateManualTotals();
})();
</script>

<script>
(function () {
    // Payment modal (edit) — keep hidden fields in sync
    var hiddenPos = document.getElementById('pos_amount_paid_hidden');
    var hiddenBankAmt = document.getElementById('bank_amount_paid_hidden');
    var hiddenSplit = document.getElementById('split-payment-toggle');
    var hiddenWallet = document.querySelector('input[name="split_wallet_amount"]');
    var hiddenKd = document.querySelector('input[name="split_kd_credit_amount"]');
    var hiddenCash = document.querySelector('input[name="split_cash_amount"]');
    var hiddenCheque = document.querySelector('input[name="split_cheque_amount"]');

    var modalSplit = document.getElementById('split_payment_modal');
    var modalSplitWrap = document.getElementById('split_fields_modal');
    var modalWallet = document.getElementById('split_wallet_amount_modal');
    var modalKd = document.getElementById('split_kd_credit_amount_modal');
    var modalCash = document.getElementById('split_cash_amount_modal');
    var modalCheque = document.getElementById('split_cheque_amount_modal');
    var modalWalletWrap = document.getElementById('split_wallet_amount_modal_wrap');
    var modalKdWrap = document.getElementById('split_kd_credit_amount_modal_wrap');
    var modalPosMachine = document.getElementById('pos_machine_modal');
    var modalPosMachineNote = document.getElementById('pos_machine_modal_note');
    var modalPosAmtWrap = document.getElementById('pos_amount_paid_modal_wrap');
    var modalPosAmt = document.getElementById('pos_amount_paid_modal');
    var modalBankAccount = document.getElementById('bank_account_modal');
    var modalBankAccountNote = document.getElementById('bank_account_modal_note');
    var modalBankAmtWrap = document.getElementById('bank_amount_paid_modal_wrap');
    var modalBankAmt = document.getElementById('bank_amount_paid_modal');
    var applyBtn = document.getElementById('payment_apply_btn');

    var dueModalEl = document.getElementById('split-total-due-modal');
    var enteredModalEl = document.getElementById('split-total-entered-modal');
    var remainingModalEl = document.getElementById('split-remaining-modal');

    if (!hiddenPos || !hiddenBankAmt || !hiddenSplit || !modalSplit || !applyBtn) return;

    function parseMoney(str) {
        let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
        const firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) {
            cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
        }
        const num = Number(cleaned);
        return Number.isFinite(num) ? num : 0;
    }

    function fmtMoney(num) {
        return Number(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function currentInvoiceTotal() {
        var t = document.getElementById('product-total')?.textContent || '0';
        if (t && String(t).trim() !== '') return parseFloat(String(t).replace(/,/g, '')) || 0;
        var mt = document.getElementById('total-display')?.value || '0';
        return parseFloat(String(mt).replace(/,/g, '')) || 0;
    }

    function recalcModalTotals() {
        var due = currentInvoiceTotal();
        var sum = 0;
        sum += parseMoney(modalWallet?.value);
        sum += parseMoney(modalKd?.value);
        sum += parseMoney(modalCash?.value);
        sum += parseMoney(modalCheque?.value);
        sum += parseMoney(modalPosAmt?.value);
        sum += parseMoney(modalBankAmt?.value);
        var rem = due - sum;
        if (dueModalEl) dueModalEl.textContent = fmtMoney(due);
        if (enteredModalEl) enteredModalEl.textContent = fmtMoney(sum);
        if (remainingModalEl) remainingModalEl.textContent = fmtMoney(rem);
    }

    function syncModalFromHidden() {
        modalSplit.checked = !!hiddenSplit.checked;
        if (modalSplitWrap) modalSplitWrap.style.display = modalSplit.checked ? '' : 'none';
        if (modalWallet && hiddenWallet) modalWallet.value = (parseFloat(hiddenWallet.value || 0) > 0) ? fmtMoney(hiddenWallet.value) : '';
        if (modalKd && hiddenKd) modalKd.value = (parseFloat(hiddenKd.value || 0) > 0) ? fmtMoney(hiddenKd.value) : '';
        if (modalCash && hiddenCash) modalCash.value = (parseFloat(hiddenCash.value || 0) > 0) ? fmtMoney(hiddenCash.value) : '';
        if (modalCheque && hiddenCheque) modalCheque.value = (parseFloat(hiddenCheque.value || 0) > 0) ? fmtMoney(hiddenCheque.value) : '';
        if (modalPosAmt) modalPosAmt.value = hiddenPos.value ? fmtMoney(hiddenPos.value) : '';
        if (modalBankAmt) modalBankAmt.value = hiddenBankAmt.value ? fmtMoney(hiddenBankAmt.value) : '';

        // Hide split inputs when balance is zero
        var walletAvail = parseMoney(document.getElementById('split-wallet-available-modal')?.textContent || '0');
        var kdAvail = parseMoney(document.getElementById('split-kd-credit-available-modal')?.textContent || '0');
        if (modalWalletWrap) modalWalletWrap.style.display = walletAvail > 0 ? '' : 'none';
        if (modalKdWrap) modalKdWrap.style.display = kdAvail > 0 ? '' : 'none';
        if (modalWallet) modalWallet.disabled = !(walletAvail > 0);
        if (modalKd) modalKd.disabled = !(kdAvail > 0);
        if (walletAvail <= 0 && modalWallet && hiddenWallet) { modalWallet.value = ''; hiddenWallet.value = '0'; }
        if (kdAvail <= 0 && modalKd && hiddenKd) { modalKd.value = ''; hiddenKd.value = '0'; }

        recalcModalTotals();
    }

    function applyToHidden() {
        hiddenSplit.checked = !!modalSplit.checked;
        hiddenSplit.dispatchEvent(new Event('change'));

        if (hiddenWallet && modalWallet) hiddenWallet.value = parseMoney(modalWallet.value).toFixed(2);
        if (hiddenKd && modalKd) hiddenKd.value = parseMoney(modalKd.value).toFixed(2);
        if (hiddenCash && modalCash) hiddenCash.value = parseMoney(modalCash.value).toFixed(2);
        if (hiddenCheque && modalCheque) hiddenCheque.value = parseMoney(modalCheque.value).toFixed(2);

        hiddenPos.value = modalPosAmt && modalPosAmt.value.trim() !== '' ? parseMoney(modalPosAmt.value).toFixed(2) : '';
        hiddenPos.dispatchEvent(new Event('input'));

        hiddenBankAmt.value = modalBankAmt && modalBankAmt.value.trim() !== '' ? parseMoney(modalBankAmt.value).toFixed(2) : '';
        hiddenBankAmt.dispatchEvent(new Event('input'));
    }

    function updatePosNote() {
        if (!modalPosMachine || !modalPosMachineNote) return;
        var opt = modalPosMachine.options[modalPosMachine.selectedIndex];
        var bank = opt?.getAttribute('data-bank') || '';
        var acctNo = opt?.getAttribute('data-account-number') || '';
        var acctName = opt?.getAttribute('data-account-name') || '';
        modalPosMachineNote.textContent = modalPosMachine.value ? ((bank || 'POS') + (acctNo ? ' • ' + acctNo : '') + (acctName ? ' • ' + acctName : '')) : '';
        if (modalPosAmtWrap) modalPosAmtWrap.style.display = modalPosMachine.value ? '' : 'none';
        if (!modalPosMachine.value && modalPosAmt) modalPosAmt.value = '';
    }

    function updateBankNote() {
        if (!modalBankAccount || !modalBankAccountNote) return;
        var opt = modalBankAccount.options[modalBankAccount.selectedIndex];
        var bank = opt?.getAttribute('data-bank') || '';
        var acctNo = opt?.getAttribute('data-account-number') || '';
        var acctName = opt?.getAttribute('data-account-name') || '';
        modalBankAccountNote.textContent = modalBankAccount.value ? ((bank || 'Bank') + (acctNo ? ' • ' + acctNo : '') + (acctName ? ' • ' + acctName : '')) : '';
        if (modalBankAmtWrap) modalBankAmtWrap.style.display = modalBankAccount.value ? '' : 'none';
        if (!modalBankAccount.value && modalBankAmt) modalBankAmt.value = '';
    }

    modalSplit.addEventListener('change', function () {
        if (modalSplitWrap) modalSplitWrap.style.display = modalSplit.checked ? '' : 'none';
        recalcModalTotals();
    });
    applyBtn.addEventListener('click', applyToHidden);

    [modalWallet, modalKd, modalCash, modalCheque].forEach(function (el) {
        if (!el) return;
        wireMoneyInput(el);
    });
    if (modalPosMachine) modalPosMachine.addEventListener('change', function () { updatePosNote(); recalcModalTotals(); });
    if (modalBankAccount) modalBankAccount.addEventListener('change', function () { updateBankNote(); recalcModalTotals(); });

    function wireMoneyInput(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/[^0-9.,]/g, '');
            recalcModalTotals();
        });
        el.addEventListener('blur', function () {
            if ((el.value || '').trim() === '') return;
            el.value = Number(parseMoney(el.value)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    }
    wireMoneyInput(modalPosAmt);
    wireMoneyInput(modalBankAmt);

    var paymentModalEl = document.getElementById('paymentModal');
    if (paymentModalEl) {
        paymentModalEl.addEventListener('shown.bs.modal', function () {
            syncModalFromHidden();
            updatePosNote();
            updateBankNote();
        });
    }
})();
</script>

@include('partials.pwa-scripts')
</body>
</html>

