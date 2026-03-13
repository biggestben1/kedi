@extends('layouts.admin')

@section('title', 'Edit KD Registration')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit KD Registration</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.index') }}">Borrow</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.registration.index') }}">KD Registrations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">KEDI Healthcare Industries Nigeria Ltd. - Application Form</h3>
            <p class="text-muted mb-0">Welcome to KEDI Family & Share The Opportunity To Succeed</p>
            <p class="text-muted small">Please fill with BLOCK LETTERS clearly</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.kd.registration.update', $registration) }}" id="kdRegistrationForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- KEDI Member Code (KD NO) --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">KEDI Member Code (KD NO) <span class="text-danger">*</span></label>
                    <input type="text" name="kd_no" class="form-control form-control-lg text-uppercase @error('kd_no') is-invalid @enderror" 
                           value="{{ old('kd_no', $registration->kd_no) }}" placeholder="e.g. KD-7-0001" required maxlength="100">
                    <small class="text-muted">Paste the KEDI ID No. Sticker that comes with the KEDI ID Card.</small>
                    @error('kd_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Link to User Account --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Link to User Account <small class="text-muted">(Optional)</small></label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">— No user account —</option>
                        @foreach($users ?? [] as $u)
                            <option value="{{ $u->id }}" {{ old('user_id', $registration->user_id) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }}) @if($u->role) - {{ $u->role->display_name ?? $u->role->name }} @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select an existing user account to link this KD registration to, or leave blank.</small>
                    @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Applicant Personal Details --}}
                <h5 class="mb-3 mt-4">Applicant Personal Details</h5>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Full Name (As in ID Card or Passport) <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" 
                               value="{{ old('full_name', $registration->full_name) }}" required maxlength="255">
                        @error('full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_m" value="M" {{ old('gender', $registration->gender) === 'M' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="gender_m">Male (M)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_f" value="F" {{ old('gender', $registration->gender) === 'F' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="gender_f">Female (F)</label>
                            </div>
                        </div>
                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" 
                               value="{{ old('state', $registration->state) }}" required maxlength="100">
                        @error('state')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Full Address <span class="text-danger">*</span></label>
                        <textarea name="full_address" class="form-control @error('full_address') is-invalid @enderror" 
                                  rows="3" required>{{ old('full_address', $registration->full_address) }}</textarea>
                        @error('full_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Phone Number <span class="text-danger">*</span> <small class="text-muted">(Compulsory)</small></label>
                        <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" 
                               value="{{ old('phone_number', $registration->phone_number) }}" required maxlength="50" placeholder="e.g. 08012345678">
                        @error('phone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Registration Date --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Registration Date <span class="text-danger">*</span></label>
                        <input type="date" name="registration_date" class="form-control @error('registration_date') is-invalid @enderror" 
                               value="{{ old('registration_date', $registration->registration_date->format('Y-m-d')) }}" required>
                        @error('registration_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Sponsor (Placement) Information --}}
                <h5 class="mb-3 mt-4">Sponsor (Placement) Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Sponsor: KEDI No. <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">KN</span>
                            <input type="text" name="sponsor_kd_no" class="form-control text-uppercase @error('sponsor_kd_no') is-invalid @enderror" 
                                   value="{{ old('sponsor_kd_no', $registration->sponsor_kd_no) }}" placeholder="e.g. -7-0001" required maxlength="100">
                        </div>
                        @error('sponsor_kd_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sponsor Name <span class="text-danger">*</span></label>
                        <input type="text" name="sponsor_name" class="form-control @error('sponsor_name') is-invalid @enderror" 
                               value="{{ old('sponsor_name', $registration->sponsor_name) }}" required maxlength="255">
                        @error('sponsor_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Placement: KEDI No. <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">KN</span>
                            <input type="text" name="placement_kd_no" class="form-control text-uppercase @error('placement_kd_no') is-invalid @enderror" 
                                   value="{{ old('placement_kd_no', $registration->placement_kd_no) }}" placeholder="e.g. -7-0001" maxlength="100">
                        </div>
                        @error('placement_kd_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Placement Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="placement_name" class="form-control @error('placement_name') is-invalid @enderror" 
                               value="{{ old('placement_name', $registration->placement_name) }}" maxlength="255">
                        @error('placement_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <div class="alert alert-info">
                        <strong>Declaration:</strong> As the Sponsor, I hereby certify that I have presented KEDI Compensation Plan to the Applicant without any distortion.
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mb-3">
                    <label class="form-label">Notes <small class="text-muted">(Optional)</small></label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $registration->notes) }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-save me-2"></i>Update Registration
                    </button>
                    <a href="{{ route('admin.kd.registration.show', $registration) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-format sponsor/placement KD NO
        document.querySelectorAll('input[name="sponsor_kd_no"], input[name="placement_kd_no"]').forEach(input => {
            input.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9-]/g, '');
                if (value && !value.startsWith('-')) {
                    value = '-' + value;
                }
                this.value = value;
            });
        });
    </script>
    @endpush
@endsection
