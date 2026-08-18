@extends('layouts.admin')

@section('title', 'New Journal Entry')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">New Journal Entry</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.journal.index') }}">Journal</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pharmacy.journal.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the errors below.</div>
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pharmacy.journal.store') }}">
        @csrf

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Entry</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="e.g. JV-0001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control" value="{{ old('type') }}" placeholder="e.g. correction / provision / accrual">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Memo</label>
                        <textarea name="memo" class="form-control" rows="2" placeholder="Short description">{{ old('memo') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Lines (Debit / Credit)</h3>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                    <i class="fe fe-plus me-1"></i>Add line
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="linesTable">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;">Account <span class="text-danger">*</span></th>
                                <th style="min-width: 240px;">Description</th>
                                <th class="text-end" style="min-width: 160px;">Debit</th>
                                <th class="text-end" style="min-width: 160px;">Credit</th>
                                <th style="width: 1%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $oldLines = old('lines');
                                if (!is_array($oldLines) || count($oldLines) < 2) {
                                    $oldLines = [
                                        ['account_name' => '', 'description' => '', 'debit' => '', 'credit' => ''],
                                        ['account_name' => '', 'description' => '', 'debit' => '', 'credit' => ''],
                                    ];
                                }
                            @endphp
                            @foreach($oldLines as $i => $line)
                                <tr>
                                    <td>
                                        <input type="text" name="lines[{{ $i }}][account_name]" class="form-control" value="{{ $line['account_name'] ?? '' }}" placeholder="e.g. Sales / Inventory / Rent">
                                    </td>
                                    <td>
                                        <input type="text" name="lines[{{ $i }}][description]" class="form-control" value="{{ $line['description'] ?? '' }}" placeholder="Optional">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control text-end debit" value="{{ $line['debit'] ?? '' }}" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control text-end credit" value="{{ $line['credit'] ?? '' }}" placeholder="0.00">
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm removeLineBtn">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="2" class="text-end fw-semibold">Totals</td>
                                <td class="text-end fw-semibold" id="totalDebit">0.00</td>
                                <td class="text-end fw-semibold" id="totalCredit">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="small text-muted">Total debit must equal total credit.</div>
                <button class="btn btn-primary" type="submit">Save Journal Entry</button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var tableBody = document.querySelector('#linesTable tbody');
            var addBtn = document.getElementById('addLineBtn');
            var totalDebitEl = document.getElementById('totalDebit');
            var totalCreditEl = document.getElementById('totalCredit');

            function recalcTotals() {
                var debit = 0;
                var credit = 0;
                document.querySelectorAll('#linesTable tbody .debit').forEach(function (el) {
                    debit += (parseFloat(el.value || '0') || 0);
                });
                document.querySelectorAll('#linesTable tbody .credit').forEach(function (el) {
                    credit += (parseFloat(el.value || '0') || 0);
                });
                totalDebitEl.textContent = debit.toFixed(2);
                totalCreditEl.textContent = credit.toFixed(2);
            }

            function reindexNames() {
                var rows = tableBody.querySelectorAll('tr');
                rows.forEach(function (row, idx) {
                    row.querySelectorAll('input').forEach(function (input) {
                        input.name = input.name.replace(/lines\\[\\d+\\]/, 'lines[' + idx + ']');
                    });
                });
            }

            function addRow() {
                var idx = tableBody.querySelectorAll('tr').length;
                var tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" name="lines[${idx}][account_name]" class="form-control" placeholder="e.g. Sales / Inventory / Rent"></td>
                    <td><input type="text" name="lines[${idx}][description]" class="form-control" placeholder="Optional"></td>
                    <td><input type="number" step="0.01" min="0" name="lines[${idx}][debit]" class="form-control text-end debit" placeholder="0.00"></td>
                    <td><input type="number" step="0.01" min="0" name="lines[${idx}][credit]" class="form-control text-end credit" placeholder="0.00"></td>
                    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm removeLineBtn">Remove</button></td>
                `;
                tableBody.appendChild(tr);
                recalcTotals();
            }

            tableBody.addEventListener('input', function (e) {
                if (e.target && (e.target.classList.contains('debit') || e.target.classList.contains('credit'))) {
                    recalcTotals();
                }
            });

            tableBody.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('removeLineBtn')) {
                    var rows = tableBody.querySelectorAll('tr');
                    if (rows.length <= 2) return;
                    e.target.closest('tr').remove();
                    reindexNames();
                    recalcTotals();
                }
            });

            addBtn.addEventListener('click', addRow);

            recalcTotals();
        })();
    </script>
@endsection

