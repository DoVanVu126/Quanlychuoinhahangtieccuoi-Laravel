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
        $faker = Faker::create();

        // Tạo 5 nhà hàng giả
        for ($i = 1; $i <= 5; $i++) {
            DB::table('restaurants')->insert([
                'name' => $faker->company(),
                'description' => $faker->sentence(),
                'street' => $faker->streetAddress(),
                'ward' => $faker->citySuffix(),
                'district' => $faker->city(),
                'city' => $faker->state(),
                'phone' => $faker->phoneNumber(),
                'email' => $faker->unique()->companyEmail(),
                'capacity' => rand(20, 100),
                'price_table' => $faker->randomFloat(2, 50, 500),
                'star_rating' => $faker->randomFloat(1, 0, 5),
                'review_count' => rand(0, 100),
                'image_url' => $faker->imageUrl(400, 300, 'restaurant', true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
