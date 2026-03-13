<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
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
