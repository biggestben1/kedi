<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->dropColumn('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('kedi_kit_items', function (Blueprint $table) {
            $table->string('customer_name', 255)->nullable()->after('kd_no');
        });
    }
};
