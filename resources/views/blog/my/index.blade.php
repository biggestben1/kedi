@extends('layouts.customer')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h3 class="card-title mb-0">Your posts</h3>
        <div>
            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-secondary me-1">View public blog</a>
            <a href="{{ route('my-blog.create') }}" class="btn btn-sm btn-primary"><i class="fe fe-plus me-1"></i>New post</a>
        </div>
    </div>
    <div class="card-body p-0">
        @if($posts->isEmpty())
            <p class="text-muted p-4 mb-0">You have no posts yet. <a href="{{ route('my-blog.create') }}">Write your first post</a>.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Image</th>
                            <th>Title</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                        <tr>
                            <td>
                                @if($post->image)
                                    <img src="{{ asset('storage/' . $post->image) }}" alt="Post image" class="br-7" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center br-7" style="width: 50px; height: 50px;">
                                        <i class="fe fe-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $post->title }}</strong>
                                @if($post->is_published)
                                    <br><small class="text-muted"><a href="{{ route('blog.show', [$post->user, $post]) }}" target="_blank" rel="noopener">Public link</a></small>
                                @endif
                            </td>
                            <td>
                                @if(filled($post->topic))
                                    <span class="text-muted">{{ $post->topic }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($post->is_published)
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td>{{ $post->updated_at->format('M j, Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('my-blog.edit', $post) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('my-blog.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
