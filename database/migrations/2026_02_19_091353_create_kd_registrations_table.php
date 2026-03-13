<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kd_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('kd_no', 100)->unique(); // KEDI Member Code (KD NO)
            $table->string('full_name', 255); // As in ID Card/Passport
            $table->enum('gender', ['M', 'F']); // Male or Female
            $table->string('state', 100);
            $table->text('full_address');
            $table->string('phone_number', 50); // Compulsory
            $table->date('registration_date');
            $table->string('applicant_signature', 500)->nullable(); // Signature path or base64
            $table->string('cashier_signature', 500)->nullable(); // Signature path or base64
            
            // Sponsor (Placement) Information
            $table->string('sponsor_kd_no', 100); // Sponsor KEDI No. (KN-...)
            $table->string('sponsor_name', 255);
            $table->string('placement_kd_no', 100)->nullable(); // Placement KEDI No. (KN-...)
            $table->string('placement_name', 255)->nullable();
            $table->string('sponsor_signature', 500)->nullable(); // Signature path or base64
            
            // Relationships
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Link to user account if created
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Who registered this (cashier/admin)
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kd_registrations');
    }
};
