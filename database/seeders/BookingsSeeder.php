<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $customers = DB::table('customers')->get();
        $restaurants = DB::table('restaurants')->pluck('restaurant_id');
        $halls = DB::table('halls')->pluck('hall_id');

        foreach ($customers as $customer) {
            // Mỗi khách hàng sẽ có 1–3 booking
            $bookingCount = rand(1, 3);

            for ($i = 0; $i < $bookingCount; $i++) {
                $eventDate = $faker->dateTimeBetween('now', '+1 month');
                $returnDate = (clone $eventDate)->modify('+' . rand(0, 10) . ' days');

                DB::table('bookings')->insert([
                    'customer_id' => $customer->customer_id,
                    'created_by_user_id' => $customer->user_id,
                    'restaurant_id' => $faker->randomElement($restaurants),
                    'hall_id' => $faker->randomElement($halls),
                    'event_type' => $faker->randomElement(['Đám cưới', 'Sinh nhật', 'Hội nghị']),
                    'event_time' => $faker->randomElement(['10:00:00', '17:00:00']),
                    'event_date' => $eventDate->format('Y-m-d'),
                    'return_date' => $faker->boolean(70) ? $returnDate->format('Y-m-d') : null, // 70% có ngày trả
                    'number_of_tables' => rand(10, 50),
                    'status' => $faker->randomElement(['pending', 'confirmed', 'completed']),
                    'notes' => $faker->sentence(6),
                    'created_at' => now(),
                ]);
            }
        }
    }
}
