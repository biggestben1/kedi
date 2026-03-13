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
        Schema::create('promo_collections', function (Blueprint $table) {
            $table->id();
            $table->string('promo_name', 255)->nullable()->comment('e.g. RICE WINNER LIST IN 2025.11 PROMO');
            $table->string('shop_no', 100)->nullable();
            $table->string('customer_no', 100)->comment('KD NO');
            $table->string('customer_name', 255);
            $table->string('promo_item', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('promo_meta', 255)->nullable()->comment('e.g. WUNMI 17/12/25');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('customer_no');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_collections');
    }
};
