<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory_invoices', function (Blueprint $table) {
            $table->timestamp('stock_added_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('factory_invoices', function (Blueprint $table) {
            $table->dropColumn('stock_added_at');
        });
    }
};
