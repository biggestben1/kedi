<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function update(User $user, BlogPost $blogPost): bool
    {
        return $user->id === $blogPost->user_id;
    }

    public function delete(User $user, BlogPost $blogPost): bool
    {
        return $user->id === $blogPost->user_id;
    }
}
