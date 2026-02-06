<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoStockSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->update(['stock' => 100]);
    }
}
