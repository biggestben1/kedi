<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->boolean('is_old')->default(false)->after('kd_no');
            $table->foreignId('purchased_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('is_old');
        });
    }

    public function down(): void
    {
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->dropForeign(['purchased_by_user_id']);
            $table->dropColumn(['is_old', 'purchased_by_user_id']);
        });
    }
};
