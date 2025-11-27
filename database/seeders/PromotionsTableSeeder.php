<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $discounts = [2, 5, 10, 15];

        for ($i = 1; $i <= 10; $i++) {
            $discount = $discounts[array_rand($discounts)]; // Chọn ngẫu nhiên trong 4 mức
           DB::table('promotions')->updateOrInsert(
    ['promotion_code' => 'PROMO' . $i], // nếu đã tồn tại thì update
    [
        'restaurant_id' => rand(1,5),
        'title' => 'Khuyến mãi đặc biệt ' . $i,
        'description' => "Nhận ngay ưu đãi giảm $discount% cho đơn hàng!",
        'discount_type' => 'percent',
        'discount_value' => $discount,
        'start_date' => Carbon::today()->addDays(rand(-10,0)),
        'end_date' => Carbon::today()->addDays(rand(1,30)),
        'status' => 'active',
        'image' => 'uploads/promotions/1764082728_tải xuống.jpg',
        'created_at' => Carbon::now(),
    ]
);
        }

        echo "✅ Đã tạo 10 khuyến mãi mẫu thành công!\n";
    }
}
