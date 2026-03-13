@extends('layouts.admin')

@section('title', 'Borrow & Share Products')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Borrow & Share Products</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Borrow</li>
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

    {{-- All Borrow (KD Numbers) --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0">All Auto-Generated KD NO</h3>
                <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fe fe-file-text me-1"></i>KD Registrations
                </a>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search KD NO, name, email..." value="{{ $search ?? '' }}" style="width:220px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                    @if($search ?? '')
                        <a href="{{ route('admin.kd.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>KD NO</th>
                            <th>Customer Name</th>
                            <th>User</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kdCustomersGrouped ?? [] as $groupKey => $groupItems)
                            <tr class="table-secondary">
                                <td colspan="5" class="fw-bold py-2">
                                    <i class="fe fe-hash me-1"></i> {{ $groupKey }}
                                    <span class="badge bg-light text-dark ms-2">{{ $groupItems->count() }} KD(s)</span>
                                </td>
                            </tr>
                            @foreach($groupItems as $kd)
                                <tr>
                                    <td><strong>{{ $kd->kd_no }}</strong></td>
                                    <td>{{ $kd->customer_name ?? '—' }}</td>
                                    <td>
                                        @if($kd->user_id)
                                            <a href="{{ route('admin.users.index', ['q' => $kd->user->email ?? $kd->user->name ?? '']) }}">{{ $kd->user->name ?? $kd->user->email ?? 'User #' . $kd->user_id }}</a>
                                            <br><small class="text-muted">{{ $kd->user->email ?? '' }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $kd->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.kd.show-share-kd', $kd) }}" class="btn btn-sm btn-outline-primary" title="Share products to this friend">
                                            <i class="fe fe-share-2"></i> Share to Friends
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No auto-generated KD numbers found. KD numbers are created when users click "Auto-generate" in the KD NO modal on the shop.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($kdCustomers->hasPages())
            <div class="card-footer">{{ $kdCustomers->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">About Borrow (Auto-Generated KD NO)</h5>
            <p class="mb-0">KD numbers in the format <strong>KD-{user_id}-{seq}</strong> are auto-generated when users click "Auto-generate" in the KD NO & Customer Name modal on the shop. They are stored in <code>kd_customers</code> and linked to user accounts for DPBV, Promo, and Bonus matching.</p>
        </div>
    </div>
@endsection
