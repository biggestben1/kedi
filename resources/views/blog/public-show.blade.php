@extends('layouts.public-blog')

@section('content')
@php
    $body = $post->body;
    $looksLikeHtml = is_string($body) && preg_match('/<[a-z][\s\S]*>/i', $body);
    $blogGuestHasName = auth()->check() || (session()->has('blog_guest_name') && filled(session('blog_guest_name')));
@endphp

{{-- PAGE-HEADER — matches public/sash/html/blog-details.html (page-title + breadcrumb) --}}
<div class="page-header">
    <h1 class="page-title">Blog details</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Shop</a></li>
            <li class="breadcrumb-item"><a href="{{ route('blog.index') }}">Community blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($post->title, 48) }}</li>
        </ol>
    </div>
</div>

<div class="row">
    {{-- Main column (col-xl-8) — Sash blog-details --}}
    <div class="col-xl-8">
        <div class="card">
            {{-- Cover: demo uses blog11.jpg; we use gradient placeholder (add featured_image later if needed) --}}
            @if($post->image_url)
                <img src="{{ $post->image_url }}" class="card-img-top blog-details-cover rounded-top" alt="{{ $post->title }}" style="object-fit: cover;" loading="lazy" onerror="this.onerror=null;this.style.display='none';">
            @else
                <div class="card-img-top blog-details-cover blog-details-cover--placeholder rounded-top">
                    <i class="fe fe-image" aria-hidden="true"></i>
                </div>
            @endif
            <div class="card-body blog-details-meta border-bottom">
                <div class="d-md-flex">
                    <a href="javascript:void(0);" class="d-flex me-4 mb-2 text-decoration-none">
                        <i class="fe fe-calendar fs-16 me-1 p-3 bg-secondary-transparent text-secondary bradius"></i>
                        <div class="mt-0 mt-3 ms-1 text-muted font-weight-semibold">
                            {{ $post->published_at?->format('M-d-Y') }}
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="d-flex mb-2 text-decoration-none">
                        <i class="fe fe-user fs-16 me-1 p-3 bg-primary-transparent text-primary bradius"></i>
                        <div class="mt-0 mt-3 ms-1 text-muted font-weight-semibold">{{ $author->name }}</div>
                    </a>
                    @if(filled($post->topic))
                        <div class="ms-auto">
                            <span class="d-flex mb-2">
                                <i class="fe fe-tag fs-16 me-1 p-3 bg-success-transparent text-success bradius"></i>
                                <div class="mt-0 mt-3 ms-1 text-muted font-weight-semibold">{{ $post->topic }}</div>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <h3 class="mb-3"><a href="javascript:void(0);" class="text-dark text-decoration-none">{{ $post->title }}</a></h3>
                <div class="richText blog-rich-readonly">
                    <div class="richText-editor">
                        @if($looksLikeHtml)
                            {!! $body !!}
                        @else
                            {!! nl2br(e($body)) !!}
                        @endif
                    </div>
                </div>

                {{-- AJAX like / unlike (Sash-style badge + “Like this”) — guests must enter name once (modal) --}}
                <div class="border-top pt-4 mt-4"
                     id="blog-like-section"
                     data-like-url="{{ route('blog.like.toggle', [$author, $post]) }}"
                     data-auth="{{ auth()->check() ? '1' : '0' }}"
                     data-guest-needs-name="{{ (!auth()->check() && !$blogGuestHasName) ? '1' : '0' }}">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <button type="button"
                                class="btn btn-sm rounded-pill px-4 {{ ($likedByMe ?? false) ? 'btn-danger' : 'btn-outline-danger' }}"
                                id="blog-like-btn"
                                aria-pressed="{{ ($likedByMe ?? false) ? 'true' : 'false' }}">
                            <i class="fe fe-heart me-1" id="blog-like-icon" aria-hidden="true"></i>
                            <span id="blog-like-btn-text">{{ ($likedByMe ?? false) ? 'Liked' : 'Like this' }}</span>
                        </button>
                        <a href="javascript:void(0);" class="like text-decoration-none" id="blog-like-badge-wrap" role="status">
                            <span class="badge btn-danger-light rounded-pill py-2 px-3" id="blog-like-count-badge">
                                <i class="fe fe-heart me-1"></i>
                                <span id="blog-like-count-num">{{ (int) ($likesCount ?? 0) }}</span>
                            </span>
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2" id="blog-like-toast"></small>
                </div>
            </div>
        </div>

        {{-- Comments — Sash blog-details style + AJAX --}}
        <div class="card mt-4" id="blog-comment-section"
             data-comment-url="{{ route('blog.comments.store', [$author, $post]) }}"
             data-auth="{{ auth()->check() ? '1' : '0' }}"
             data-guest-needs-name="{{ (!auth()->check() && !$blogGuestHasName) ? '1' : '0' }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title mb-0">Comments</div>
                <span class="badge bg-secondary-transparent text-secondary" id="blog-comments-count-badge">{{ isset($comments) ? $comments->count() : 0 }}</span>
            </div>
            <div class="card-body pb-0" id="blog-comments-list">
                @forelse($comments ?? [] as $comment)
                    @include('blog.partials.comment', ['comment' => $comment])
                @empty
                    <p class="text-muted mb-0" id="blog-comments-empty">No comments yet. Be the first to comment.</p>
                @endforelse
            </div>
            <div class="card-body border-top">
                <p class="fw-semibold mb-3">Add a comment</p>
                <form id="blog-comment-form" action="javascript:void(0)">
                    @auth
                        <div class="mb-3">
                            <label class="form-label">Your name</label>
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }}" readonly tabindex="-1" aria-readonly="true">
                            <small class="text-muted">Comments are posted under your account name.</small>
                        </div>
                    @else
                        <div class="mb-3" id="blog-comment-guest-name-wrap">
                            <label for="blog-comment-guest-name" class="form-label">Your name <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control"
                                   id="blog-comment-guest-name"
                                   name="guest_name"
                                   maxlength="120"
                                   placeholder="e.g. Jane Doe"
                                   autocomplete="name"
                                   value="{{ old('guest_name', session('blog_guest_name')) }}">
                            <small class="text-muted">Shown on your comment. Saved for this session (same as likes).</small>
                        </div>
                    @endauth
                    <div class="mb-3">
                        <label for="blog-comment-body" class="form-label">Your comment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="blog-comment-body" rows="4" required placeholder="Write your comment…" maxlength="5000"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="blog-comment-submit">
                        <i class="fe fe-message-square me-1"></i> Post comment
                    </button>
                    @guest
                        <span class="ms-2 small text-muted">or <a href="{{ route('login') }}">log in</a> to comment as a member.</span>
                    @endguest
                    <small class="text-muted d-block mt-2" id="blog-comment-toast"></small>
                </form>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('blog.index') }}" class="btn btn-outline-secondary btn-sm">All posts</a>
            <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm">Shop</a>
            @auth
                <a href="{{ route('my-blog.index') }}" class="btn btn-outline-primary btn-sm">My blog</a>
            @endauth
        </div>
    </div>

    {{-- Sidebar (col-xl-4) — search, categories/topics, recent posts --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <form class="input-group" method="get" action="{{ route('blog.index') }}" role="search">
                    <input type="text" name="q" class="form-control border-end-0" placeholder="Search …" value="{{ request('q') }}" aria-label="Search blog">
                    <button type="submit" class="btn input-group-text bg-transparent border-start-0 text-muted">
                        <i class="fe fe-search" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>

        @if(isset($topicSidebar) && $topicSidebar->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Topics</div>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($topicSidebar as $row)
                            <li class="list-group-item border-0 p-0">
                                <a href="{{ route('blog.index', ['q' => $row->topic]) }}" class="text-decoration-none">
                                    <i class="fe fe-chevron-right"></i> {{ $row->topic }}
                                </a>
                                <span class="product-label">{{ $row->post_count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(isset($recentPosts) && $recentPosts->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Recent Posts</div>
                </div>
                <div class="card-body">
                    @foreach($recentPosts as $idx => $rp)
                        <div class="d-flex overflow-visible {{ $idx > 0 ? 'mt-5' : '' }}">
                            <div class="recent-post-thumb d-flex align-items-center justify-content-center bg-secondary-transparent overflow-hidden p-0">
                                @if($rp->image_url)
                                    <img src="{{ $rp->image_url }}" alt="{{ $rp->title }}" class="w-100 h-100" width="72" height="72" loading="lazy" decoding="async" style="object-fit: cover;">
                                @else
                                    <i class="fe fe-book-open text-muted" aria-hidden="true"></i>
                                @endif
                            </div>
                            <div class="ps-3 flex-column">
                                <h4 class="fs-16 mb-1">
                                    <a href="{{ route('blog.show', [$rp->user, $rp]) }}" class="text-dark">{{ $rp->title }}</a>
                                </h4>
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($rp->excerpt, 90) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Guest: name required before like (Bootstrap modal) --}}
@guest
<div class="modal fade" id="blog-like-name-modal" tabindex="-1" aria-labelledby="blog-like-name-modal-label" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blog-like-name-modal-label">Like this post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Enter your name to like posts as a guest.</p>
                <div class="mb-3">
                    <label for="blog-like-guest-name-input" class="form-label">Your name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="blog-like-guest-name-input" name="guest_display" maxlength="120" placeholder="e.g. Jane Doe" autocomplete="name">
                    <small class="text-muted">We’ll remember it for this browser session.</small>
                </div>
                <p class="mb-0 small"><a href="{{ route('login') }}">Log in</a> to like with your account (no name prompt).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="blog-like-name-submit">
                    <i class="fe fe-heart me-1"></i> Like
                </button>
            </div>
        </div>
    </div>
</div>
@endguest

@endsection

@push('scripts')
<script>
(function () {
    var section = document.getElementById('blog-like-section');
    if (!section) return;
    var url = section.getAttribute('data-like-url');
    var isAuth = section.getAttribute('data-auth') === '1';
    var guestNeedsName = section.getAttribute('data-guest-needs-name') === '1';
    var btn = document.getElementById('blog-like-btn');
    var btnText = document.getElementById('blog-like-btn-text');
    var countEl = document.getElementById('blog-like-count-num');
    var toast = document.getElementById('blog-like-toast');
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrf = token ? token.getAttribute('content') : '';
    var nameModal = document.getElementById('blog-like-name-modal');
    var nameInput = document.getElementById('blog-like-guest-name-input');
    var nameSubmit = document.getElementById('blog-like-name-submit');
    var modalInstance = null;

    function getModal() {
        if (!nameModal || typeof bootstrap === 'undefined') return null;
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(nameModal);
        }
        return modalInstance;
    }

    function setLikedState(liked) {
        if (!btn || !btnText) return;
        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
        btnText.textContent = liked ? 'Liked' : 'Like this';
        btn.classList.toggle('btn-danger', liked);
        btn.classList.toggle('btn-outline-danger', !liked);
    }

    function showToast(msg) {
        if (toast) toast.textContent = msg || '';
    }

    function markGuestNameSaved() {
        guestNeedsName = false;
        section.setAttribute('data-guest-needs-name', '0');
    }

    function parseResponse(r, text) {
        var data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            data = {};
        }
        if (!r.ok) {
            if (data.requires_name && r.status === 422) {
                var err = new Error(data.message || 'Name required');
                err.requiresName = true;
                throw err;
            }
            var msg = (data && data.message) ? data.message : null;
            if (!msg && r.status === 419) {
                msg = 'Session expired — refresh the page and try again.';
            }
            if (!msg) {
                msg = 'Error ' + r.status + (text && text.indexOf('<!') === 0 ? '' : ': ' + text.slice(0, 80));
            }
            throw new Error(msg);
        }
        return data;
    }

    function postLike(guestName) {
        var formData = new FormData();
        formData.append('_token', csrf);
        if (guestName) {
            formData.append('guest_name', guestName);
        }
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            credentials: 'same-origin',
            body: formData
        }).then(function (r) {
            return r.text().then(function (text) {
                return parseResponse(r, text);
            });
        });
    }

    function applyLikeResult(data) {
        if (typeof data.likes_count === 'number' && countEl) {
            countEl.textContent = data.likes_count;
        }
        setLikedState(!!data.liked);
        showToast(data.message || '');
        if (data.guest_name_saved) {
            markGuestNameSaved();
        }
    }

    btn.addEventListener('click', function () {
        if (!isAuth && guestNeedsName) {
            var m = getModal();
            if (m) {
                m.show();
                if (nameInput) {
                    setTimeout(function () { nameInput.focus(); }, 400);
                }
            } else {
                showToast('Please enter your name to like this post.');
            }
            return;
        }

        btn.disabled = true;
        postLike(null)
            .then(applyLikeResult)
            .catch(function (err) {
                if (err.requiresName) {
                    var m2 = getModal();
                    if (m2) m2.show();
                    showToast(err.message || '');
                } else {
                    showToast(err.message || 'Could not update like. Please refresh and try again.');
                }
            })
            .finally(function () {
                btn.disabled = false;
            });
    });

    if (nameSubmit && nameInput) {
        nameSubmit.addEventListener('click', function () {
            var raw = (nameInput.value || '').trim();
            if (raw.length < 2) {
                showToast('Please enter at least 2 characters for your name.');
                nameInput.focus();
                return;
            }
            nameSubmit.disabled = true;
            postLike(raw)
                .then(function (data) {
                    applyLikeResult(data);
                    var m = getModal();
                    if (m) m.hide();
                })
                .catch(function (err) {
                    showToast(err.message || 'Could not save like.');
                })
                .finally(function () {
                    nameSubmit.disabled = false;
                });
        });
    }
})();

