<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesTableSeeder extends Seeder
{
    public function run(): void
    {
        $serviceNames = [
            'Dọn dẹp bàn',
            'Trang trí tiệc',
            'Âm thanh ánh sáng',
            'Nhân viên phục vụ',
            'Bánh kem',
            'Hoa trang trí',
            'Chụp ảnh sự kiện',
            'Quà tặng khách mời',
            'Nước uống',
            'Món ăn phụ',
            'Thiết bị trình chiếu',
            'Âm nhạc sống',
            'Đặt bàn VIP',
            'Thuê MC',
            'Thực đơn đặc biệt',
            'Tổ chức trò chơi',
            'Cho thuê sân khấu',
            'Dịch vụ vệ sinh',
            'Đặt bàn ngoài trời',
            'Setup photobooth',
        ];

        $statuses = ['available', 'unavailable', 'maintenance'];

        for ($i = 1; $i <= 50; $i++) {
            $name = $serviceNames[array_rand($serviceNames)];
            $status = $statuses[array_rand($statuses)];

            DB::table('services')->insert([
                'restaurant_id' => rand(1, 5),
                'name' => $name,
                'description' => 'Dịch vụ ' . strtolower($name),
                'price' => rand(50, 200),
                'status' => $status,
                'image_url' => 'uploads/services/1763997307_q5.jpg',
                'created_at' => now(),
            ]);
        }
    }
}
