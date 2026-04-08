@extends('layouts.customer')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h3 class="card-title mb-0">Edit post</h3>
        @if($post->is_published)
            <a href="{{ route('blog.show', [$post->user, $post]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View public</a>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('my-blog.update', $post) }}" id="blog-edit-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row mb-4">
                <label class="col-md-3 form-label">Title <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="title" class="form-control" value="{{ old('title', $post->title) }}" required maxlength="255">
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-topic">Topic <span class="text-muted">(optional)</span></label>
                <div class="col-md-9">
                    <input type="text" name="topic" id="blog-topic" class="form-control" value="{{ old('topic', $post->topic) }}" maxlength="120" autocomplete="off" placeholder="e.g. Wellness, News">
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label">URL slug</label>
                <div class="col-md-9">
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $post->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                    <small class="text-muted">Changing the slug will change the public URL.</small>
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-image">Featured Image <span class="text-muted">(optional)</span></label>
                <div class="col-md-9">
                    @if($post->image_url)
                        <div class="mb-3">
                            <img src="{{ $post->image_url }}" alt="Current image" class="img-thumbnail" style="max-height: 150px;" loading="lazy" onerror="this.onerror=null;this.style.display='none';">
                            <p class="small text-muted">Current image. Upload a new one to replace it.</p>
                        </div>
                    @endif
                    <input type="file" name="image" id="blog-image" class="form-control" accept="image/*">
                    <small class="text-muted">Max size: 2MB. Recommended ratio: 16:9.</small>
                </div>
            </div>
            <div class="row">
                <label class="col-md-3 form-label mb-4">Post description :</label>
                <div class="col-md-9 mb-4">
                    <textarea class="content" name="body" id="blog-body" required>{{ old('body', $post->body) }}</textarea>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('my-blog.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('sash/assets/plugins/wysiwyag/jquery.richtext.js') }}"></script>
<script>
$(function () {
    $('.content').richText({
        imageUpload: false,
        fileUpload: false,
        fileHTML: '',
        imageHTML: '',
        height: 300,
    });
    $('#blog-edit-form').on('submit', function () {
        $('.richText-editor').trigger('blur');
    });
});
</script>
@endpush
