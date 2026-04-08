<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostLike extends Model
{
    protected $fillable = [
        'blog_post_id',
        'liker_hash',
        'guest_name',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    public static function likerHashForRequest(): string
    {
        if (auth()->check()) {
            return 'u-'.auth()->id();
        }

        $sid = request()->session()->getId();
        if ($sid === '' || $sid === null) {
            // Fallback when session is not available yet (still unique enough per browser/IP)
            $sid = sha1((string) request()->ip().(string) request()->userAgent().request()->header('Accept-Language', ''));
        }

        return 'g-'.$sid;
    }
}
