@extends('layouts.admin')

@section('title', 'Upload Promo Excel')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Upload Promo Excel</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.promo.index') }}">Promo Collections</a></li>
                <li class="breadcrumb-item active" aria-current="page">Upload</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Upload Promo Excel</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.promo.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="promo_name" class="form-label">Promo Name (optional)</label>
                    <input type="text" name="promo_name" id="promo_name" class="form-control" value="{{ old('promo_name') }}" placeholder="e.g. RICE WINNER LIST IN 2025.11 PROMO">
                    <small class="form-text text-muted">Leave blank to auto-detect from the sheet.</small>
                </div>

                <div class="mb-4">
                    <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">Must have header row with ShopNO, CustomerNO, CustomerName. CustomerNO = KD NO.</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info">
                    <strong>Format:</strong> Header row with <strong>ShopNO</strong>, <strong>CustomerNO</strong> (KD NO), <strong>CustomerName</strong>, then a promo item column (header = item name like "BIG BULL 5KG RICE", value = quantity). CustomerNO is matched to users; they will see their promos on "My Promo".
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload & Import</button>
                    <a href="{{ route('admin.promo.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
