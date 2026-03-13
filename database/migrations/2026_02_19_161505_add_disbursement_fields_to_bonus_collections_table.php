<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonus_collections', function (Blueprint $table) {
            $table->boolean('is_disbursed')->default(false)->after('user_id');
            $table->timestamp('disbursed_at')->nullable()->after('is_disbursed');
            $table->foreignId('disbursed_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('disbursed_at');
            $table->index('is_disbursed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonus_collections', function (Blueprint $table) {
            $table->dropForeign(['disbursed_by_user_id']);
            $table->dropIndex(['is_disbursed']);
            $table->dropColumn(['is_disbursed', 'disbursed_at', 'disbursed_by_user_id']);
        });
    }
};
