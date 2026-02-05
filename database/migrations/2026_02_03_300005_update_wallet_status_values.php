<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map old values (approved) to new label (accepted)
        DB::table('wallet_transactions')
            ->where('status', 'approved')
            ->update(['status' => 'accepted']);

        // Ensure default aligns with new naming
        DB::statement("ALTER TABLE wallet_transactions ALTER COLUMN status SET DEFAULT 'accepted'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_transactions ALTER COLUMN status SET DEFAULT 'approved'");

        DB::table('wallet_transactions')
            ->where('status', 'accepted')
            ->update(['status' => 'approved']);
    }
};

