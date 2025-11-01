<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Lấy danh sách ID sẵn có từ 2 bảng liên quan
        $restaurantIds = DB::table('restaurants')->pluck('restaurant_id')->toArray();
        $userIds = DB::table('users')->pluck('user_id')->toArray();

        // Nếu chưa có dữ liệu ở 2 bảng kia thì không seed
        if (empty($restaurantIds) || empty($userIds)) {
            echo "⚠️ Chưa có dữ liệu trong bảng users hoặc restaurants — bỏ qua seed reviews.\n";
            return;
        }

        // Sinh 200 review ngẫu nhiên
        for ($i = 1; $i <= 200; $i++) {
            DB::table('reviews')->insert([
                'restaurant_id' => $faker->randomElement($restaurantIds),
                'user_id' => $faker->randomElement($userIds),
                'star_rating' => $faker->numberBetween(1, 5),
                'comment' => $faker->optional()->sentence(10),
                'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
            ]);
        }
    }
}
