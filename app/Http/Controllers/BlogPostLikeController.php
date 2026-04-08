<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogPostLike;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlogPostLikeController extends Controller
{
    /**
     * Toggle like for the current user or guest session (AJAX).
     * Guests must provide a name (or have one saved in session) before adding a like.
     */
    public function toggle(Request $request, User $user, BlogPost $blog_post): JsonResponse
    {
        abort_unless((int) $blog_post->user_id === (int) $user->id, 404);
        abort_unless(
            $blog_post->is_published
            && $blog_post->published_at
            && $blog_post->published_at->lte(now()),
            404
        );

        $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Schema::hasTable('blog_post_likes')) {
            return response()->json([
                'message' => 'Likes are not set up yet. Run: php artisan migrate',
                'likes_count' => 0,
                'liked' => false,
            ], 503);
        }

        $hash = BlogPostLike::likerHashForRequest();

        try {
            $existing = BlogPostLike::query()
                ->where('blog_post_id', $blog_post->id)
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
                            'message' => 'Please enter your name to like this post.',
                        ], 422);
                    }
                    if (Str::length($guestName) < 2) {
                        return response()->json([
                            'requires_name' => true,
                            'message' => 'Please enter at least 2 characters for your name.',
                        ], 422);
                    }
                    session(['blog_guest_name' => $guestName]);
                    BlogPostLike::query()->create([
                        'blog_post_id' => $blog_post->id,
                        'liker_hash' => $hash,
                        'guest_name' => $guestName,
                    ]);
                } else {
                    BlogPostLike::query()->create([
                        'blog_post_id' => $blog_post->id,
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

        $count = (int) $blog_post->likes()->count();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $count,
            'message' => $liked ? 'You liked this post.' : 'Like removed.',
            'guest_name_saved' => auth()->check() ? false : (bool) session('blog_guest_name'),
        ]);
    }
}
