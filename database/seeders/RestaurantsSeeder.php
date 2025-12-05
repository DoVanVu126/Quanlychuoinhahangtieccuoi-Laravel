<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RestaurantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN'); // Dữ liệu tiếng Việt

        for ($i = 1; $i <= 100; $i++) {
            DB::table('restaurants')->insert([
                'name' => $faker->company,
                'description' => $faker->paragraph(3),
                'ward' => 'Phường ' . $faker->numberBetween(1, 15),
                'city' => 'TP. ' . $faker->city,
                'phone' => '0' . $faker->numberBetween(900000000, 999999999),
                'email' => $faker->unique()->safeEmail,
                'capacity' => $faker->numberBetween(50, 500),
                'price_table' => $faker->randomFloat(2, 1000000, 20000000),
                'star_rating' => $faker->randomFloat(1, 0, 5),
                'review_count' => $faker->numberBetween(0, 500),
                'image_url' => $faker->imageUrl(640, 480, 'restaurant', true, 'Nhà hàng'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
