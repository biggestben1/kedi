@extends('layouts.admin')

@section('title', 'Edit POS Machine')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit POS Machine</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.pos-machines.index') }}">POS Machines</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">POS Machine</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.pos-machines.update', $posMachine) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $posMachine->bank_name) }}" required>
                    @error('bank_name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $posMachine->account_name) }}" required>
                    @error('account_name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $posMachine->account_number) }}" required>
                    @error('account_number')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active', (string) (int) $posMachine->is_active) == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', (string) (int) $posMachine->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $posMachine->notes) }}</textarea>
                    @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Update POS Machine</button>
                    <a href="{{ route('admin.pos-machines.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

