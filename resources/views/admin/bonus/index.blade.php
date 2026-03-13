@extends('layouts.admin')

@section('title', 'Bonus Collections')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Bonus Collections</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bonus Collections</li>
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
                <h3 class="card-title mb-0">Bonus Records</h3>
                {{-- Disbursement Filter Buttons --}}
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.bonus.index', array_merge(request()->query(), ['filter' => 'all'])) }}" 
                       class="btn btn-sm {{ ($filter ?? 'all') === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                        All
                    </a>
                    <a href="{{ route('admin.bonus.index', array_merge(request()->query(), ['filter' => 'disbursed'])) }}" 
                       class="btn btn-sm {{ ($filter ?? 'all') === 'disbursed' ? 'btn-success' : 'btn-outline-success' }}">
                        Disbursed
                    </a>
                    <a href="{{ route('admin.bonus.index', array_merge(request()->query(), ['filter' => 'undisbursed'])) }}" 
                       class="btn btn-sm {{ ($filter ?? 'all') === 'undisbursed' ? 'btn-warning' : 'btn-outline-warning' }}">
                        Undisbursed
                    </a>
                </div>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Filter by Code (KD NO)" value="{{ $codeFilter ?? '' }}" style="width:200px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                    @if($codeFilter ?? '')
                        <a href="{{ route('admin.bonus.index', ['filter' => $filter ?? 'all']) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'headquarters')
                <form method="POST" action="{{ route('admin.bonus.rematch') }}" class="d-inline" onsubmit="return confirm('Re-match all unmatched bonus records?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"><i class="fe fe-refresh-cw me-1"></i>Re-match Unmatched</button>
                </form>
                <a href="{{ route('admin.bonus.create') }}" class="btn btn-primary"><i class="fe fe-upload me-1"></i>Upload Excel</a>
                <form id="bulkActionForm" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="button" class="btn btn-success" onclick="bulkAction('{{ route('admin.bonus.bulk-disburse') }}', 'Disburse')"><i class="fe fe-check me-1"></i>Bulk Disburse</button>
                    <button type="button" class="btn btn-warning" onclick="bulkAction('{{ route('admin.bonus.bulk-undisburse') }}', 'Undisburse')"><i class="fe fe-rotate-ccw me-1"></i>Bulk Undisburse</button>
                    <span class="ms-2">Selected: <strong id="selectedCount">0</strong></span>
                </form>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="selectAll"></th>
                            <th>No</th>
                            <th>Code (KD NO)</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>SC</th>
                            <th>Grade</th>
                            <th>Honorary</th>
                            <th class="text-end">Total</th>
                            <th>Matched User</th>
                            <th>Disbursed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collections as $c)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" class="row-checkbox" form="bulkActionForm"></td>
                                <td>{{ $c->no ?? '—' }}</td>
                                <td><strong>{{ $c->code }}</strong></td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->record_date->format('Y-m-d') }}</td>
                                <td>{{ $c->sc }}</td>
                                <td>{{ $c->grade ?? '—' }}</td>
                                <td>{{ $c->honorary ?? '—' }}</td>
                                <td class="text-end">{{ number_format($c->total, 2) }}</td>
                                <td>
                                    @if($c->user_id)
                                        <span class="badge bg-success">{{ $c->user->name ?? 'User #' . $c->user_id }}</span>
                                    @else
                                        <span class="badge bg-secondary">Unmatched</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c->is_disbursed)
                                        <span class="badge bg-success">Yes</span>
                                        <div class="text-muted small">by {{ $c->disbursedBy?->name ?? 'User #' . $c->disbursed_by_user_id }}<br>{{ optional($c->disbursed_at)->format('Y-m-d H:i') }}</div>
                                    @else
                                        <span class="badge bg-warning">No</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.bonus.toggle-disbursement', $c) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
                                        <button type="submit" class="btn btn-sm {{ $c->is_disbursed ? 'btn-outline-warning' : 'btn-outline-success' }}">{{ $c->is_disbursed ? 'Mark Undisbursed' : 'Mark Disbursed' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted p-4">No bonus records. <a href="{{ route('admin.bonus.create') }}">Upload Excel</a> to import.</td></tr>
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
            <h5 class="card-title">Excel Format</h5>
            <p class="mb-0">Upload an Excel file with columns: <strong>No</strong>, <strong>Code</strong> (KD NO), <strong>Name</strong>, <strong>Date</strong>, <strong>SC</strong>, <strong>Grade</strong>, <strong>Honorary</strong>, <strong>Total</strong>. Code is matched against <code>kd_customers</code> and <code>orders.kd_id</code>. Matched users see their bonus on "My Bonus".</p>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectedCount = document.getElementById('selectedCount');

            function updateSelectedCount() {
                const count = document.querySelectorAll('.row-checkbox:checked').length;
                selectedCount.textContent = count;
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateSelectedCount();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        });

        function bulkAction(url, action) {
            const form = document.getElementById('bulkActionForm');
            const checked = document.querySelectorAll('.row-checkbox:checked');
            
            if (checked.length === 0) {
                alert('Please select at least one bonus record.');
                return;
            }

            if (!confirm(`Are you sure you want to ${action.toLowerCase()} ${checked.length} selected bonus record(s)?`)) {
                return;
            }

            form.action = url;
            form.submit();
        }
    </script>
    @endpush
@endsection
