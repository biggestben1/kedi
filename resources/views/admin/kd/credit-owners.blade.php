@extends('layouts.admin')

@section('title', 'Kedi Credit Owners')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Kedi Credit Owners</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kedi Credit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">Who is owning credit</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fe fe-plus-circle me-1"></i>Add Credit
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.kd.credit-owners') }}" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search name / email / phone / SC code..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-3">
                    <input type="number" name="min" class="form-control" step="0.01" min="0" value="{{ $min ?? 0.01 }}" placeholder="Min balance">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fe fe-search me-1"></i>Search</button>
                    <a class="btn btn-outline-secondary w-100" href="{{ route('admin.kd.credit-owners') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Service Center Code</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th class="text-end">Credit Balance</th>
                            <th style="width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($owners as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->service_center_code ?? '—' }}</td>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->phone ?? '—' }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format((float) ($row->kedi_credit_balance ?? 0), 2) }}</td>
                                <td>
                                    <a href="{{ route('admin.kd.service-centers.credit.form', $row) }}" class="btn btn-sm btn-outline-primary">Add Credit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No credit owners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $owners->links() }}
            </div>
        </div>
    </div>
@endsection

