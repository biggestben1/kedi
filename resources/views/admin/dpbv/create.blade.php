@extends('layouts.admin')

@section('title', 'Upload DPBV Excel')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Upload DPBV Excel</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.dpbv.index') }}">DPBV Collections</a></li>
                <li class="breadcrumb-item active" aria-current="page">Upload</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Upload DPBV Collection Excel</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.dpbv.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">Accepted: .xlsx, .xls, .csv. Max 10MB. Must have columns: NO, CODE, NAME, DATE, SC, DPBV.</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info">
                    <strong>Format:</strong> The first row should contain headers: <strong>NO</strong>, <strong>CODE</strong> (KD NO), <strong>NAME</strong>, <strong>DATE</strong>, <strong>SC</strong>, <strong>DPBV</strong>. CODE values are matched against <code>kd_customers.kd_no</code> to link records to user accounts. Users with matching KD numbers will see their DPBV on "My DPBV".
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload & Import</button>
                    <a href="{{ route('admin.dpbv.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
