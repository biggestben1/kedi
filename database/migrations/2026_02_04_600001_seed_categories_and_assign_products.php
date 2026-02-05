<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $categories = [
            ['id' => 1, 'name' => 'Immune & Wellness'],
            ['id' => 2, 'name' => 'Brain & Memory'],
            ['id' => 3, 'name' => 'Heart & Cardiovascular'],
            ['id' => 4, 'name' => 'Women Health'],
            ['id' => 5, 'name' => 'Digestive Health'],
            ['id' => 6, 'name' => 'Blood Support'],
            ['id' => 7, 'name' => 'Eye Support'],
            ['id' => 8, 'name' => 'Bone & Joint'],
            ['id' => 9, 'name' => 'Diabetes Support'],
            ['id' => 10, 'name' => 'Hormonal Support'],
            ['id' => 11, 'name' => 'Herbal & Specialty'],
        ];

        foreach ($categories as $row) {
            $slug = Str::slug($row['name']);
            DB::table('categories')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'slug' => $slug,
                    'sort_order' => $row['id'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $productCategoryMap = [
            1 => ['Golden Six', 'Re-Vive', 'Packet Re-Vive', 'Mini Re-Vive', 'Vigor Essential', 'Cordy Active', 'Small Cordy Active', 'Golden Hypha', 'Small Golden Hypha'],
            2 => ['Memory 24/7', 'Cello Q10', 'Grapemin-E'],
            3 => ['Cardibetter', 'Vitapress', 'Ultamega', 'Lycovite'],
            4 => ['Gynapharm', "Eve's Comfort", 'M&V Women'],
            5 => ['Gastrifort', 'Small Gastrifort', 'Colon Cleanser', 'Refresh Tea', 'Constilease'],
            6 => ['Haemocare'],
            7 => ['Eye Beta'],
            8 => ['Jointeez', 'Calmasine'],
            9 => ['Diawell'],
            10 => ['Cordy Royal Jelly', 'Small Cordy Royal Jelly'],
            11 => ['Qinghao', 'Reishi', 'Small Reishi', 'Lirich', 'Vitagent'],
        ];

        foreach ($productCategoryMap as $categoryId => $names) {
            DB::table('products')->whereIn('name', $names)->update(['category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        DB::table('products')->whereIn('category_id', range(1, 11))->update(['category_id' => null]);
        DB::table('categories')->whereIn('id', range(1, 11))->delete();
    }
};
