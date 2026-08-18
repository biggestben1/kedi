<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->foreign('created_by_user_id', 'qn_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_id');
            $table->text('body');
            $table->string('type', 32); // text | single_choice
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('questionnaire_id', 'qq_questionnaire_fk')->references('id')->on('questionnaires')->cascadeOnDelete();
        });

        Schema::create('questionnaire_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_question_id');
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('questionnaire_question_id', 'qqo_question_fk')->references('id')->on('questionnaire_questions')->cascadeOnDelete();
        });

        Schema::create('questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_email')->nullable();
            $table->timestamps();
            $table->foreign('questionnaire_id', 'qresp_questionnaire_fk')->references('id')->on('questionnaires')->cascadeOnDelete();
            $table->foreign('user_id', 'qresp_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('questionnaire_response_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_response_id');
            $table->unsignedBigInteger('questionnaire_question_id');
            $table->text('answer_text')->nullable();
            $table->unsignedBigInteger('questionnaire_question_option_id')->nullable();
            $table->timestamps();
            $table->foreign('questionnaire_response_id', 'qra_response_fk')->references('id')->on('questionnaire_responses')->cascadeOnDelete();
            $table->foreign('questionnaire_question_id', 'qra_question_fk')->references('id')->on('questionnaire_questions')->cascadeOnDelete();
            $table->foreign('questionnaire_question_option_id', 'qra_option_fk')->references('id')->on('questionnaire_question_options')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_response_answers');
        Schema::dropIfExists('questionnaire_responses');
        Schema::dropIfExists('questionnaire_question_options');
        Schema::dropIfExists('questionnaire_questions');
        Schema::dropIfExists('questionnaires');
    }
};
