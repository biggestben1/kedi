<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pvBvRole = DB::table('roles')->where('name', 'pv_bv')->first();
        $pvRole = DB::table('roles')->where('name', 'pv')->first();
        $bvRole = DB::table('roles')->where('name', 'bv')->first();

        if ($pvBvRole && $pvRole) {
            DB::table('users')->where('role_id', $pvRole->id)->update(['role_id' => $pvBvRole->id]);
        }
        if ($pvBvRole && $bvRole) {
            DB::table('users')->where('role_id', $bvRole->id)->update(['role_id' => $pvBvRole->id]);
        }
        if ($pvRole) {
            DB::table('roles')->where('name', 'pv')->delete();
        }
        if ($bvRole) {
            DB::table('roles')->where('name', 'bv')->delete();
        }
    }

    public function down(): void
    {
        $pvBvRole = DB::table('roles')->where('name', 'pv_bv')->first();
        if (! $pvBvRole) {
            return;
        }

        $pvRole = DB::table('roles')->where('name', 'pv')->first();
        $bvRole = DB::table('roles')->where('name', 'bv')->first();

        if (! $pvRole) {
            DB::table('roles')->insert(['name' => 'pv', 'display_name' => 'PV', 'description' => 'View and manage PV reports.', 'created_at' => now(), 'updated_at' => now()]);
            $pvRole = DB::table('roles')->where('name', 'pv')->first();
        }
        if (! $bvRole) {
            DB::table('roles')->insert(['name' => 'bv', 'display_name' => 'BV', 'description' => 'View and manage BV reports.', 'created_at' => now(), 'updated_at' => now()]);
            $bvRole = DB::table('roles')->where('name', 'bv')->first();
        }

        // Note: We cannot reliably restore pv/bv users from pv_bv in down()
    }
};
