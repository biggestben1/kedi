@extends('layouts.admin')

@section('title', 'DPBV Collections')

@section('content')
    <div class="page-header">
        <h1 class="page-title">DPBV Collections</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">DPBV Collections</li>
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
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0">DPBV Collection Records</h3>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Filter by CODE (KD NO)" value="{{ $codeFilter ?? '' }}" style="width:200px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                    @if($codeFilter ?? '')
                        <a href="{{ route('admin.dpbv.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.dpbv.rematch') }}" class="d-inline" onsubmit="return confirm('Re-match all unmatched DPBV records against kd_customers and orders?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"><i class="fe fe-refresh-cw me-1"></i>Re-match Unmatched</button>
                </form>
                <a href="{{ route('admin.dpbv.create') }}" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload Excel</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>CODE (KD NO)</th>
                            <th>NAME</th>
                            <th>DATE</th>
                            <th>SC</th>
                            <th class="text-end">DPBV</th>
                            <th>Matched User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collections as $c)
                            <tr>
                                <td>{{ $c->no ?? '—' }}</td>
                                <td><strong>{{ $c->code }}</strong></td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->record_date->format('Y-m-d') }}</td>
                                <td>{{ $c->sc }}</td>
                                <td class="text-end">{{ number_format($c->dpbv, 2) }}</td>
                                <td>
                                    @if($c->user_id)
                                        <span class="badge bg-success">{{ $c->user->name ?? 'User #' . $c->user_id }}</span>
                                    @else
                                        <span class="badge bg-secondary">Unmatched</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-4">No DPBV records. <a href="{{ route('admin.dpbv.create') }}">Upload Excel</a> to import.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($collections->hasPages())
            <div class="card-footer">{{ $collections->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Excel Format & Matching</h5>
            <p class="mb-0">Upload an Excel (.xlsx, .xls) or CSV file with columns: <strong>NO</strong>, <strong>CODE</strong> (KD NO), <strong>NAME</strong>, <strong>DATE</strong>, <strong>SC</strong>, <strong>DPBV</strong>. CODE is matched against <code>kd_customers.kd_no</code> and <code>orders.kd_id</code> (from shopping) to link records to user accounts. If orders were placed <em>after</em> upload, click <strong>Re-match Unmatched</strong> to update.</p>
        </div>
    </div>
@endsection
