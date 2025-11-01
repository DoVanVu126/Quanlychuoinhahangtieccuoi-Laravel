<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy danh sách user_id hiện có
        $userIds = DB::table('users')->pluck('user_id')->toArray();

        // Chọn ngẫu nhiên một số user để làm khách hàng
        $selectedUsers = collect($userIds)->random(rand(5, count($userIds)))->all();

        foreach ($selectedUsers as $userId) {
            DB::table('customers')->insert([
                'user_id' => $userId,
                'created_at' => now(),
            ]);
        }
    }
}
