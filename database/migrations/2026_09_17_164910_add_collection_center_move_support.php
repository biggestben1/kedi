<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_groups', function (Blueprint $table) {
            $table->foreignId('collection_branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('collection_center_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_group_id')->nullable()->constrained('order_groups')->nullOnDelete();
            $table->foreignId('from_branch_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_branch_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('moved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['to_branch_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_center_moves');

        Schema::table('order_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collection_branch_id');
        });
    }
};
