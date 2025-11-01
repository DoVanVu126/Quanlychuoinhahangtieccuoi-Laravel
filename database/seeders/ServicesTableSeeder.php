<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 10; $i++) {
            DB::table('services')->insert([
                'restaurant_id' => rand(1, 5),
                'name' => $faker->words(2, true),
                'description' => $faker->sentence(),
                'price' => $faker->randomFloat(2, 50, 200),
                'status' => $faker->randomElement(['available', 'unavailable', 'maintenance']),
                'image_url' => $faker->imageUrl(200, 200, 'food', true),
            ]);
        }
    }
}
