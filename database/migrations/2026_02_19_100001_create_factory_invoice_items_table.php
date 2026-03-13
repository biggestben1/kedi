<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factory_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_code', 50)->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(0);
            $table->string('status', 30)->default('achievement'); // achievement, borrow, dpbv, promo, backorder
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_invoice_items');
    }
};
