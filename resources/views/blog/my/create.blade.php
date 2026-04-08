@extends('layouts.customer')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">New post</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('my-blog.store') }}" id="blog-create-form" enctype="multipart/form-data">
            @csrf
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-title">Title <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="title" id="blog-title" class="form-control" value="{{ old('title', $prefillTitle ?? '') }}" required maxlength="255" autocomplete="off" placeholder="Post title">
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-topic">Topic <span class="text-muted">(optional)</span></label>
                <div class="col-md-9">
                    <input type="text" name="topic" id="blog-topic" class="form-control" value="{{ old('topic') }}" maxlength="120" autocomplete="off" placeholder="e.g. Wellness, News, Product tips">
                    <small class="text-muted">Shown on the public post as <strong>Topic :</strong> …</small>
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-slug">URL slug <span class="text-muted">(optional)</span></label>
                <div class="col-md-9">
                    <input type="text" name="slug" id="blog-slug" class="form-control" value="{{ old('slug', $prefillSlug ?? '') }}" placeholder="auto-filled from title" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" autocomplete="off">
                    <small class="text-muted">Updates as you type the title. Edit for a custom address, or clear to sync from the title again. Leave blank on save to let the server generate from the title.</small>
                </div>
            </div>
            <div class="row mb-4">
                <label class="col-md-3 form-label" for="blog-image">Featured Image <span class="text-muted">(optional)</span></label>
                <div class="col-md-9">
                    <input type="file" name="image" id="blog-image" class="form-control" accept="image/*">
                    <small class="text-muted">Max size: 2MB. Recommended ratio: 16:9.</small>
                </div>
            </div>
            <!-- Same layout as public/sash/html/add-product.html — Product Description -->
            <div class="row">
                <label class="col-md-3 form-label mb-4">Post description :</label>
                <div class="col-md-9 mb-4">
                    <textarea class="content" name="body" id="blog-body" required>{{ old('body') }}</textarea>
                    <small class="text-muted d-block mt-2">Rich text editor matches the Sash admin “Add Product” form (toolbar + white content area).</small>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" {{ old('is_published') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publish immediately</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
    {{-- Same footer action row as add-product.html --}}
    <div class="card-footer">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-9">
                <button type="submit" form="blog-create-form" class="btn btn-primary">Save</button>
                <a href="{{ route('my-blog.index') }}" class="btn btn-default float-end border">Cancel</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('sash/assets/plugins/wysiwyag/jquery.richtext.js') }}"></script>
<script>
(function () {
    $(function () {
        $('.content').richText({
            imageUpload: false,
            fileUpload: false,
            fileHTML: '',
            imageHTML: '',
            height: 300,
        });
    });

    const titleEl = document.getElementById('blog-title');
    const slugEl = document.getElementById('blog-slug');
    if (!titleEl || !slugEl) return;

    const slugManualInitial = {{ json_encode(old('slug') !== null && old('slug') !== '') }};
    const prefillSlug = @json($prefillSlug ?? '');
    let slugManual = slugManualInitial || (prefillSlug !== '');

    function slugify(str) {
        return String(str).toLowerCase().trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    let programmatic = false;

    function setSlugFromTitle() {
        programmatic = true;
        slugEl.value = slugify(titleEl.value);
        programmatic = false;
    }

    titleEl.addEventListener('input', function () {
        if (!slugManual) {
            setSlugFromTitle();
        }
    });

    slugEl.addEventListener('input', function () {
        if (programmatic) return;
        if (slugEl.value === '') {
            slugManual = false;
            setSlugFromTitle();
            return;
        }
        slugManual = true;
    });

    if (!slugManual && titleEl.value) {
        setSlugFromTitle();
    }

    var form = document.getElementById('blog-create-form');
    if (form) {
        form.addEventListener('submit', function () {
            if (window.jQuery) {
                window.jQuery('.richText-editor').trigger('blur');
            }
        });
    }
})();
</script>
@endpush
