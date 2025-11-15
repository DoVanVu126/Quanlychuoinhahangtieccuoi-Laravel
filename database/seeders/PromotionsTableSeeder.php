<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PromotionsTableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('promotions')->insert([
                'restaurant_id' => rand(1, 5),
                'promotion_code' => 'PROMO' . $i,
                'title' => 'Khuyến mãi ' . $i,
                'description' => 'Mô tả khuyến mãi ' . $i,
                'discount_type' => rand(0,1) ? 'percent' : 'amount',
                'discount_value' => rand(5,50),
                'start_date' => Carbon::today()->addDays(rand(-10, 10)),
                'end_date' => Carbon::today()->addDays(rand(11, 30)),
                'status' => 'active',
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
