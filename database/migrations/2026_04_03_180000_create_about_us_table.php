<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('image')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_us');
    }
};
