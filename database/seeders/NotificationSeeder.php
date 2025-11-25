<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('notifications')->insert([
            [
                'user_id' => 1,
                'title' => 'Đơn đặt tiệc mới',
                'message' => 'Bạn có một đơn đặt tiệc mới số #101',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 1,
                'title' => 'Khuyến mãi mới',
                'message' => 'Chương trình khuyến mãi mùa cưới đã được cập nhật',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 2,
                'title' => 'Đơn đặt tiệc mới',
                'message' => 'Bạn có một đơn đặt tiệc mới số #102',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
