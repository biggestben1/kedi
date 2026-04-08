<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogPostComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostCommentController extends Controller
{
    /**
     * Store a new comment (AJAX). Guests need a name (session or request).
     */
    public function store(Request $request, User $user, BlogPost $blog_post): JsonResponse
    {
        abort_unless((int) $blog_post->user_id === (int) $user->id, 404);
        abort_unless(
            $blog_post->is_published
            && $blog_post->published_at
            && $blog_post->published_at->lte(now()),
            404
        );

        if (! Schema::hasTable('blog_post_comments')) {
            return response()->json([
                'message' => 'Comments are not set up yet. Run: php artisan migrate',
            ], 503);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'guest_name' => ['nullable', 'string', 'max:120'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('blog_post_comments', 'id')->where('blog_post_id', $blog_post->id),
            ],
        ]);

        $body = trim($data['body']);
        if ($body === '') {
            return response()->json(['message' => 'Comment cannot be empty.'], 422);
        }

        $guestName = null;
        if (! auth()->check()) {
            $guestName = trim((string) ($data['guest_name'] ?? ''));
            if ($guestName === '') {
                $guestName = trim((string) session('blog_guest_name', ''));
            }
            if ($guestName === '') {
                return response()->json([
                    'requires_name' => true,
                    'message' => 'Please enter your name to comment.',
                ], 422);
            }
            if (Str::length($guestName) < 2) {
                return response()->json([
                    'requires_name' => true,
                    'message' => 'Please enter at least 2 characters for your name.',
                ], 422);
            }
            session(['blog_guest_name' => $guestName]);
        }

        $comment = BlogPostComment::query()->create([
            'blog_post_id' => $blog_post->id,
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => auth()->id(),
            'guest_name' => auth()->check() ? null : $guestName,
            'body' => $body,
        ]);

        $comment->load('user');
        $comment->loadCount('likes');
        $comment->setAttribute('liked_by_me', false);
        $comment->setRelation('children', collect());

        $depth = 0;
        $walk = $comment->parent_id ? BlogPostComment::query()->find($comment->parent_id) : null;
        while ($walk) {
            $depth++;
            $walk = $walk->parent_id ? BlogPostComment::query()->find($walk->parent_id) : null;
            if ($depth > 50) {
                break;
            }
        }

        return response()->json([
            'message' => $comment->parent_id ? 'Reply posted.' : 'Comment posted.',
            'guest_name_saved' => auth()->check() ? false : true,
            'parent_id' => $comment->parent_id,
            'html' => view('blog.partials.comment', [
                'comment' => $comment,
                'author' => $user,
                'post' => $blog_post,
                'depth' => $depth,
            ])->render(),
            'comments_count' => (int) $blog_post->comments()->count(),
        ]);
    }
}
