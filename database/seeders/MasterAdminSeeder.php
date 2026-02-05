<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', Role::SUPER_ADMIN)->first();
        if (! $superAdminRole) {
            (new RoleSeeder())->run();
            $superAdminRole = Role::where('name', Role::SUPER_ADMIN)->first();
        }

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Master Admin',
                'email' => 'admin@admin.com',
                'phone' => '+2348050921999',
                'password' => Hash::make('admin'),
                'role_id' => $superAdminRole?->id,
            ]
        );
    }
}
