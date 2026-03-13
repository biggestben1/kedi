<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kedi_kit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kedi_kit_id')->constrained('kedi_kits')->onDelete('cascade');
            $table->foreignId('buyer_user_id')->constrained('users')->onDelete('cascade'); // Who is buying
            $table->foreignId('seller_user_id')->constrained('users')->onDelete('cascade'); // Who is selling (based on hierarchy)
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kedi_kit_purchases');
    }
};
