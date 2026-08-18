@extends('layouts.admin')

@section('title', 'Add Warehouse')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Add Warehouse</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.warehouses.index') }}">Warehouses</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Warehouse</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">New Warehouse</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.warehouses.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save Warehouse</button>
                    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

