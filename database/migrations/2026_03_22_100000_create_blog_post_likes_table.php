<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            /** @var string Hash: "u-{userId}" or "g-{sessionId}" — unique per post */
            $table->string('liker_hash', 80);
            $table->timestamps();

            $table->unique(['blog_post_id', 'liker_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_likes');
    }
};
