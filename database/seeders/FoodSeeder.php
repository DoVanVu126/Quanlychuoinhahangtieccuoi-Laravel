<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            ['Gỏi ngó sen tôm thịt', 'Món khai vị thanh mát', 'đĩa', 1],
            ['Bò lúc lắc khoai tây', 'Món chính phổ biến trong tiệc', 'phần', 2],
            ['Gà quay mật ong', 'Món chính hấp dẫn với da giòn', 'con', 2],
            ['Chè hạt sen long nhãn', 'Tráng miệng ngọt mát', 'chén', 3],
            ['Nước ép cam', 'Đồ uống tươi mát', 'ly', 4],
            ['Coca-Cola', 'Thức uống có ga', 'chai', 4],
        ];

        // Tạo vòng lặp chèn nhiều bản ghi
        foreach ($foods as $food) {
            DB::table('foods')->insert([
                'food_type_id' => $food[3],       // tham chiếu tới loại món
                'restaurant_id' => 1,            // giả định nhà hàng ID = 1
                'name' => $food[0],
                'description' => $food[1],
                'unit' => $food[2],
                'image_url' => 'images/foods/' . str_replace(' ', '-', strtolower($food[0])) . '.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // (Tuỳ chọn) — Tạo thêm dữ liệu mẫu ngẫu nhiên bằng vòng lặp for
        for ($i = 1; $i <= 10; $i++) {
            DB::table('foods')->insert([
                'food_type_id' => rand(1, 4),
                'restaurant_id' => 1,
                'name' => 'Món ăn mẫu ' . $i,
                'description' => 'Mô tả món ăn mẫu ' . $i,
                'unit' => 'phần',
                'image_url' => 'images/foods/mon-an-' . $i . '.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
