<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kd_registrations', function (Blueprint $table) {
            $table->enum('gender', ['M', 'F'])->nullable()->change();
            $table->string('state', 100)->nullable()->change();
            $table->text('full_address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kd_registrations', function (Blueprint $table) {
            $table->enum('gender', ['M', 'F'])->nullable(false)->change();
            $table->string('state', 100)->nullable(false)->change();
            $table->text('full_address')->nullable(false)->change();
        });
    }
};
