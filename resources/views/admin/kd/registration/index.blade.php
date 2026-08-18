@extends('layouts.admin')

@section('title', 'Service Centers')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Service Centers</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Service Centers</li>
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
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h3 class="card-title mb-0">All Service Centers</h3>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email, phone, SC code..." value="{{ $search ?? '' }}" style="width:280px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                    @if($search ?? '')
                        <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
                <a href="{{ route('admin.kd.credit-owners') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fe fe-users me-1"></i>Who is owning
                </a>
            </div>
            <a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="btn btn-primary">
                <i class="fe fe-users me-2"></i>Manage Service Centers
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Service Center Code</th>
                            <th class="text-end">Wallet</th>
                            <th class="text-end">Kedi Credit</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceCenters as $sc)
                            <tr>
                                <td class="fw-semibold">{{ $sc->name }}</td>
                                <td>{{ $sc->email }}</td>
                                <td>{{ $sc->phone ?? '—' }}</td>
                                <td>{{ $sc->service_center_code ?? '—' }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format((float) ($sc->wallet_balance ?? 0), 2) }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format((float) ($sc->kedi_credit_balance ?? 0), 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.kd.service-centers.credit.form', $sc) }}" class="btn btn-sm btn-primary">
                                        <i class="fe fe-plus-circle me-1"></i>Add Credit
                                    </a>
                                    <a href="{{ route('admin.users.edit', $sc) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-4">No Service Centers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(($serviceCenters ?? null) && $serviceCenters->hasPages())
            <div class="card-footer">{{ $serviceCenters->links() }}</div>
        @endif
    </div>
@endsection
