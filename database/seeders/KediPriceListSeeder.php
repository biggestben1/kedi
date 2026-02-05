<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Updates products from KEDI price list.
 *
 * Option 1 – CSV: Place a file at database/data/kedi_price_list.csv with header:
 *   item_code,name,pack_size,bv,pv,member_price,retail_price
 * (retail_price is optional; stored as cost_price.)
 *
 * Option 2 – Inline: Edit the $products array below and run without a CSV.
 *
 * Run: php artisan db:seed --class=KediPriceListSeeder
 */
class KediPriceListSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('database/data/kedi_price_list.csv');

        if (File::exists($path)) {
            $this->updateFromCsv($path);
            return;
        }

        $this->updateFromArray($this->defaultProducts());
    }

    protected function updateFromCsv(string $path): void
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->command?->warn("Could not open: {$path}");
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return;
        }

        $header = array_map('trim', $header);
        $findCol = function ($keys) use ($header) {
            foreach ((array) $keys as $key) {
                $i = array_search($key, $header, true);
                if ($i !== false) {
                    return $i;
                }
            }
            return false;
        };
        $idx = [
            'item_code' => $findCol(['item_code', 'ITEM', 'Item']),
            'name' => $findCol(['name', 'products', 'PRODUCTS', 'Product']),
            'pack_size' => $findCol(['pack_size', 'unit', 'UNIT', 'Unit']),
            'bv' => $findCol(['bv', 'BV']),
            'pv' => $findCol(['pv', 'PV']),
            'member_price' => $findCol(['member_price', 'Member Price (N)', 'Member Price']),
            'retail_price' => $findCol(['retail_price', 'Retail Price (N)', 'Retail Price']),
        ];

        $sortOrder = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $sortOrder++;
            $itemCode = isset($idx['item_code']) && $idx['item_code'] !== false ? trim($row[$idx['item_code']] ?? '') : '';
            if ($itemCode === '' || $itemCode === 'ITEM' || $itemCode === 'item_code') {
                $sortOrder--;
                continue;
            }

            $name = isset($idx['name']) && $idx['name'] !== false ? trim($row[$idx['name']] ?? '') : '';
            $packSize = (isset($idx['pack_size']) && $idx['pack_size'] !== false ? trim($row[$idx['pack_size']] ?? '') : '') ?: null;
            $bv = isset($idx['bv']) && $idx['bv'] !== false ? $this->parseDecimal($row[$idx['bv']] ?? 0) : 0;
            $pv = isset($idx['pv']) && $idx['pv'] !== false ? $this->parseDecimal($row[$idx['pv']] ?? 0) : 0;
            $memberPrice = isset($idx['member_price']) && $idx['member_price'] !== false ? $this->parseDecimal($row[$idx['member_price']] ?? 0) : 0;
            $retailPrice = isset($idx['retail_price']) && $idx['retail_price'] !== false ? $this->parseDecimal($row[$idx['retail_price']] ?? null) : null;

            Product::updateOrCreate(
                ['item_code' => $itemCode],
                [
                    'name' => $name ?: 'Product ' . $itemCode,
                    'pack_size' => $packSize,
                    'bv' => $bv,
                    'pv' => $pv,
                    'price' => $memberPrice,
                    'cost_price' => $retailPrice,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]
            );
        }

        fclose($handle);
        $this->command?->info('Products updated from CSV.');
    }

    protected function parseDecimal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) preg_replace('/[^0-9.-]/', '', (string) $value);
    }

    protected function updateFromArray(array $products): void
    {
        foreach ($products as $index => $row) {
            $itemCode = $row['item_code'] ?? $row['ITEM'] ?? '';
            if ($itemCode === '') {
                continue;
            }

            Product::updateOrCreate(
                ['item_code' => $itemCode],
                [
                    'name' => $row['name'] ?? $row['PRODUCTS'] ?? 'Product ' . $itemCode,
                    'pack_size' => $row['pack_size'] ?? $row['UNIT'] ?? null,
                    'bv' => (float) ($row['bv'] ?? $row['BV'] ?? 0),
                    'pv' => (float) ($row['pv'] ?? $row['PV'] ?? 0),
                    'price' => (float) ($row['member_price'] ?? $row['price'] ?? $row['Member Price (N)'] ?? 0),
                    'cost_price' => isset($row['retail_price']) || isset($row['Retail Price (N)']) ? (float) ($row['retail_price'] ?? $row['Retail Price (N)'] ?? null) : null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Products updated from inline list.');
    }

    /**
     * Default list – replace with your full KEDI price list or use a CSV.
     */
    protected function defaultProducts(): array
    {
        return [
            ['item_code' => 'A01', 'name' => 'Golden Six', 'pack_size' => 'Bottle', 'bv' => 12, 'pv' => 12, 'member_price' => 14000, 'retail_price' => 0],
            ['item_code' => 'A02', 'name' => 'Magilim', 'pack_size' => 'Packet', 'bv' => 25, 'pv' => 25, 'member_price' => 26000, 'retail_price' => 0],
            ['item_code' => 'A03', 'name' => 'Re-vive (30 Caps)', 'pack_size' => '30s', 'bv' => 27, 'pv' => 37, 'member_price' => 40500, 'retail_price' => 0],
            ['item_code' => 'T01', 'name' => 'Refresh Tea', 'pack_size' => '20s', 'bv' => 11.5, 'pv' => 11.5, 'member_price' => 13000, 'retail_price' => 0],
            ['item_code' => 'L01', 'name' => 'Gynapharm Capsule', 'pack_size' => '72s', 'bv' => 32, 'pv' => 32, 'member_price' => 33000, 'retail_price' => 0],
            ['item_code' => 'V01', 'name' => 'Ultramega', 'pack_size' => '60s', 'bv' => 12, 'pv' => 17, 'member_price' => 18000, 'retail_price' => 0],
            ['item_code' => 'V02', 'name' => 'Blood Circulatory Instrument', 'pack_size' => '60s', 'bv' => 13, 'pv' => 18, 'member_price' => 29000, 'retail_price' => 0],
        ];
    }
}
