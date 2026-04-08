<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_post_likes') && ! Schema::hasColumn('blog_post_likes', 'guest_name')) {
            Schema::table('blog_post_likes', function (Blueprint $table) {
                $table->string('guest_name', 120)->nullable()->after('liker_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_post_likes') && Schema::hasColumn('blog_post_likes', 'guest_name')) {
            Schema::table('blog_post_likes', function (Blueprint $table) {
                $table->dropColumn('guest_name');
            });
        }
    }
};
