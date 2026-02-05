<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number', 100)->nullable()->after('invoice_number');
            $table->timestamp('packed_at')->nullable()->after('delivered_at');
            $table->timestamp('shipped_at')->nullable()->after('packed_at');
            $table->string('delivery_courier', 255)->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'packed_at', 'shipped_at', 'delivery_courier']);
        });
    }
};
