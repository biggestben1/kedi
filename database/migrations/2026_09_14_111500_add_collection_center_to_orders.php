<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('collection_branch_id')->nullable()->after('branch_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable()->after('stock_deducted_at');
            $table->foreignId('collected_by_user_id')->nullable()->after('collected_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->foreignId('branch_user_id')->nullable()->after('headquarters_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('pos_machines', function (Blueprint $table) {
            $table->foreignId('branch_user_id')->nullable()->after('account_number')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_machines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_user_id');
        });
        Schema::table('banks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_user_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by_user_id');
            $table->dropColumn('collected_at');
            $table->dropConstrainedForeignId('collection_branch_id');
        });
    }
};
