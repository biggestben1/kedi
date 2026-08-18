<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_machines', function (Blueprint $table) {
            // New required fields
            $table->string('bank_name')->nullable()->after('id');
            $table->string('account_name')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_name');
        });

        // Backfill from old columns where possible (best effort)
        if (Schema::hasColumn('pos_machines', 'name')) {
            \DB::table('pos_machines')->whereNull('bank_name')->update([
                'bank_name' => \DB::raw('`name`'),
            ]);
        }
        if (Schema::hasColumn('pos_machines', 'serial_number')) {
            \DB::table('pos_machines')->whereNull('account_number')->update([
                'account_number' => \DB::raw('`serial_number`'),
            ]);
        }

        // Make account_number unique after backfill
        Schema::table('pos_machines', function (Blueprint $table) {
            $table->unique('account_number');
        });

        // Drop old POS fields we no longer use
        Schema::table('pos_machines', function (Blueprint $table) {
            if (Schema::hasColumn('pos_machines', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
            if (Schema::hasColumn('pos_machines', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('pos_machines', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
            if (Schema::hasColumn('pos_machines', 'machine_model')) {
                $table->dropColumn('machine_model');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_machines', function (Blueprint $table) {
            // Restore old columns (without the old data guarantees)
            $table->string('name')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('machine_model')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->dropUnique(['account_number']);
            $table->dropColumn(['bank_name', 'account_name', 'account_number']);
        });
    }
};

