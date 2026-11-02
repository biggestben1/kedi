<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('kd_id', 100)->nullable()->after('customer_name');
            $table->foreignId('order_group_id')->nullable()->after('kd_id')->constrained('order_groups')->nullOnDelete();
            $table->foreignId('collection_branch_id')->nullable()->after('order_group_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_group_id');
            $table->dropConstrainedForeignId('collection_branch_id');
            $table->dropColumn('kd_id');
        });
    }
};
