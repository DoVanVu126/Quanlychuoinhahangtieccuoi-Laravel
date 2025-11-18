<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách ID nhà hàng hiện có
        $restaurantIds = DB::table('restaurants')->pluck('restaurant_id')->toArray();

        // Nếu chưa có nhà hàng nào thì bỏ qua
        if (empty($restaurantIds)) {
            echo "⚠️ Không có dữ liệu nhà hàng trong bảng restaurants. Vui lòng seed restaurants trước.\n";
            return;
        }

        $statuses = ['maintenance', 'active'];

        for ($i = 1; $i <= 12; $i++) {
            DB::table('halls')->insert([
                'restaurant_id' => $restaurantIds[array_rand($restaurantIds)], // Gán ngẫu nhiên 1 nhà hàng
                'name' => 'Sảnh ' . $i,
                'capacity' => rand(100, 500),
                'price' => rand(5, 20) * 1000000, // 5 - 20 triệu
                'description' => 'Sảnh ' . $i . ' được thiết kế sang trọng, phù hợp tổ chức tiệc cưới và hội nghị.',
                'status' => $statuses[array_rand($statuses)],
                'image_url' => 'uploads/halls/hall' . $i . '.jpg', // hoặc null nếu chưa có ảnh
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "✅ Đã tạo 12 sảnh tiệc mẫu thành công!\n";
    }
}
