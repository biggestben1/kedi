<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'topic',
        'slug',
        'body',
        'image',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            $post->slug = static::makeUniqueSlug($post->user_id, $post->title, $post->slug ?: null);
            static::syncPublishTime($post);
        });

        static::updating(function (BlogPost $post) {
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $post->slug = static::makeUniqueSlug($post->user_id, $post->title, null, $post->id);
            } elseif ($post->isDirty('slug')) {
                $post->slug = static::makeUniqueSlug($post->user_id, $post->title, $post->slug, $post->id);
            }
            static::syncPublishTime($post);
        });
    }

    protected static function syncPublishTime(BlogPost $post): void
    {
        if ($post->is_published && $post->published_at === null) {
            $post->published_at = now();
        }
        if (! $post->is_published) {
            $post->published_at = null;
        }
    }

    public static function makeUniqueSlug(int $userId, string $title, ?string $desiredSlug, ?int $ignoreId = null): string
    {
        $base = Str::slug($desiredSlug ?: $title);
        if ($base === '') {
            $base = 'post';
        }

        $slug = $base;
        $n = 2;
        while (static::query()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(BlogPostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogPostComment::class);
    }

    public function likedByCurrentVisitor(): bool
    {
        $hash = BlogPostLike::likerHashForRequest();

        return $this->likes()->where('liker_hash', $hash)->exists();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->body), 200);
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = (string) ($this->image ?? '');
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->exists($path)
            ? url('api/v1/storage/' . ltrim($path, '/'))
            : null;
    }
}
