@extends('layouts.admin')

@section('title', 'Branch Stock')

@section('content')
    <div class="page-header">
        <h1 class="page-title">
            @if($isSuperAdmin && $branchUser)
                Stock: {{ $branchUser->name }}
            @else
                My Stock
            @endif
        </h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Branch Stock</li>
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

    @if($isSuperAdmin && $branchUsers->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.branch.stock.index') }}" class="d-flex gap-2 align-items-end">
                    <div>
                        <label class="form-label small mb-1">Select Branch</label>
                        <select name="branch_user_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select branch --</option>
                            @foreach($branchUsers as $bu)
                                <option value="{{ $bu->id }}" {{ (string) ($branchUser->id ?? '') === (string) $bu->id ? 'selected' : '' }}>{{ $bu->name }} ({{ $bu->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.branch.stock.allocate') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-1"></i>Allocate Stock
                    </a>
                </form>
            </div>
        </div>
    @endif

    @if($branchUser)
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Stock items for {{ $branchUser->name }}</span>
                    @if($isSuperAdmin)
                        <a href="{{ route('admin.branch.stock.allocate') }}?branch_user_id={{ $branchUser->id }}" class="btn btn-sm btn-primary">
                            <i class="fe fe-plus me-1"></i>Allocate
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Item Code</th>
                                <th class="text-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockItems as $item)
                                <tr>
                                    <td>{{ $item->product?->name ?? '—' }}</td>
                                    <td>{{ $item->product?->item_code ?? '—' }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted p-4">No stock allocated yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($isSuperAdmin && $branchUsers->isEmpty())
        <div class="alert alert-info">No branch users found. Create a Branch user first.</div>
    @endif
@endsection
