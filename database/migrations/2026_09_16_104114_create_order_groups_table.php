<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255)->nullable();
            $table->string('kd_id', 100)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('status', 30)->default('open'); // open, paid, cancelled
            $table->string('payment_method', 50)->nullable();
            $table->json('payment_breakdown')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_group_id')->nullable()->after('user_id')->constrained('order_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_group_id');
        });

        Schema::dropIfExists('order_groups');
    }
};
