<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserBlogController extends Controller
{
    private function cartCount(Request $request): int
    {
        return (int) array_sum($request->session()->get('cart', []));
    }

    public function index(Request $request)
    {
        $posts = BlogPost::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('blog.my.index', [
            'posts' => $posts,
            'cartCount' => $this->cartCount($request),
            'pageTitle' => 'My Blog',
            'customerMenuActive' => 'blog',
        ]);
    }

    public function create(Request $request)
    {
        $prefillTitle = (string) $request->query('title', '');
        $prefillSlug = (string) $request->query('slug', '');

        return view('blog.my.create', [
            'cartCount' => $this->cartCount($request),
            'pageTitle' => 'New blog post',
            'customerMenuActive' => 'blog',
            'prefillTitle' => $prefillTitle,
            'prefillSlug' => $prefillSlug,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? \Illuminate\Support\Str::slug($data['slug'])
            : null;

        $topic = trim((string) ($data['topic'] ?? ''));

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blog_images', 'public');
        }

        $post = new BlogPost([
            'title' => $data['title'],
            'topic' => $topic !== '' ? $topic : null,
            'slug' => $slug ?? '',
            'body' => $data['body'],
            'image' => $imagePath,
            'is_published' => $request->boolean('is_published'),
        ]);
        $post->user_id = $user->id;
        $post->save();

        return redirect()->route('my-blog.index')
            ->with('success', $post->is_published ? 'Post published.' : 'Draft saved.');
    }

    public function edit(Request $request, BlogPost $blog_post)
    {
        $this->authorize('update', $blog_post);

        return view('blog.my.edit', [
            'post' => $blog_post,
            'cartCount' => $this->cartCount($request),
            'pageTitle' => 'Edit blog post',
            'customerMenuActive' => 'blog',
        ]);
    }

    public function update(Request $request, BlogPost $blog_post)
    {
        $this->authorize('update', $blog_post);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_posts', 'slug')->where('user_id', $blog_post->user_id)->ignore($blog_post->id),
            ],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $blog_post->title = $data['title'];
        $topic = trim((string) ($data['topic'] ?? ''));
        $blog_post->topic = $topic !== '' ? $topic : null;
        if (! empty(trim($data['slug'] ?? ''))) {
            $blog_post->slug = \Illuminate\Support\Str::slug($data['slug']);
        }
        $blog_post->body = $data['body'];

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($blog_post->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($blog_post->image);
            }
            $blog_post->image = $request->file('image')->store('blog_images', 'public');
        }

        $blog_post->is_published = $request->boolean('is_published');
        $blog_post->save();

        return redirect()->route('my-blog.index')
            ->with('success', 'Post updated.');
    }

    public function destroy(Request $request, BlogPost $blog_post)
    {
        $this->authorize('delete', $blog_post);
        $blog_post->delete();

        return redirect()->route('my-blog.index')
            ->with('success', 'Post deleted.');
    }
}
