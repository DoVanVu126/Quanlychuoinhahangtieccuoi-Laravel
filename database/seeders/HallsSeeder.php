<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class HallsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Lấy danh sách nhà hàng hiện có để gán hall cho đúng nhà hàng
        $restaurantIds = DB::table('restaurants')->pluck('restaurant_id')->toArray();

        if (empty($restaurantIds)) {
            $this->command->warn('⚠️ Không có nhà hàng nào trong bảng restaurants. Hãy seed restaurants trước.');
            return;
        }

        // Tạo 10 sảnh tiệc giả
        foreach (range(1, 100) as $i) {
            DB::table('halls')->insert([
                'restaurant_id' => $faker->randomElement($restaurantIds),
                'name' => 'Sảnh ' . $faker->randomElement(['A', 'B', 'C', 'D', 'E']) . '-' . $faker->numberBetween(1, 5),
                'capacity' => $faker->numberBetween(100, 1000),
                'price' => $faker->randomFloat(2, 5000000, 50000000),
                'description' => $faker->sentence(10),
                'status' => $faker->randomElement(['available', 'booked', 'maintenance']),
                'image_url' => $faker->imageUrl(640, 480, 'wedding hall', true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Đã thêm dữ liệu giả cho bảng halls thành công!');
    }
}
