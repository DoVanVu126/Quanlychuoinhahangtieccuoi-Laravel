<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Khai vị', 'Các món ăn nhẹ trước bữa chính'],
            ['Món chính', 'Các món chính trong bữa tiệc'],
            ['Tráng miệng', 'Các món ngọt sau bữa ăn'],
            ['Đồ uống', 'Các loại nước uống đi kèm'],
        ];

        foreach ($types as $type) {
            DB::table('food_types')->insert([
                'name' => $type[0],
                'description' => $type[1],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
