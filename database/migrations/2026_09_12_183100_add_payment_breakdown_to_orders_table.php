<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('pos_amount_paid', 12, 2)->nullable()->after('payment_method');
            $table->json('payment_breakdown')->nullable()->after('pos_amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pos_amount_paid', 'payment_breakdown']);
        });
    }
};
