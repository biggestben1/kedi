@extends('layouts.admin')

@section('title', 'Edit Coupon')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Coupon</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.coupons.index') }}">Coupons</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Coupon Details: {{ $coupon->code }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Coupon Code</label>
                            <input type="text" class="form-control" value="{{ $coupon->code }}" readonly disabled>
                            <input type="hidden" name="code" value="{{ $coupon->code }}">
                            <small class="text-muted">Coupon codes cannot be changed after creation.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Discount Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" name="discount_percentage" class="form-control @error('discount_percentage') is-invalid @enderror" value="{{ old('discount_percentage', number_format($coupon->discount_percentage, 0)) }}" min="0" max="100" required>
                            @error('discount_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Expires at (optional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
                            @error('expires_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                                <span class="custom-control-label">Is Active</span>
                            </label>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Coupon</button>
                            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
