<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_question_options', function (Blueprint $table) {
            $table->boolean('is_correct')->default(false)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_question_options', function (Blueprint $table) {
            $table->dropColumn('is_correct');
        });
    }
};
