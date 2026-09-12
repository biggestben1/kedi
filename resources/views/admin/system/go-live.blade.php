@extends('layouts.admin')

@section('title', 'Go Live — Clear Test Data')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Go Live — Clear Test Data</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Go Live</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0"><i class="fe fe-rocket me-2"></i>Prepare for go-live</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Use this once to remove <strong>test orders and invoices</strong> before you go live.
                        Users, products, and stock levels are kept. This cannot be undone.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="text-muted small">Orders</div>
                                <div class="h4 mb-0">{{ number_format($orderCount) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="text-muted small">Invoices</div>
                                <div class="h4 mb-0">{{ number_format($invoiceCount) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="text-muted small">Back orders</div>
                                <div class="h4 mb-0">{{ number_format($backOrderCount) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="text-muted small">Wallet transactions</div>
                                <div class="h4 mb-0">{{ number_format($walletTxCount) }}</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-danger">This will permanently delete:</h6>
                    <ul class="mb-4">
                        <li>All customer <strong>orders</strong> and order items</li>
                        <li>All sales <strong>invoices</strong> and invoice items</li>
                        <li>All <strong>back orders</strong> and KEDI kit back orders</li>
                        <li>All <strong>factory invoices</strong> (stock-in records)</li>
                        <li>All <strong>wallet transactions</strong></li>
                        <li>Reset every user&apos;s <strong>wallet</strong> and <strong>KEDI credit</strong> balance to ₦0.00</li>
                    </ul>

                    <form method="POST" action="{{ route('admin.system.clear-go-live') }}" onsubmit="return confirm('This permanently deletes ALL orders, invoices, and wallet history. Are you absolutely sure?');">
                        @csrf
                        <div class="mb-3">
                            <label for="confirm" class="form-label">Type <strong>GO LIVE</strong> to confirm</label>
                            <input type="text" name="confirm" id="confirm" class="form-control @error('confirm') is-invalid @enderror" placeholder="GO LIVE" autocomplete="off" required>
                            @error('confirm')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fe fe-trash-2 me-2"></i>Clear all orders &amp; invoices
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
