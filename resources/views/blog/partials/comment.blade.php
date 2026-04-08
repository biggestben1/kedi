@php
    $depth = $depth ?? 0;
    $displayName = $comment->authorDisplayName();
    $initial = strtoupper(\Illuminate\Support\Str::substr($displayName !== '' ? $displayName : '?', 0, 1));
    $likedByMe = (bool) ($comment->liked_by_me ?? false);
    $likesCount = (int) ($comment->likes_count ?? 0);
    $children = $comment->relationLoaded('children') ? $comment->children : collect();
@endphp
<div class="media mb-4 overflow-visible d-block d-sm-flex blog-comment-item" data-comment-id="{{ $comment->id }}">
    <div class="me-3 mb-2">
        <span class="avatar avatar-md brround bg-primary-transparent text-primary d-inline-flex align-items-center justify-content-center fw-semibold">
            {{ $initial }}
        </span>
    </div>
    <div class="media-body overflow-visible flex-grow-1">
        <div class="border p-4 br-5">
            <h5 class="mt-0 mb-2">{{ $displayName }}</h5>
            @if($comment->user_id)
                <span class="badge bg-primary-transparent text-primary small mb-2">Member</span>
            @else
                <span class="badge bg-secondary-transparent text-secondary small mb-2">Guest</span>
            @endif
            <p class="font-13 text-muted mb-0 text-break">{{ $comment->body }}</p>
            <small class="text-muted d-block mt-2">{{ $comment->created_at->diffForHumans() }}</small>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="button"
                        class="btn btn-sm rounded-pill px-3 blog-comment-like-btn {{ $likedByMe ? 'btn-danger' : 'btn-outline-danger' }}"
                        data-like-url="{{ route('blog.comment.like.toggle', [$author, $post, $comment]) }}"
                        data-comment-id="{{ $comment->id }}"
                        aria-pressed="{{ $likedByMe ? 'true' : 'false' }}">
                    <i class="fe fe-heart me-1" aria-hidden="true"></i>
                    <span class="blog-comment-like-label">{{ $likedByMe ? 'Liked' : 'Like' }}</span>
                    <span class="badge bg-danger-transparent text-danger ms-1 blog-comment-like-count">{{ $likesCount }}</span>
                </button>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary blog-comment-reply-btn"
                        data-comment-id="{{ $comment->id }}"
                        data-author-name="{{ $displayName }}">
                    <i class="fe fe-corner-up-left me-1" aria-hidden="true"></i> Reply
                </button>
            </div>
        </div>

        <div class="comment-replies mt-3 ms-md-3 ps-md-3 border-start border-secondary border-opacity-25" id="comment-replies-{{ $comment->id }}">
            @foreach($children as $child)
                @include('blog.partials.comment', ['comment' => $child, 'author' => $author, 'post' => $post, 'depth' => $depth + 1])
            @endforeach
        </div>
    </div>
</div>
