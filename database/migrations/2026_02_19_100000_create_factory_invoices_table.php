<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factory_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100);
            $table->string('factory_name', 255)->nullable();
            $table->date('invoice_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_invoices');
    }
};
