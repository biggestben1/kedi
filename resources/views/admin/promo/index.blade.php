@extends('layouts.admin')

@section('title', 'Promo Collections')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Promo Collections</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Promo Collections</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0">Promo Records</h3>
                <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Filter by KD NO" value="{{ $codeFilter ?? '' }}" style="width:150px">
                    <input type="text" name="promo" class="form-control form-control-sm" placeholder="Filter by promo" value="{{ $promoFilter ?? '' }}" style="width:150px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                    @if(($codeFilter ?? '') || ($promoFilter ?? ''))
                        <a href="{{ route('admin.promo.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.promo.rematch') }}" class="d-inline" onsubmit="return confirm('Re-match all unmatched promo records?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"><i class="fe fe-refresh-cw me-1"></i>Re-match Unmatched</button>
                </form>
                <a href="{{ route('admin.promo.create') }}" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload Excel</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Promo</th>
                            <th>Shop</th>
                            <th>CustomerNO (KD NO)</th>
                            <th>Customer Name</th>
                            <th>Promo Item</th>
                            <th>Qty</th>
                            <th>Matched User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collections as $c)
                            <tr>
                                <td>{{ $c->promo_name ?? '—' }}</td>
                                <td>{{ $c->shop_no ?? '—' }}</td>
                                <td><strong>{{ $c->customer_no }}</strong></td>
                                <td>{{ $c->customer_name }}</td>
                                <td>{{ $c->promo_item ?? '—' }}</td>
                                <td>{{ $c->quantity }}</td>
                                <td>
                                    @if($c->user_id)
                                        <span class="badge bg-success">{{ $c->user->name ?? 'User #' . $c->user_id }}</span>
                                    @else
                                        <span class="badge bg-secondary">Unmatched</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-4">No promo records. <a href="{{ route('admin.promo.create') }}">Upload Excel</a> to import.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($collections->hasPages())
            <div class="card-footer">{{ $collections->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Excel Format</h5>
            <p class="mb-0">Upload an Excel file with columns: <strong>ShopNO</strong>, <strong>CustomerNO</strong> (KD NO), <strong>CustomerName</strong>, promo item column (header = item name, e.g. BIG BULL 5KG RICE; value = quantity). CustomerNO is matched against <code>kd_customers</code> and <code>orders.kd_id</code>. Matched users see their promos on "My Promo".</p>
        </div>
    </div>
@endsection
