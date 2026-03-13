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
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->foreignId('kedi_kit_purchase_id')->nullable()->after('kedi_kit_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->dropForeign(['kedi_kit_purchase_id']);
            $table->dropColumn('kedi_kit_purchase_id');
        });
    }
};
