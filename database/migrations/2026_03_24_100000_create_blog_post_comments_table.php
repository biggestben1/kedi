<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name', 120)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['blog_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_comments');
    }
};
