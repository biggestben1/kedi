@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Slider</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.landing-sliders.index') }}">Landing Page Sliders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.landing-sliders.update', $landingSlider) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Update Image (Leave blank to keep current)</label>
                        <input type="file" name="image" class="form-control">
                        @error('image') <span class="text-danger small">{{ $message }}</span> @enderror
                        <div class="mt-2">
                            <label class="form-label">Current Image:</label>
                            @if(str_starts_with($landingSlider->image, 'images/'))
                                <img src="{{ asset($landingSlider->image) }}" alt="" style="height: 100px; border-radius: 5px;">
                            @else
                                <img src="{{ asset('storage/' . $landingSlider->image) }}" alt="" style="height: 100px; border-radius: 5px;">
                            @endif
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Title (Optional)</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') ?? $landingSlider->title }}">
                        @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Sub Title (Optional)</label>
                        <input type="text" name="sub_title" class="form-control" value="{{ old('sub_title') ?? $landingSlider->sub_title }}">
                        @error('sub_title') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Link (Optional)</label>
                        <input type="text" name="link" class="form-control" value="{{ old('link') ?? $landingSlider->link }}">
                        @error('link') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order') ?? $landingSlider->sort_order }}">
                                @error('sort_order') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3 mt-4">
                                <label class="custom-control custom-checkbox mt-4">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="custom-control-input" name="is_active" value="1" {{ $landingSlider->is_active ? 'checked' : '' }}>
                                    <span class="custom-control-label">Is Active?</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary">Update Slider</button>
                        <a href="{{ route('admin.landing-sliders.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
