@extends('layouts.admin')

@section('title', 'View KD Registration')

@section('content')
    <div class="page-header">
        <h1 class="page-title">View KD Registration</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.index') }}">Borrow</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.registration.index') }}">KD Registrations</a></li>
                <li class="breadcrumb-item active" aria-current="page">View</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">KD Registration Details</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.kd.registration.edit', $registration) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">KEDI Member Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">KD NO:</th>
                            <td><strong>{{ $registration->kd_no }}</strong></td>
                        </tr>
                        <tr>
                            <th>Registration Date:</th>
                            <td>{{ $registration->registration_date->format('Y-m-d') }}</td>
                        </tr>
                    </table>

                    <h5 class="mb-3 mt-4">Applicant Personal Details</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Full Name:</th>
                            <td>{{ $registration->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Gender:</th>
                            <td>{{ $registration->gender === 'M' ? 'Male' : 'Female' }}</td>
                        </tr>
                        <tr>
                            <th>State:</th>
                            <td>{{ $registration->state }}</td>
                        </tr>
                        <tr>
                            <th>Full Address:</th>
                            <td>{{ $registration->full_address }}</td>
                        </tr>
                        <tr>
                            <th>Phone Number:</th>
                            <td>{{ $registration->phone_number }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-3">Sponsor (Placement) Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Sponsor KEDI No.:</th>
                            <td><strong>{{ $registration->sponsor_kd_no }}</strong></td>
                        </tr>
                        <tr>
                            <th>Sponsor Name:</th>
                            <td>{{ $registration->sponsor_name }}</td>
                        </tr>
                        @if($registration->placement_kd_no)
                        <tr>
                            <th>Placement KEDI No.:</th>
                            <td><strong>{{ $registration->placement_kd_no }}</strong></td>
                        </tr>
                        <tr>
                            <th>Placement Name:</th>
                            <td>{{ $registration->placement_name }}</td>
                        </tr>
                        @endif
                    </table>

                    @if($registration->notes)
                    <h5 class="mb-3 mt-4">Notes</h5>
                    <div class="alert alert-info">{{ $registration->notes }}</div>
                    @endif

                    <h5 class="mb-3 mt-4">System Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Linked User Account:</th>
                            <td>
                                @if($registration->user)
                                    <a href="{{ route('admin.users.index', ['q' => $registration->user->email ?? $registration->user->name ?? '']) }}">
                                        {{ $registration->user->name }} ({{ $registration->user->email }})
                                    </a>
                                    @if($registration->user->role)
                                        <br><small class="text-muted">Role: {{ $registration->user->role->display_name ?? $registration->user->role->name }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">— Not linked</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Registered By:</th>
                            <td>{{ $registration->registeredBy ? $registration->registeredBy->name : '—' }}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>{{ $registration->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At:</th>
                            <td>{{ $registration->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Credit System Section --}}
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Credit System</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCreditModal">
                                <i class="fe fe-plus me-1"></i>Add Credit/Debit
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="alert alert-info mb-0">
                                        <strong>Current Credit Balance:</strong> 
                                        <span class="fs-4 fw-bold {{ $creditBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                            ₦{{ number_format($creditBalance, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-3">Credit Transaction History</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Balance After</th>
                                            <th>Reference</th>
                                            <th>Notes</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($credits as $index => $credit)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $credit->created_at->format('Y-m-d H:i') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $credit->type === 'credit' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($credit->type) }}
                                                    </span>
                                                </td>
                                                <td>₦{{ number_format($credit->amount, 2) }}</td>
                                                <td>
                                                    <span class="{{ $credit->balance_after >= 0 ? 'text-success' : 'text-danger' }}">
                                                        ₦{{ number_format($credit->balance_after, 2) }}
                                                    </span>
                                                </td>
                                                <td>{{ $credit->reference ?? '—' }}</td>
                                                <td>{{ $credit->notes ?? '—' }}</td>
                                                <td>{{ $credit->createdBy->name ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No credit transactions yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Credit Modal --}}
    <div class="modal fade" id="addCreditModal" tabindex="-1" aria-labelledby="addCreditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCreditModalLabel">Add Credit/Debit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.kd.registration.add-credit', $registration) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Balance</label>
                            <div class="alert alert-info mb-0">
                                <strong>₦{{ number_format($creditBalance, 2) }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="credit">Credit (Add)</option>
                                <option value="debit">Debit (Subtract)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="text" id="amountInput" class="form-control" required placeholder="0.00" autocomplete="off">
                            <input type="hidden" name="amount" id="amountHidden">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" class="form-control" maxlength="255" placeholder="Optional reference">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amountInput');
    const amountHidden = document.getElementById('amountHidden');
    const form = amountInput.closest('form');

    // Format number with thousand separators
    function formatNumber(value) {
        // Remove all non-digit characters except decimal point
        let numValue = value.replace(/[^\d.]/g, '');
        
        // Ensure only one decimal point
        const parts = numValue.split('.');
        if (parts.length > 2) {
            numValue = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Limit to 2 decimal places
        if (parts.length === 2 && parts[1].length > 2) {
            numValue = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        // Split into integer and decimal parts
        const [integerPart, decimalPart] = numValue.split('.');
        
        // Add thousand separators to integer part
        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        // Combine integer and decimal parts
        return decimalPart !== undefined ? formattedInteger + '.' + decimalPart : formattedInteger;
    }

    // Parse formatted number to actual numeric value
    function parseNumber(formattedValue) {
        return parseFloat(formattedValue.replace(/,/g, '')) || 0;
    }

    // Handle input event
    amountInput.addEventListener('input', function(e) {
        const cursorPosition = this.selectionStart;
        const oldValue = this.value;
        const oldLength = oldValue.length;
        
        // Format the value
        const formatted = formatNumber(this.value);
        this.value = formatted;
        
        // Adjust cursor position after formatting
        const newLength = formatted.length;
        const lengthDiff = newLength - oldLength;
        const newCursorPosition = Math.max(0, cursorPosition + lengthDiff);
        this.setSelectionRange(newCursorPosition, newCursorPosition);
        
        // Update hidden field with numeric value
        amountHidden.value = parseNumber(formatted);
    });

    // Handle paste event
    amountInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericValue = parseNumber(pastedText);
        this.value = formatNumber(numericValue.toString());
        amountHidden.value = numericValue;
    });

    // Handle blur - ensure value is properly formatted
    amountInput.addEventListener('blur', function() {
        const numericValue = parseNumber(this.value);
        if (numericValue > 0) {
            this.value = formatNumber(numericValue.toFixed(2));
            amountHidden.value = numericValue.toFixed(2);
        } else {
            this.value = '';
            amountHidden.value = '';
        }
    });

    // Validate before form submission
    form.addEventListener('submit', function(e) {
        const numericValue = parseNumber(amountInput.value);
        
        if (!numericValue || numericValue < 0.01) {
            e.preventDefault();
            alert('Please enter a valid amount (minimum 0.01)');
            amountInput.focus();
            return false;
        }
        
        // Ensure hidden field has the correct numeric value
        amountHidden.value = numericValue.toFixed(2);
    });

    // Initialize hidden field
    amountHidden.value = '';
});
</script>
@endpush
@endsection
