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
        Schema::create('bonus_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('no')->nullable();
            $table->string('code', 100)->comment('KD NO');
            $table->string('name', 255);
            $table->date('record_date');
            $table->unsignedInteger('sc')->default(0);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('honorary', 50)->nullable();
            $table->decimal('total', 12, 2)->default(0);
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
        Schema::dropIfExists('bonus_collections');
    }
};
