<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_post_comments') && ! Schema::hasColumn('blog_post_comments', 'parent_id')) {
            Schema::table('blog_post_comments', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('blog_post_id')
                    ->constrained('blog_post_comments')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_post_comments') && Schema::hasColumn('blog_post_comments', 'parent_id')) {
            Schema::table('blog_post_comments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};
