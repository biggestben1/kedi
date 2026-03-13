@extends('layouts.admin')

@section('title', 'Create KEDI Kit')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New KEDI Kit</h3>
                    <div class="card-options">
                        <a href="{{ route('admin.kedi-kits.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fe fe-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.kedi-kits.store') }}" method="POST" id="kitForm">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" id="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="english" {{ old('category') === 'english' ? 'selected' : '' }}>English</option>
                                    <option value="french" {{ old('category') === 'french' ? 'selected' : '' }}>French</option>
                                </select>
                                @error('category')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Price (₦) <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="price" class="form-control" value="{{ old('price', 12000) }}" step="0.01" min="0" required>
                                @error('price')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="quantity" class="form-control" value="{{ old('quantity', 0) }}" min="0" required>
                                @error('quantity')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">KD Numbers <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-success" id="addKdRow">
                                    <i class="fe fe-plus"></i> Add KD Number
                                </button>
                            </div>
                            <div id="kdNumbersContainer">
                                <!-- KD numbers will be added here dynamically -->
                            </div>
                            @error('kd_numbers')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> Create Kit
                            </button>
                            <a href="{{ route('admin.kedi-kits.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('kdNumbersContainer');
    const addBtn = document.getElementById('addKdRow');
    let rowCount = 0;

    function addKdRow(kdNo = '', isOld = false, purchasedByUserId = '') {
        rowCount++;
        const row = document.createElement('div');
        row.className = 'row mb-2 kd-row';
        row.dataset.index = rowCount;
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" name="kd_numbers[${rowCount}][kd_no]" class="form-control" placeholder="KD Number" value="${kdNo}" required>
            </div>
            <div class="col-md-3">
                <select name="kd_numbers[${rowCount}][is_old]" class="form-select">
                    <option value="0" ${!isOld ? 'selected' : ''}>New</option>
                    <option value="1" ${isOld ? 'selected' : ''}>Old</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="kd_numbers[${rowCount}][purchased_by_user_id]" class="form-select">
                    <option value="">Select User (Optional)</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="fe fe-trash"></i>
                </button>
            </div>
        `;
        if (purchasedByUserId) {
            const select = row.querySelector('select[name*="[purchased_by_user_id]"]');
            if (select) select.value = purchasedByUserId;
        }
        container.appendChild(row);
    }

    addBtn.addEventListener('click', function() {
        addKdRow();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('.kd-row').remove();
        }
    });

    // Add initial row
    addKdRow();
});
</script>
@endpush
@endsection
