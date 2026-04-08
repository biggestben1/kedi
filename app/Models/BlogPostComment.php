<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogPostComment extends Model
{
    protected $fillable = [
        'blog_post_id',
        'parent_id',
        'user_id',
        'guest_name',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
        ];
    }

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogPostComment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BlogPostComment::class, 'parent_id')->orderBy('created_at');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(BlogCommentLike::class, 'blog_post_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorDisplayName(): string
    {
        if ($this->user_id && $this->relationLoaded('user') && $this->user) {
            return (string) $this->user->name;
        }

        return (string) ($this->guest_name ?? 'Guest');
    }
}
