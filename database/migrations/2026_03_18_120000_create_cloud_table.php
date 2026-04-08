<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Global footer HTML/text stored for site-wide display (see partials.cloud-footer).
     */
    public function up(): void
    {
        if (! Schema::hasTable('cloud')) {
            Schema::create('cloud', function (Blueprint $table) {
                $table->id();
                $table->text('body');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud');
    }
};
