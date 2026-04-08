<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\User;
use App\Policies\BlogPostPolicy;
use App\Support\HybridApp;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(BlogPost::class, BlogPostPolicy::class);

        /*
         * Public blog: /blog/{user}/{slug} — slug is unique per author only, so resolve scoped to {user}.
         * My blog (dashboard): /my-blog/{id}/... uses numeric id via explicit {blog_post:id} in routes.
         */
        Route::bind('blog_post', function (string $value, \Illuminate\Routing\Route $route) {
            $uri = $route->uri();
            $name = $route->getName() ?? '';

            $isMyBlog = str_starts_with($uri, 'my-blog/')
                || str_starts_with($name, 'my-blog.');

            if ($isMyBlog) {
                return BlogPost::query()->whereKey($value)->firstOrFail();
            }

            $user = $route->parameter('user');
            $userId = $user instanceof User ? (int) $user->id : (int) $user;

            return BlogPost::query()
                ->where('slug', $value)
                ->where('user_id', $userId)
                ->firstOrFail();
        });

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $view->with('isHybridApp', HybridApp::detect(request()));

            try {
                $cloudFooter = \App\Models\Cloud::query()->orderByDesc('id')->first();
                $view->with('cloudFooter', $cloudFooter);
            } catch (\Throwable $e) {
                $view->with('cloudFooter', null);
            }

            if (auth()->check()) {
                $announcements = \App\Models\Announcement::where('is_active', true)
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->limit(1)
                    ->get();
                $view->with('announcements', $announcements);
            }
        });
    }
}
