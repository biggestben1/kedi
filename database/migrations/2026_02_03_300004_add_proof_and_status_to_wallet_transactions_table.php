<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('reference'); // pending, approved, rejected
            $table->string('proof_path')->nullable()->after('status'); // stored receipt path
            $table->timestamp('approved_at')->nullable()->after('proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'proof_path', 'approved_at']);
        });
    }
};

