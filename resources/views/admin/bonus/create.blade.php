@extends('layouts.admin')

@section('title', 'Upload Bonus Excel')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Upload Bonus Excel</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.bonus.index') }}">Bonus Collections</a></li>
                <li class="breadcrumb-item active" aria-current="page">Upload</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Upload Bonus Excel</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bonus.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">Must have a header row with Code (or KD NO, KDNO). Optional: No, Name, Date, SC, Grade, Honorary, Total.</small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info">
                    <strong>Format:</strong> Header row with <strong>Code</strong> (or KD NO, KDNO) required; optional: No, Name, Date, SC, Grade, Honorary, Total. Code = KD NO, matched to users. They will see their bonus on "My Bonus".
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload & Import</button>
                    <a href="{{ route('admin.bonus.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
