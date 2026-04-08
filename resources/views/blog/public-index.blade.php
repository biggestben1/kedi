@extends('layouts.public-blog')

@section('content')
<div class="page-header mb-4">
    <h1 class="page-title">Community blog</h1>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
        <p class="text-muted mb-0">Stories and updates from members.</p>
        <form class="input-group" style="max-width: 320px;" method="get" action="{{ route('blog.index') }}" role="search">
            <input type="text" name="q" class="form-control border-end-0" placeholder="Search …" value="{{ request('q') }}" aria-label="Search blog">
            <button type="submit" class="btn input-group-text bg-transparent border-start-0 text-muted">
                <i class="fe fe-search" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</div>

@if($posts->isEmpty())
    <div class="card p-5 text-center">
        <p class="text-muted mb-3">No published posts yet.</p>
        @auth
            <a href="{{ route('my-blog.create') }}" class="btn btn-primary">Write the first post</a>
        @else
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('login') }}" class="btn btn-primary">Login to write</a>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back to shop</a>
            </div>
        @endauth
    </div>
@else
    <div class="row">
        @foreach($posts as $post)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card h-100 shadow-sm">
                    @if($post->image_url)
                        <a href="{{ route('blog.show', [$post->user, $post]) }}">
                            <img src="{{ $post->image_url }}" class="card-img-top" alt="{{ $post->title }}" style="height: 180px; object-fit: cover;" loading="lazy" onerror="this.onerror=null;this.remove();">
                        </a>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <a href="{{ route('blog.show', [$post->user, $post]) }}" class="text-dark">{{ $post->title }}</a>
                        </h5>
                        @if(filled($post->topic))
                            <p class="small text-muted mb-1"><span class="text-muted">Topic :</span> <span class="fw-medium text-body">{{ $post->topic }}</span></p>
                        @endif
                        <p class="text-muted small mb-2">
                            By <strong>{{ $post->user->name }}</strong>
                            · {{ $post->published_at?->format('M j, Y') }}
                        </p>
                        <p class="card-text text-muted small flex-grow-1">{{ $post->excerpt }}</p>
                        <a href="{{ route('blog.show', [$post->user, $post]) }}" class="btn btn-sm btn-outline-primary mt-2 align-self-start">Read more</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">
        {{ $posts->links() }}
    </div>
@endif
@endsection
