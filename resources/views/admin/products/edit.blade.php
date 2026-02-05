@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Product</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $product->display_name }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Item code</label>
                    <input type="text" name="item_code" class="form-control" value="{{ old('item_code', $product->item_code) }}" required maxlength="20">
                    @error('item_code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Category (optional)</label>
                    <select name="category_id" class="form-select">
                        <option value="">— No category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('category_id', $product->category_id) === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Pack size (optional)</label>
                    <input type="text" name="pack_size" class="form-control" value="{{ old('pack_size', $product->pack_size) }}" maxlength="100">
                    @error('pack_size')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Sort order (optional)</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $product->sort_order) }}" min="0">
                    @error('sort_order')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Image (optional)</label>
                    @if($product->image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="" class="rounded" style="max-width: 80px; max-height: 80px; object-fit: cover;">
                            <span class="text-muted ms-2">Current image. Upload a new file to replace.</span>
                        </div>
                    @endif
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small class="text-muted">Max 2MB. Leave empty to keep current image.</small>
                    @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Member Price (₦) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $product->price) }}" step="0.01" min="0" required placeholder="Members">
                    <small class="text-muted">Price shown to members on the shop.</small>
                    @error('price')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Retail Price (₦)</label>
                    <input type="number" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0" placeholder="Guests and non-members">
                    <small class="text-muted">Price shown to guests and other roles. Leave empty to use 20% above member price.</small>
                    @error('cost_price')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock (add to stock)</label>
                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" min="0">
                    @error('stock')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Expiry date (optional)</label>
                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}">
                    @error('expiry_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batch number (optional)</label>
                    <input type="text" name="batch_number" class="form-control" value="{{ old('batch_number', $product->batch_number) }}" maxlength="100">
                    @error('batch_number')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min stock (reorder alert)</label>
                    <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock) }}" min="0">
                    @error('min_stock')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">BV</label>
                    <input type="number" name="bv" class="form-control" value="{{ old('bv', $product->bv) }}" step="0.01" min="0" required>
                    @error('bv')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">PV</label>
                    <input type="number" name="pv" class="form-control" value="{{ old('pv', $product->pv) }}" step="0.01" min="0" required>
                    @error('pv')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4"></div>

                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active (visible on shop)</label>
                    </div>
                    @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
@endsection
