@extends('layouts.admin')

@section('title', 'Edit Service')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Service: {{ $service->title }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.services.update', $service) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Icon (Emoji)</label>
                        <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror" value="{{ old('icon', $service->icon) }}" required>
                        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Service Title</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $service->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" required>{{ old('description', $service->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $service->sort_order) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
