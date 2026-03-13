<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix personal_access_tokens.id missing AUTO_INCREMENT.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE personal_access_tokens MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely reverse - would need to know original definition
    }
};
