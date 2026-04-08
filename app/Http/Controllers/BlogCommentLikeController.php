<?php

namespace App\Http\Controllers;

use App\Models\BlogCommentLike;
use App\Models\BlogPost;
use App\Models\BlogPostComment;
use App\Models\BlogPostLike;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlogCommentLikeController extends Controller
{
    /**
     * Toggle like on a comment (AJAX). Same guest-name rules as post likes.
     */
    public function toggle(Request $request, User $user, BlogPost $blog_post, BlogPostComment $comment): JsonResponse
    {
        abort_unless((int) $blog_post->user_id === (int) $user->id, 404);
        abort_unless($comment->blog_post_id === $blog_post->id, 404);
        abort_unless(
            $blog_post->is_published
            && $blog_post->published_at
            && $blog_post->published_at->lte(now()),
            404
        );

        $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Schema::hasTable('blog_comment_likes')) {
            return response()->json([
                'message' => 'Comment likes are not set up yet. Run: php artisan migrate',
                'likes_count' => 0,
                'liked' => false,
            ], 503);
        }

        $hash = BlogPostLike::likerHashForRequest();

        try {
            $existing = BlogCommentLike::query()
                ->where('blog_post_comment_id', $comment->id)
                ->where('liker_hash', $hash)
                ->first();

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                if (! auth()->check()) {
                    $guestName = trim((string) $request->input('guest_name', ''));
                    if ($guestName === '') {
                        $guestName = trim((string) session('blog_guest_name', ''));
                    }
                    if ($guestName === '') {
                        return response()->json([
                            'requires_name' => true,
                            'message' => 'Please enter your name to like comments.',
                        ], 422);
                    }
                    if (Str::length($guestName) < 2) {
                        return response()->json([
                            'requires_name' => true,
                            'message' => 'Please enter at least 2 characters for your name.',
                        ], 422);
                    }
                    session(['blog_guest_name' => $guestName]);
                    BlogCommentLike::query()->create([
                        'blog_post_comment_id' => $comment->id,
                        'liker_hash' => $hash,
                        'guest_name' => $guestName,
                    ]);
                } else {
                    BlogCommentLike::query()->create([
                        'blog_post_comment_id' => $comment->id,
                        'liker_hash' => $hash,
                        'guest_name' => null,
                    ]);
                }
                $liked = true;
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Could not save like. Run php artisan migrate and try again.',
            ], 500);
        }

        $count = (int) $comment->likes()->count();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $count,
            'comment_id' => $comment->id,
            'message' => $liked ? 'You liked this comment.' : 'Like removed.',
            'guest_name_saved' => auth()->check() ? false : (bool) session('blog_guest_name'),
        ]);
    }
}
