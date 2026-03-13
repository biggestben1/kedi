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
        Schema::create('dpbv_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('no')->nullable()->comment('Row number from Excel');
            $table->string('code', 100)->comment('KD NO (e.g. KN79569)');
            $table->string('name', 255);
            $table->date('record_date');
            $table->unsignedInteger('sc')->default(0);
            $table->decimal('dpbv', 10, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('code');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dpbv_collections');
    }
};
