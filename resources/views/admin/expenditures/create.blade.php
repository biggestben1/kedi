@extends('layouts.admin')

@section('title', 'Create Expenditure')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Create Expenditure</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Expenditure Details</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenditures.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Date <span class="text-red">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <i class="fa fa-calendar tx-16 lh-0 op-6"></i>
                                        </div>
                                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Category <span class="text-red">*</span></label>
                                    <select name="category" class="form-control form-select select2" required>
                                        <option value="">-- Select category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Amount (₦) <span class="text-red">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            ₦
                                        </div>
                                        <input type="text" name="amount" class="form-control amount-input" placeholder="0.00" value="{{ old('amount') ? number_format(old('amount'), 2) : '' }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Description <span class="text-red">*</span></label>
                                    <input type="text" name="description" class="form-control" placeholder="Brief description of expense" value="{{ old('description') }}" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Notes / Additional Details</label>
                                    <textarea name="notes" class="form-control" rows="4" placeholder="Any additional information...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{ route('admin.expenditures.index') }}" class="btn btn-danger my-1">Cancel</a>
                            <button type="submit" class="btn btn-primary my-1">Save Expenditure</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.querySelector('.amount-input');
            
            if (amountInput) {
                // Function to format number with commas
                const formatNumber = (value) => {
                    if (!value) return '';
                    // Remove existing commas
                    let num = value.replace(/,/g, '');
                    // Split into integer and decimal parts
                    const parts = num.split('.');
                    // Format integer part with commas
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    // Rejoin
                    return parts.join('.');
                };

                // Format on input
                amountInput.addEventListener('input', function(e) {
                    // Allow only numbers and dot
                    let value = this.value.replace(/[^0-9.]/g, '');
                    
                    // Prevent multiple dots
                    const dots = value.match(/\./g);
                    if (dots && dots.length > 1) {
                        value = value.substring(0, value.lastIndexOf('.'));
                    }

                    this.value = formatNumber(value);
                });

                // Format on blur to ensure 2 decimal places if needed (optional, keeping it simple as per request "on typing")
                amountInput.addEventListener('blur', function() {
                    let value = this.value.replace(/,/g, '');
                    if (value && !isNaN(value)) {
                        const num = parseFloat(value);
                        this.value = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                });
            }
        });
    </script>
    @endpush
