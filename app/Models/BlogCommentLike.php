<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogCommentLike extends Model
{
    protected $table = 'blog_comment_likes';

    protected $fillable = [
        'blog_post_comment_id',
        'liker_hash',
        'guest_name',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(BlogPostComment::class, 'blog_post_comment_id');
    }
}
