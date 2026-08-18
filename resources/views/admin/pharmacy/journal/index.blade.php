@extends('layouts.admin')

@section('title', 'Journal')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Journal</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Journal</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pharmacy.journal.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New Journal Entry</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pharmacy.journal.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Apply</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.pharmacy.journal.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Entries</h3>
            <div class="small text-muted mt-1">For error correction, provisions and accruals (debit/credit).</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Memo</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $je)
                            @php
                                $totalDebit = (float) $je->lines->sum('debit');
                                $totalCredit = (float) $je->lines->sum('credit');
                            @endphp
                            <tr>
                                <td>{{ $je->entry_date?->format('M d, Y') ?? '—' }}</td>
                                <td>{{ $je->reference ?? '—' }}</td>
                                <td>{{ $je->type ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($je->memo ?? '', 80) }}</td>
                                <td class="text-end">₦{{ number_format($totalDebit, 2) }}</td>
                                <td class="text-end">₦{{ number_format($totalCredit, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted p-4">No journal entries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
            <div class="card-footer">{{ $entries->links() }}</div>
        @endif
    </div>
@endsection

