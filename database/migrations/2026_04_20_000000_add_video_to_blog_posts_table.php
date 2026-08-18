<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('video_original')->nullable()->after('image');
            $table->string('video_mp4')->nullable()->after('video_original');
            $table->string('video_webm')->nullable()->after('video_mp4');
            $table->string('video_status')->nullable()->after('video_webm'); // e.g. pending|ready|failed
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['video_original', 'video_mp4', 'video_webm', 'video_status']);
        });
    }
};

