<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pvBvRole = DB::table('roles')->where('name', 'pv_bv')->first();
        if ($pvBvRole) {
            DB::table('users')->where('role_id', $pvBvRole->id)->update(['role_id' => null]);
            DB::table('roles')->where('name', 'pv_bv')->delete();
        }
    }

    public function down(): void
    {
        if (! DB::table('roles')->where('name', 'pv_bv')->exists()) {
            DB::table('roles')->insert([
                'name' => 'pv_bv',
                'display_name' => 'PV & BV',
                'description' => 'View and manage PV (Personal Volume) and BV (Business Volume) reports.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
