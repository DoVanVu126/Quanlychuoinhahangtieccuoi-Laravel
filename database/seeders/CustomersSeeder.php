<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy ID của một Sảnh và Nhà hàng bất kỳ để đặt tiệc
        // (Giả sử bạn đã chạy RestaurantSeeder trước đó)
        $hall = DB::table('halls')->first();
        
        if (!$hall) {
            $this->command->info('⚠️ Chưa có Sảnh nào. Vui lòng chạy RestaurantSeeder trước!');
            return;
        }

        $restaurantId = $hall->restaurant_id;
        $hallId = $hall->hall_id;

        // 2. Danh sách khách hàng mẫu muốn thêm
        $customersData = [
            ['Phạm Văn D', 'vand@gmail.com', '0911222333'],
            ['Nguyen Thi E', 'thie@gmail.com', '0944555666'],
            ['Tran Van F', 'vanf@gmail.com', '0977888999'],
        ];

        foreach ($customersData as $index => $data) {
            // A. Tạo User (Tài khoản đăng nhập)
            $userId = DB::table('users')->insertGetId([
                'username' => 'khachhang' . ($index + 1), // khachhang1, khachhang2...
                'email' => $data[1],
                'password_hash' => Hash::make('12345678'),
                'role' => 'customer',
                'full_name' => $data[0],
                'phone' => $data[2],
                'address' => 'Hà Nội',
                'image_url' => null,
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            // B. Tạo Customer Profile (Liên kết với User)
            $customerId = DB::table('customers')->insertGetId([
                'user_id' => $userId,
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            // C. Tạo Booking (Đặt tiệc)
            $bookingId = DB::table('bookings')->insertGetId([
                'customer_id' => $customerId,
                'created_by_user_id' => $userId,
                'restaurant_id' => $restaurantId,
                'hall_id' => $hallId,
                'event_type' => 'Tiệc sinh nhật',
                'event_date' => Carbon::now()->addDays(rand(10, 60)), // Tổ chức trong tương lai
                'event_time' => '11:30:00',
                'number_of_tables' => rand(10, 50),
                'price' => rand(10, 50) * 3000000, // Giá ví dụ
                'status' => 'confirmed',
                'notes' => 'Ghi chú mẫu từ seeder',
                'created_at' => now(),
            ]);

            // D. Tạo Payment (Thanh toán cọc)
            DB::table('payments')->insert([
                'booking_id' => $bookingId,
                'total_amount' => 5000000, // Cọc 5 triệu
                'deposit_amount' => 5000000,
                'remaining_amount' => (rand(10, 50) * 3000000) - 5000000,
                'payment_status' => 'partial',
                'payment_method' => 'bank_transfer',
                'transaction_code' => 'TRX-' . rand(100000, 999999),
                'payment_date' => now(),
                'created_at' => now(),
            ]);
        }
        
        $this->command->info('✅ Đã tạo xong dữ liệu mẫu cho Customers!');
    }
}