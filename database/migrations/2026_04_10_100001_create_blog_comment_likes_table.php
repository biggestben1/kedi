<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_comment_id')->constrained('blog_post_comments')->cascadeOnDelete();
            $table->string('liker_hash', 80);
            $table->string('guest_name', 120)->nullable();
            $table->timestamps();

            $table->unique(['blog_post_comment_id', 'liker_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comment_likes');
    }
};
