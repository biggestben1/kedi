@extends('layouts.customer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('order-groups.index') }}">Order Groups</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Edit order group</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('order-groups.update', $group) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="form-label" for="name">Group name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $group->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('order-groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