(function () {
    var commentSection = document.getElementById('blog-comment-section');
    if (!commentSection) return;
    var form = document.getElementById('blog-comment-form');
    var submitBtn = document.getElementById('blog-comment-submit');
    var bodyEl = document.getElementById('blog-comment-body');
    var guestNameEl = document.getElementById('blog-comment-guest-name');
    var list = document.getElementById('blog-comments-list');
    var emptyEl = document.getElementById('blog-comments-empty');
    var countBadge = document.getElementById('blog-comments-count-badge');
    var cToast = document.getElementById('blog-comment-toast');
    var commentUrl = commentSection.getAttribute('data-comment-url');
    var parentIdInput = document.getElementById('blog-comment-parent-id');
    var replyBanner = document.getElementById('blog-comment-reply-banner');
    var replyToName = document.getElementById('blog-comment-reply-to-name');
    var cancelReplyBtn = document.getElementById('blog-comment-cancel-reply');
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrf = token ? token.getAttribute('content') : '';

    function cancelReply() {
        if (parentIdInput) parentIdInput.value = '';
        if (replyBanner) replyBanner.classList.add('d-none');
    }

    if (cancelReplyBtn) {
        cancelReplyBtn.addEventListener('click', cancelReply);
    }

    commentSection.addEventListener('click', function (e) {
        var replyBtn = e.target.closest('.blog-comment-reply-btn');
        if (!replyBtn) return;
        var cid = replyBtn.getAttribute('data-comment-id');
        var aname = replyBtn.getAttribute('data-author-name') || 'Comment';
        if (parentIdInput && cid) parentIdInput.value = cid;
        if (replyToName) replyToName.textContent = aname;
        if (replyBanner) replyBanner.classList.remove('d-none');
        if (bodyEl) {
            bodyEl.focus();
            try { bodyEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (err) {}
        }
    });

    function showCommentToast(msg) {
        if (cToast) cToast.textContent = msg || '';
    }

    function parseCommentResponse(r, text) {
        var data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            data = {};
        }
        if (!r.ok) {
            if (data.requires_name && r.status === 422) {
                var err = new Error(data.message || 'Name required');
                err.requiresName = true;
                throw err;
            }
            var msg = (data && data.message) ? data.message : null;
            if (!msg && r.status === 419) {
                msg = 'Session expired — refresh the page and try again.';
            }
            if (!msg) {
                msg = 'Error ' + r.status;
            }
            throw new Error(msg);
        }
        return data;
    }

    function syncGuestNameAfterComment() {
        commentSection.setAttribute('data-guest-needs-name', '0');
        var likeSec = document.getElementById('blog-like-section');
        if (likeSec) {
            likeSec.setAttribute('data-guest-needs-name', '0');
        }
    }

    if (!form || !commentUrl) return;

    var commentIsAuth = commentSection.getAttribute('data-auth') === '1';

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var body = bodyEl ? (bodyEl.value || '').trim() : '';
        if (!body) {
            showCommentToast('Please write a comment.');
            return;
        }
        if (!commentIsAuth) {
            var gnCheck = guestNameEl ? (guestNameEl.value || '').trim() : '';
            if (gnCheck.length < 2) {
                showCommentToast('Please enter your name (at least 2 characters).');
                if (guestNameEl) guestNameEl.focus();
                return;
            }
        }
        var formData = new FormData();
        formData.append('_token', csrf);
        formData.append('body', body);
        if (!commentIsAuth && guestNameEl) {
            formData.append('guest_name', (guestNameEl.value || '').trim());
        }
        if (parentIdInput && parentIdInput.value) {
            formData.append('parent_id', parentIdInput.value);
        }

        submitBtn.disabled = true;
        fetch(commentUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            credentials: 'same-origin',
            body: formData
        })
        .then(function (r) {
            return r.text().then(function (text) {
                return parseCommentResponse(r, text);
            });
        })
        .then(function (data) {
            if (data.html && list) {
                if (emptyEl) {
                    emptyEl.remove();
                }
                var wrap = document.createElement('div');
                wrap.innerHTML = data.html.trim();
                var target = list;
                var parentIdVal = (data.parent_id != null && data.parent_id !== '') ? String(data.parent_id) : '';
                if (parentIdVal) {
                    var repliesEl = document.getElementById('comment-replies-' + parentIdVal);
                    if (repliesEl) target = repliesEl;
                }
                while (wrap.firstChild) {
                    target.appendChild(wrap.firstChild);
                }
            }
            if (typeof data.comments_count === 'number' && countBadge) {
                countBadge.textContent = data.comments_count;
            }
            if (bodyEl) bodyEl.value = '';
            cancelReply();
            showCommentToast(data.message || 'Comment posted.');
            if (data.guest_name_saved) {
                syncGuestNameAfterComment();
            }
        })
        .catch(function (err) {
            if (err.requiresName && guestNameEl) {
                guestNameEl.focus();
            }
            showCommentToast(err.message || 'Could not post comment.');
        })
        .finally(function () {
            submitBtn.disabled = false;
        });
    });
})();

