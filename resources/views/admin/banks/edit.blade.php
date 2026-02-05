@extends('layouts.admin')

@section('title', 'Edit Bank')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Bank</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.banks.index') }}">Banks</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Bank</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.banks.update', $bank) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    @php
                        $bankNames = [
                            'Access Bank Limited', 'Fidelity Bank Plc', 'First City Monument Bank Limited',
                            'First Bank Nigeria Limited', 'Guaranty Trust Bank Limited', 'United Bank of Africa Plc',
                            'Zenith Bank Plc', 'Citibank Nigeria Limited', 'Ecobank Nigeria Limited',
                            'Heritage Bank Plc', 'Globus Bank Limited', 'Keystone Bank Limited',
                            'Polaris Bank Limited', 'Stanbic IBTC Bank Limited', 'Standard Chartered Bank Limited',
                            'Sterling Bank Limited', 'Titan Trust Bank Limited', 'Union Bank of Nigeria Plc',
                            'Unity Bank Plc', 'Wema Bank Plc', 'Premium Trust Bank Limited', 'Optimus Bank Limited',
                            'Providus Bank Limited', 'Parallex Bank Limited', 'Suntrust Bank Nigeria Limited',
                            'Signature Bank Limited', 'Jaiz Bank Plc', 'Taj Bank Limited', 'Lotus Bank Limited',
                            'Alternative Bank Limited'
                        ];
                        $currentName = old('name', $bank->name);
                        $isInList = in_array($currentName, $bankNames);
                    @endphp
                    <select name="name" id="bank_name_select" class="form-select" required>
                        <option value="">— Select Bank —</option>
                        <optgroup label="International Authorization">
                            <option value="Access Bank Limited" {{ $currentName === 'Access Bank Limited' ? 'selected' : '' }}>Access Bank Limited</option>
                            <option value="Fidelity Bank Plc" {{ $currentName === 'Fidelity Bank Plc' ? 'selected' : '' }}>Fidelity Bank Plc</option>
                            <option value="First City Monument Bank Limited" {{ $currentName === 'First City Monument Bank Limited' ? 'selected' : '' }}>First City Monument Bank Limited</option>
                            <option value="First Bank Nigeria Limited" {{ $currentName === 'First Bank Nigeria Limited' ? 'selected' : '' }}>First Bank Nigeria Limited</option>
                            <option value="Guaranty Trust Bank Limited" {{ $currentName === 'Guaranty Trust Bank Limited' ? 'selected' : '' }}>Guaranty Trust Bank Limited</option>
                            <option value="United Bank of Africa Plc" {{ $currentName === 'United Bank of Africa Plc' ? 'selected' : '' }}>United Bank of Africa Plc</option>
                            <option value="Zenith Bank Plc" {{ $currentName === 'Zenith Bank Plc' ? 'selected' : '' }}>Zenith Bank Plc</option>
                        </optgroup>
                        <optgroup label="National Authorization">
                            <option value="Citibank Nigeria Limited" {{ $currentName === 'Citibank Nigeria Limited' ? 'selected' : '' }}>Citibank Nigeria Limited</option>
                            <option value="Ecobank Nigeria Limited" {{ $currentName === 'Ecobank Nigeria Limited' ? 'selected' : '' }}>Ecobank Nigeria Limited</option>
                            <option value="Heritage Bank Plc" {{ $currentName === 'Heritage Bank Plc' ? 'selected' : '' }}>Heritage Bank Plc</option>
                            <option value="Globus Bank Limited" {{ $currentName === 'Globus Bank Limited' ? 'selected' : '' }}>Globus Bank Limited</option>
                            <option value="Keystone Bank Limited" {{ $currentName === 'Keystone Bank Limited' ? 'selected' : '' }}>Keystone Bank Limited</option>
                            <option value="Polaris Bank Limited" {{ $currentName === 'Polaris Bank Limited' ? 'selected' : '' }}>Polaris Bank Limited</option>
                            <option value="Stanbic IBTC Bank Limited" {{ $currentName === 'Stanbic IBTC Bank Limited' ? 'selected' : '' }}>Stanbic IBTC Bank Limited</option>
                            <option value="Standard Chartered Bank Limited" {{ $currentName === 'Standard Chartered Bank Limited' ? 'selected' : '' }}>Standard Chartered Bank Limited</option>
                            <option value="Sterling Bank Limited" {{ $currentName === 'Sterling Bank Limited' ? 'selected' : '' }}>Sterling Bank Limited</option>
                            <option value="Titan Trust Bank Limited" {{ $currentName === 'Titan Trust Bank Limited' ? 'selected' : '' }}>Titan Trust Bank Limited</option>
                            <option value="Union Bank of Nigeria Plc" {{ $currentName === 'Union Bank of Nigeria Plc' ? 'selected' : '' }}>Union Bank of Nigeria Plc</option>
                            <option value="Unity Bank Plc" {{ $currentName === 'Unity Bank Plc' ? 'selected' : '' }}>Unity Bank Plc</option>
                            <option value="Wema Bank Plc" {{ $currentName === 'Wema Bank Plc' ? 'selected' : '' }}>Wema Bank Plc</option>
                            <option value="Premium Trust Bank Limited" {{ $currentName === 'Premium Trust Bank Limited' ? 'selected' : '' }}>Premium Trust Bank Limited</option>
                            <option value="Optimus Bank Limited" {{ $currentName === 'Optimus Bank Limited' ? 'selected' : '' }}>Optimus Bank Limited</option>
                        </optgroup>
                        <optgroup label="Regional Authorization">
                            <option value="Providus Bank Limited" {{ $currentName === 'Providus Bank Limited' ? 'selected' : '' }}>Providus Bank Limited</option>
                            <option value="Parallex Bank Limited" {{ $currentName === 'Parallex Bank Limited' ? 'selected' : '' }}>Parallex Bank Limited</option>
                            <option value="Suntrust Bank Nigeria Limited" {{ $currentName === 'Suntrust Bank Nigeria Limited' ? 'selected' : '' }}>Suntrust Bank Nigeria Limited</option>
                            <option value="Signature Bank Limited" {{ $currentName === 'Signature Bank Limited' ? 'selected' : '' }}>Signature Bank Limited</option>
                        </optgroup>
                        <optgroup label="Non-Interest Banks">
                            <option value="Jaiz Bank Plc" {{ $currentName === 'Jaiz Bank Plc' ? 'selected' : '' }}>Jaiz Bank Plc</option>
                            <option value="Taj Bank Limited" {{ $currentName === 'Taj Bank Limited' ? 'selected' : '' }}>Taj Bank Limited</option>
                            <option value="Lotus Bank Limited" {{ $currentName === 'Lotus Bank Limited' ? 'selected' : '' }}>Lotus Bank Limited</option>
                            <option value="Alternative Bank Limited" {{ $currentName === 'Alternative Bank Limited' ? 'selected' : '' }}>Alternative Bank Limited</option>
                        </optgroup>
                        <option value="__OTHER__" {{ !$isInList ? 'selected' : '' }}>— Other (Enter custom name) —</option>
                    </select>
                    <input type="text" name="name_custom" id="bank_name_custom" class="form-control mt-2" value="{{ !$isInList ? $currentName : old('name_custom') }}" placeholder="Enter bank name" style="{{ !$isInList ? 'display: block;' : 'display: none;' }}">
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $bank->account_name) }}">
                    @error('account_name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $bank->account_number) }}">
                    @error('account_number')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="is_active" {{ old('is_active', $bank->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary">Update Bank</button>
                    @if($bank->is_active)
                        <form action="{{ route('admin.banks.deactivate', $bank) }}" method="POST" class="d-inline" onsubmit="return confirm('Deactivate this bank?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning">Deactivate</button>
                        </form>
                    @else
                        <form action="{{ route('admin.banks.activate', $bank) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">Activate</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.banks.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        var select = document.getElementById('bank_name_select');
        var customInput = document.getElementById('bank_name_custom');
        if (!select || !customInput) return;

        function toggleCustomInput() {
            if (select.value === '__OTHER__') {
                customInput.style.display = 'block';
                customInput.required = true;
                select.required = false;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                select.required = true;
            }
        }

        select.addEventListener('change', toggleCustomInput);
        toggleCustomInput(); // Initial check

        // On form submit, if Other is selected, copy custom value to name field
        var form = select.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (select.value === '__OTHER__') {
                    var customVal = customInput.value.trim();
                    if (!customVal) {
                        e.preventDefault();
                        alert('Please enter a bank name');
                        customInput.focus();
                        return false;
                    }
                    // Create hidden input with the custom name
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'name';
                    hidden.value = customVal;
                    form.appendChild(hidden);
                    select.name = ''; // Remove name from select so it's not submitted
                }
            });
        }
    })();
    </script>
    @endpush
@endsection
