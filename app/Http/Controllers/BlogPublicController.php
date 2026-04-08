<?php

namespace App\Http\Controllers;

use App\Models\BlogCommentLike;
use App\Models\BlogPost;
use App\Models\BlogPostComment;
use App\Models\BlogPostLike;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BlogPublicController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::query()
            ->with('user')
            ->published()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('topic', 'like', $term);
                });
            })
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $cartCount = auth()->check()
            ? (int) array_sum($request->session()->get('cart', []))
            : 0;

        return view('blog.public-index', [
            'posts' => $posts,
            'cartCount' => $cartCount,
        ]);
    }

    /**
     * Public post page — no authentication required (guests and members can read).
     * URL shape: /blog/{user_id}/{post_slug}
     */
    public function show(User $user, BlogPost $blog_post)
    {
        abort_unless((int) $blog_post->user_id === (int) $user->id, 404);
        abort_unless(
            $blog_post->is_published
            && $blog_post->published_at
            && $blog_post->published_at->lte(now()),
            404
        );

        $blog_post->load('user');
        $blog_post->loadCount('likes');

        $comments = collect();
        $commentTree = collect();
        if (Schema::hasTable('blog_post_comments')) {
            $comments = $blog_post->comments()
                ->with('user')
                ->withCount('likes')
                ->orderBy('created_at')
                ->get();

            if ($comments->isNotEmpty() && Schema::hasTable('blog_comment_likes')) {
                $hash = BlogPostLike::likerHashForRequest();
                $likedIds = BlogCommentLike::query()
                    ->where('liker_hash', $hash)
                    ->whereIn('blog_post_comment_id', $comments->pluck('id'))
                    ->pluck('blog_post_comment_id');
                foreach ($comments as $c) {
                    $c->setAttribute('liked_by_me', $likedIds->contains($c->id));
                }
            } else {
                foreach ($comments as $c) {
                    $c->setAttribute('liked_by_me', false);
                }
            }

            $commentTree = $this->buildCommentTree($comments);
        }

        $cartCount = auth()->check()
            ? (int) array_sum(request()->session()->get('cart', []))
            : 0;

        $recentPosts = BlogPost::query()
            ->with('user')
            ->published()
            ->where('id', '!=', $blog_post->id)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $topicSidebar = collect();
        if (Schema::hasColumn('blog_posts', 'topic')) {
            $topicSidebar = BlogPost::query()
                ->published()
                ->whereNotNull('topic')
                ->where('topic', '!=', '')
                ->selectRaw('topic, COUNT(*) as post_count')
                ->groupBy('topic')
                ->orderByDesc('post_count')
                ->limit(8)
                ->get();
        }

        return view('blog.public-show', [
            'post' => $blog_post,
            'author' => $user,
            'cartCount' => $cartCount,
            'pageTitle' => $blog_post->title,
            'recentPosts' => $recentPosts,
            'topicSidebar' => $topicSidebar,
            'likesCount' => (int) $blog_post->likes_count,
            'likedByMe' => $blog_post->likedByCurrentVisitor(),
            'comments' => $comments,
            'commentTree' => $commentTree,
        ]);
    }

    /**
     * @param  Collection<int, BlogPostComment>  $flat
     * @return Collection<int, BlogPostComment>
     */
    private function buildCommentTree(Collection $flat, ?int $parentId = null): Collection
    {
        return $flat
            ->filter(function (BlogPostComment $c) use ($parentId) {
                if ($parentId === null) {
                    return $c->parent_id === null;
                }

                return (int) $c->parent_id === $parentId;
            })
            ->values()
            ->map(function (BlogPostComment $c) use ($flat) {
                $c->setRelation('children', $this->buildCommentTree($flat, (int) $c->id));

                return $c;
            });
    }
}
