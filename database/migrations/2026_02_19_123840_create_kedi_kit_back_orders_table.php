<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kedi_kit_back_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kedi_kit_id')->constrained('kedi_kits')->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('kedi_kit_purchases')->onDelete('cascade');
            $table->foreignId('buyer_user_id')->constrained('users')->onDelete('cascade');
            $table->integer('quantity_pending'); // Quantity in back order
            $table->integer('quantity_fulfilled')->default(0); // Quantity fulfilled
            $table->enum('status', ['pending', 'fulfilled', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kedi_kit_back_orders');
    }
};
