@extends('layouts.admin')

@section('title', 'Add Kedi Credit')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Add Kedi Credit</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.registration.index') }}">Service Centers</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Credit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">{{ $serviceCenter->name }} <span class="text-muted fw-normal">({{ $serviceCenter->service_center_code ?? '—' }})</span></h3>
            <a href="{{ route('admin.kd.registration.index', ['search' => $serviceCenter->service_center_code ?? $serviceCenter->email]) }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <div><strong>Current credit balance:</strong> ₦{{ number_format((float) ($currentBalance ?? 0), 2) }}</div>
            </div>

            <form method="POST" action="{{ route('admin.kd.service-centers.credit.store', $serviceCenter) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="credit" {{ old('type', 'credit') === 'credit' ? 'selected' : '' }}>Credit</option>
                            <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input type="hidden" name="amount" id="amount_raw" value="{{ old('amount') }}">
                        <input
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            id="amount_display"
                            class="form-control @error('amount') is-invalid @enderror"
                            placeholder="1,000,000.00"
                            value="{{ old('amount') ? number_format((float) old('amount'), 2) : '' }}"
                            required
                        >
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" placeholder="Optional">
                        @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Optional">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fe fe-check-circle me-1"></i>Save
                        </button>
                        <a href="{{ route('admin.kd.registration.index', ['search' => $serviceCenter->service_center_code ?? $serviceCenter->email]) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var display = document.getElementById('amount_display');
        var raw = document.getElementById('amount_raw');
        if (!display || !raw) return;

        function formatWithCommas(value) {
            var s = String(value || '').replace(/,/g, '').replace(/[^\d.]/g, '');
            // keep only first dot
            var parts = s.split('.');
            var intPart = parts[0] || '';
            var decPart = parts.slice(1).join(''); // merge extra dots
            if (decPart.length > 2) decPart = decPart.slice(0, 2);
            intPart = intPart.replace(/^0+(?=\d)/, '');
            var formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return decPart.length ? formattedInt + '.' + decPart : formattedInt;
        }

        function syncRaw() {
            var s = String(display.value || '').replace(/,/g, '').trim();
            raw.value = s;
        }

        display.addEventListener('input', function () {
            var caret = display.selectionStart || 0;
            var before = display.value;
            var formatted = formatWithCommas(before);
            display.value = formatted;
            syncRaw();
            // best-effort caret restore
            var diff = (formatted.length - before.length);
            var next = Math.max(0, caret + diff);
            display.setSelectionRange(next, next);
        });

        // Ensure raw value is correct on submit
        display.closest('form')?.addEventListener('submit', function () {
            syncRaw();
        });

        // Initial sync
        syncRaw();
    })();
</script>
@endpush

