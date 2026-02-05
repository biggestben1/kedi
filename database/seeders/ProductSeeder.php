<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['item_code' => 'AB1', 'name' => 'Golden Six', 'pack_size' => '30s', 'bv' => 12.0, 'pv' => 12.0, 'price' => 14000],
            ['item_code' => 'A02', 'name' => 'Magilim', 'pack_size' => '90s', 'bv' => 25.0, 'pv' => 25.0, 'price' => 26000],
            ['item_code' => 'A03', 'name' => 'Re-Vive', 'pack_size' => '30s', 'bv' => 27.0, 'pv' => 37.0, 'price' => 40500],
            ['item_code' => 'A031', 'name' => 'Packet Re-Vive', 'pack_size' => '10s', 'bv' => 8.0, 'pv' => 13.0, 'price' => 14500],
            ['item_code' => 'A032', 'name' => 'Mini Re-Vive', 'pack_size' => '4s x 1 Pack', 'bv' => 3.0, 'pv' => 5.5, 'price' => 5500],
            ['item_code' => 'A04', 'name' => 'Vigor Essential', 'pack_size' => '30s', 'bv' => 20.0, 'pv' => 20.0, 'price' => 21000],
            ['item_code' => 'A05', 'name' => 'Cordy Active', 'pack_size' => '60s', 'bv' => 18.0, 'pv' => 18.0, 'price' => 21000],
            ['item_code' => 'A051', 'name' => 'Small Cordy Active', 'pack_size' => '30s', 'bv' => 9.5, 'pv' => 9.5, 'price' => 12000],
            ['item_code' => 'A06', 'name' => 'Golden Hypha', 'pack_size' => '90s', 'bv' => 38.5, 'pv' => 38.5, 'price' => 42500],
            ['item_code' => 'A061', 'name' => 'Small Golden Hypha', 'pack_size' => '30s', 'bv' => 13.5, 'pv' => 13.5, 'price' => 15000],
            ['item_code' => 'A07', 'name' => 'Constilease', 'pack_size' => '60s', 'bv' => 22.0, 'pv' => 22.0, 'price' => 24000],
            ['item_code' => 'A08', 'name' => 'Reishi', 'pack_size' => '90s', 'bv' => 27.5, 'pv' => 27.5, 'price' => 31500],
            ['item_code' => 'A081', 'name' => 'Small Reishi', 'pack_size' => '30s', 'bv' => 10.0, 'pv' => 10.0, 'price' => 12500],
            ['item_code' => 'A09', 'name' => 'Gastrifort', 'pack_size' => '90s', 'bv' => 33.0, 'pv' => 33.0, 'price' => 37000],
            ['item_code' => 'A091', 'name' => 'Small Gastrifort', 'pack_size' => '30s', 'bv' => 11.5, 'pv' => 11.5, 'price' => 14000],
            ['item_code' => 'A10', 'name' => 'Cordy Royal Jelly', 'pack_size' => '90s', 'bv' => 28.0, 'pv' => 28.0, 'price' => 31500],
            ['item_code' => 'A101', 'name' => 'Small Cordy Royal Jelly', 'pack_size' => '30s', 'bv' => 9.5, 'pv' => 9.5, 'price' => 12000],
            ['item_code' => 'A11', 'name' => 'Qinghao', 'pack_size' => '8s', 'bv' => 4.0, 'pv' => 7.0, 'price' => 8900],
            ['item_code' => 'A12', 'name' => 'Haemocare', 'pack_size' => '30s', 'bv' => 20.0, 'pv' => 20.0, 'price' => 20000],
            ['item_code' => 'A13', 'name' => 'Eye Beta', 'pack_size' => '30s', 'bv' => 22.0, 'pv' => 22.0, 'price' => 23000],
            ['item_code' => 'L01', 'name' => 'Gynapharm', 'pack_size' => '72s', 'bv' => 32.0, 'pv' => 32.0, 'price' => 33000],
            ['item_code' => 'L02', 'name' => "Eve's Comfort", 'pack_size' => '36s', 'bv' => 22.0, 'pv' => 24.0, 'price' => 24900],
            ['item_code' => 'L05', 'name' => 'Jointeez', 'pack_size' => '50s', 'bv' => 10.0, 'pv' => 12.0, 'price' => 14000],
            ['item_code' => 'L06', 'name' => 'Diawell', 'pack_size' => '40s', 'bv' => 13.0, 'pv' => 13.0, 'price' => 15000],
            ['item_code' => 'L07', 'name' => 'Cardibetter', 'pack_size' => '60s', 'bv' => 26.0, 'pv' => 26.0, 'price' => 28500],
            ['item_code' => 'L08', 'name' => 'Lirich', 'pack_size' => '30s', 'bv' => 16.0, 'pv' => 16.0, 'price' => 18500],
            ['item_code' => 'T01', 'name' => 'Refresh Tea', 'pack_size' => '20s', 'bv' => 11.5, 'pv' => 11.5, 'price' => 13000],
            ['item_code' => 'T02', 'name' => 'Colon Cleanser', 'pack_size' => '20s', 'bv' => 12.5, 'pv' => 12.5, 'price' => 16000],
            ['item_code' => 'V01', 'name' => 'Ultamega', 'pack_size' => '60s', 'bv' => 12.0, 'pv' => 17.0, 'price' => 18000],
            ['item_code' => 'V02', 'name' => 'Calmasine', 'pack_size' => '60s', 'bv' => 13.0, 'pv' => 18.0, 'price' => 29000],
            ['item_code' => 'V03', 'name' => 'Lycovite', 'pack_size' => '60s', 'bv' => 20.0, 'pv' => 21.0, 'price' => 22000],
            ['item_code' => 'V04', 'name' => 'Cello Q10', 'pack_size' => '60s', 'bv' => 27.0, 'pv' => 29.0, 'price' => 30000],
            ['item_code' => 'V05', 'name' => 'Vitapress', 'pack_size' => '60s', 'bv' => 21.0, 'pv' => 25.0, 'price' => 26000],
            ['item_code' => 'V06', 'name' => 'Memory 24/7', 'pack_size' => '60s', 'bv' => 22.0, 'pv' => 22.0, 'price' => 28000],
            ['item_code' => 'V07', 'name' => 'Grapemin-E', 'pack_size' => '60s', 'bv' => 22.0, 'pv' => 27.0, 'price' => 29000],
            ['item_code' => 'V08', 'name' => 'Vitagent', 'pack_size' => '60s', 'bv' => 20.0, 'pv' => 25.0, 'price' => 28000],
            ['item_code' => 'V09', 'name' => 'M&V Women', 'pack_size' => '60s', 'bv' => 20.0, 'pv' => 25.0, 'price' => 26000],
        ];

        foreach ($products as $index => $product) {
            Product::updateOrCreate(
                ['item_code' => $product['item_code']],
                array_merge($product, ['sort_order' => $index + 1])
            );
        }
    }
}
