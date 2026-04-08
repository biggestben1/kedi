@extends('layouts.admin')

@section('title', 'Manage About Us')

@push('styles')
<link rel="stylesheet" href="{{ asset('sash/assets/plugins/wysiwyag/richtext.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Edit "About Us" Content</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.about.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group mb-3">
                        <label class="form-label">Section Title</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $about->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Section Content</label>
                        <textarea name="content" id="about-content" class="form-control content @error('content') is-invalid @enderror" rows="10" required>{{ old('content', $about->content) }}</textarea>
                        <small class="text-muted">Use the rich text editor to format your content.</small>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" name="button_text" class="form-control @error('button_text') is-invalid @enderror" value="{{ old('button_text', $about->button_text) }}">
                                @error('button_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Button Link / Action</label>
                                <input type="text" name="button_link" class="form-control @error('button_link') is-invalid @enderror" value="{{ old('button_link', $about->button_link) }}" placeholder="e.g. tel:08067131990 or https://example.com">
                                @error('button_link')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Featured Image (Optional)</label>
                        @if($about->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $about->image) }}" alt="About Us" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror">
                        <small class="text-muted">Will replace the gradient on the landing page if uploaded.</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card-footer text-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Content</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('sash/assets/plugins/wysiwyag/jquery.richtext.js') }}"></script>
<script>
$(function() {
    $('.content').richText({
        imageUpload: false,
        fileUpload: false,
        videoEmbed: false,
        urls: false,
        table: false,
        removeStyles: false,
        code: false,
        height: 300,
    });
});
</script>
@endpush
