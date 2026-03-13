<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kedi_kits', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['english', 'french']); // Kit category
            $table->decimal('price', 12, 2)->default(12000.00); // Price per kit (12,000 naira)
            $table->text('description')->nullable(); // Optional description
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade'); // Super Admin who created it
            $table->timestamps();
        });

        Schema::create('kedi_kit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kedi_kit_id')->constrained('kedi_kits')->onDelete('cascade');
            $table->string('kd_no', 100); // KD number in this kit
            $table->string('customer_name', 255)->nullable(); // Optional customer name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kedi_kit_items');
        Schema::dropIfExists('kedi_kits');
    }
};
