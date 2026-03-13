@extends('layouts.admin')

@section('title', 'Allocate Stock to Branch')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Allocate Stock to Branch</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.branch.stock.index') }}">Branch Stock</a></li>
                <li class="breadcrumb-item active" aria-current="page">Allocate</li>
            </ol>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($branchUsers->isEmpty())
        <div class="alert alert-warning">No branch users found. Create a Branch user first from Users.</div>
    @else
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.branch.stock.store-allocate') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_user_id" class="form-select @error('branch_user_id') is-invalid @enderror" required>
                                <option value="">-- Select branch --</option>
                                @foreach($branchUsers as $bu)
                                    <option value="{{ $bu->id }}" {{ old('branch_user_id', request('branch_user_id')) == $bu->id ? 'selected' : '' }}>{{ $bu->name }} ({{ $bu->email }})</option>
                                @endforeach
                            </select>
                            @error('branch_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">-- Select product --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }} data-stock="{{ $p->stock }}">
                                        {{ $p->display_name }} (Main stock: {{ $p->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" min="1" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Allocate</button>
                            <a href="{{ route('admin.branch.stock.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
