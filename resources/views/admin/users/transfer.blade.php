@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Transfer User: {{ $user->name }}</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Transfer</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Select New Parent/Place</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.perform-transfer', $user) }}" method="POST">
                    @csrf
                    
                    <div class="form-group mb-4">
                        <label class="form-label">User Information</label>
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Current Role:</strong> {{ $user->role?->display_name ?? $user->role?->name }}</p>
                        <p><strong>Current Parent:</strong> {{ $user->creator->name ?? 'None' }} ({{ $user->creator->role?->display_name ?? 'N/A' }})</p>
                    </div>

                    <div class="form-group">
                        <label for="new_parent_id" class="form-label">New Parent (Place)</label>
                        <select name="new_parent_id" id="new_parent_id" class="form-control select2-show-search form-select" data-placeholder="Choose New Parent" required>
                            <option value=""></option>
                            @foreach($potentialParents as $parent)
                                <option value="{{ $parent->id }}" {{ (old('new_parent_id') == $parent->id) ? 'selected' : '' }}>
                                    {{ $parent->name }} ({{ $parent->role?->display_name ?? $parent->role?->name }}) - {{ $parent->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('new_parent_id')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                        <p class="text-muted small mt-2">
                            This will change who "owns" or "created" this user, effectively moving them to a different branch or headquarters.
                        </p>
                    </div>

                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary">Perform Transfer</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2-show-search').select2({
            minimumResultsForSearch: ''
        });
    });
</script>
@endpush
