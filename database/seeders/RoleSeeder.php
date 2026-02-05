<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::SUPER_ADMIN,
                'display_name' => 'Super Admin',
                'description' => 'Full system control, approvals, compliance, audits.',
            ],
            [
                'name' => Role::WHOLESALE_STAFF,
                'display_name' => 'Wholesale Staff',
                'description' => 'Inventory management, order fulfillment, delivery confirmation.',
            ],
            [
                'name' => Role::RESELLER,
                'display_name' => 'Reseller',
                'description' => 'Customer sales, installment setup, wallet & credit tracking.',
            ],
            [
                'name' => Role::CUSTOMER,
                'display_name' => 'Customer',
                'description' => 'Browse products, purchase, pay via wallet or installments, prescription management.',
            ],
            [
                'name' => Role::ACCOUNTANT,
                'display_name' => 'Accountant',
                'description' => 'Financial reconciliation, wallet & credit reporting.',
            ],
            [
                'name' => Role::DISPATCH,
                'display_name' => 'Dispatch',
                'description' => 'Order processing, packaging, shipment, delivery notes.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
