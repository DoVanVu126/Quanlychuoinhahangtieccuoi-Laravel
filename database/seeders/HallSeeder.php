<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Hall;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantIds = DB::table('restaurants')->pluck('restaurant_id')->toArray();

        if (empty($restaurantIds)) {
            echo "⚠️ Không có dữ liệu nhà hàng trong bảng restaurants. Vui lòng seed restaurants trước.\n";
            return;
        }

        $statuses = ['maintenance', 'available'];

        for ($i = 1; $i <= 20; $i++) {
            $hallData = [
                'restaurant_id' => $restaurantIds[array_rand($restaurantIds)],
                'name' => 'Sảnh ' . $i,
                'capacity' => rand(100, 500),
                'price' => rand(5, 20) * 1000000,
                'description' => 'Sảnh ' . $i . ' được thiết kế sang trọng, phù hợp tổ chức tiệc cưới và hội nghị.',
                'status' => $statuses[array_rand($statuses)],
                'image_url' => 'uploads/halls/1763999441_anh3.jpg',
            ];

            // ✅ update nếu có hall_id = $i, nếu chưa có thì tạo mới
            Hall::updateOrCreate(
                ['hall_id' => $i], // tìm theo hall_id
                $hallData           // update hoặc tạo mới với dữ liệu này
            );
        }

        echo "✅ Đã tạo hoặc cập nhật 20 sảnh tiệc mẫu thành công!\n";
    }
}
