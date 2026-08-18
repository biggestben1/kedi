<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('coupon_code', 100)->nullable()->after('sc_referral_code');
            $table->decimal('coupon_discount_amount', 12, 2)->default(0)->after('discount');
            $table->string('payment_method', 50)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'coupon_discount_amount', 'payment_method']);
        });
    }
};

