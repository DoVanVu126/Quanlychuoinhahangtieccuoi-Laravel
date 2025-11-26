<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuggestionPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get a restaurant to attach packages to
        $restaurant = DB::table('restaurants')->first();
        if (!$restaurant) {
            // nothing to seed against
            return;
        }

        $hall = DB::table('halls')->where('restaurant_id', $restaurant->restaurant_id)->first();

        // pick some foods/services for the restaurant if available
        $foodIds = DB::table('foods')->where('restaurant_id', $restaurant->restaurant_id)->limit(10)->pluck('food_id')->toArray();
        $serviceIds = DB::table('services')->where('restaurant_id', $restaurant->restaurant_id)->limit(10)->pluck('service_id')->toArray();

        $packages = [
            [
                'restaurant_id' => $restaurant->restaurant_id,
                'name' => 'Gói Cơ Bản',
                'event_type' => 'Đám cưới',
                'hall_id' => $hall? $hall->hall_id : null,
                'number_of_tables' => 10,
                'description' => 'Gói cơ bản bao gồm sảnh, set menu phổ thông và 1 dịch vụ',
                'image_url' => null,
                'created_at' => now(),
            ],
            [
                'restaurant_id' => $restaurant->restaurant_id,
                'name' => 'Gói VIP',
                'event_type' => 'Đám cưới',
                'hall_id' => $hall? $hall->hall_id : null,
                'number_of_tables' => 20,
                'description' => 'Gói VIP với món cao cấp và nhiều dịch vụ',
                'image_url' => null,
                'created_at' => now(),
            ],
        ];

        foreach ($packages as $pkg) {
            $packageId = DB::table('suggestion_packages')->insertGetId($pkg);

            // attach up to 3 foods
            $attachFoods = array_slice($foodIds, 0, 3);
            foreach ($attachFoods as $fid) {
                DB::table('suggestion_foods')->insert([
                    'package_id' => $packageId,
                    'food_id' => $fid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // attach up to 2 services
            $attachServices = array_slice($serviceIds, 0, 2);
            foreach ($attachServices as $sid) {
                DB::table('suggestion_services')->insert([
                    'package_id' => $packageId,
                    'service_id' => $sid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
