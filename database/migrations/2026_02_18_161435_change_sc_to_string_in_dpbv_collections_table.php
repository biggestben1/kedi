<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dpbv_collections', function (Blueprint $table) {
            // Change sc column from unsignedInteger to nullable string
            // First, convert existing integer values to strings
            DB::statement('ALTER TABLE dpbv_collections MODIFY COLUMN sc VARCHAR(100) NULL');
        });
    }

    public function down(): void
    {
        Schema::table('dpbv_collections', function (Blueprint $table) {
            // Convert back to integer (will lose non-numeric values)
            DB::statement('ALTER TABLE dpbv_collections MODIFY COLUMN sc INT UNSIGNED NULL DEFAULT 0');
        });
    }
};