/** Comment likes (delegated); guest name uses same modal as post like */
(function () {
    var commentSection = document.getElementById('blog-comment-section');
    if (!commentSection) return;
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrf = token ? token.getAttribute('content') : '';
    var isAuth = commentSection.getAttribute('data-auth') === '1';
    var guestNeedsName = commentSection.getAttribute('data-guest-needs-name') === '1';
    var nameModal = document.getElementById('blog-like-name-modal');
    var nameInput = document.getElementById('blog-like-guest-name-input');
    var nameSubmit = document.getElementById('blog-like-name-submit');
    var pendingCommentLikeBtn = null;
    var modalInstance = null;

    function getModal() {
        if (!nameModal || typeof bootstrap === 'undefined') return null;
        if (!modalInstance) modalInstance = new bootstrap.Modal(nameModal);
        return modalInstance;
    }

    function parseCommentLikeResponse(r, text) {
        var data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            data = {};
        }
        if (!r.ok) {
            if (data.requires_name && r.status === 422) {
                var err = new Error(data.message || 'Name required');
                err.requiresName = true;
                throw err;
            }
            var msg = (data && data.message) ? data.message : null;
            if (!msg && r.status === 419) {
                msg = 'Session expired — refresh the page and try again.';
            }
            if (!msg) {
                msg = 'Error ' + r.status;
            }
            throw new Error(msg);
        }
        return data;
    }

    function postCommentLike(url, guestName) {
        var formData = new FormData();
        formData.append('_token', csrf);
        if (guestName) {
            formData.append('guest_name', guestName);
        }
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            credentials: 'same-origin',
            body: formData
        }).then(function (r) {
            return r.text().then(function (text) {
                return parseCommentLikeResponse(r, text);
            });
        });
    }

    function markGuestNameSaved() {
        guestNeedsName = false;
        commentSection.setAttribute('data-guest-needs-name', '0');
        var likeSec = document.getElementById('blog-like-section');
        if (likeSec) {
            likeSec.setAttribute('data-guest-needs-name', '0');
        }
    }

    function applyCommentLikeResult(btn, data) {
        var liked = !!data.liked;
        var count = typeof data.likes_count === 'number' ? data.likes_count : 0;
        var label = btn.querySelector('.blog-comment-like-label');
        var countEl = btn.querySelector('.blog-comment-like-count');
        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
        btn.classList.toggle('btn-danger', liked);
        btn.classList.toggle('btn-outline-danger', !liked);
        if (label) label.textContent = liked ? 'Liked' : 'Like';
        if (countEl) countEl.textContent = String(count);
        if (data.guest_name_saved) {
            markGuestNameSaved();
        }
    }

    if (nameModal) {
        nameModal.addEventListener('hidden.bs.modal', function () {
            pendingCommentLikeBtn = null;
        });
    }

    if (nameSubmit && nameInput) {
        nameSubmit.addEventListener('click', function (e) {
            if (!pendingCommentLikeBtn) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            var btn = pendingCommentLikeBtn;
            var url = btn.getAttribute('data-like-url');
            var raw = (nameInput.value || '').trim();
            var likeToast = document.getElementById('blog-like-toast');
            if (raw.length < 2) {
                if (likeToast) likeToast.textContent = 'Please enter at least 2 characters for your name.';
                nameInput.focus();
                return;
            }
            nameSubmit.disabled = true;
            postCommentLike(url, raw)
                .then(function (data) {
                    applyCommentLikeResult(btn, data);
                    var m = getModal();
                    if (m) m.hide();
                })
                .catch(function () {})
                .finally(function () {
                    nameSubmit.disabled = false;
                    pendingCommentLikeBtn = null;
                });
        }, true);
    }

    commentSection.addEventListener('click', function (e) {
        var btn = e.target.closest('.blog-comment-like-btn');
        if (!btn) return;
        var url = btn.getAttribute('data-like-url');
        if (!url) return;

        if (!isAuth && guestNeedsName) {
            pendingCommentLikeBtn = btn;
            var m = getModal();
            if (m) {
                m.show();
                if (nameInput) {
                    setTimeout(function () { nameInput.focus(); }, 400);
                }
            }
            return;
        }

        btn.disabled = true;
        postCommentLike(url, null)
            .then(function (data) {
                applyCommentLikeResult(btn, data);
            })
            .catch(function (err) {
                if (err.requiresName) {
                    pendingCommentLikeBtn = btn;
                    var m2 = getModal();
                    if (m2) m2.show();
                }
            })
            .finally(function () {
                btn.disabled = false;
            });
    });
})();
</script>
@endpush
