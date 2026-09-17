<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kd_registrations', function (Blueprint $table) {
            $table->string('phone_number', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kd_registrations', function (Blueprint $table) {
            $table->string('phone_number', 50)->nullable(false)->change();
        });
    }
};
