<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Lấy danh sách booking_id từ bảng bookings
        $bookingIds = DB::table('bookings')->pluck('booking_id')->toArray();

        if (empty($bookingIds)) {
            $this->command->warn('⚠️ Không có booking nào trong database. Vui lòng chạy BookingsSeeder trước.');
            return;
        }

        $payments = [];

        foreach ($bookingIds as $bookingId) {
            // Tính tổng tiền ngẫu nhiên từ 10 triệu đến 200 triệu
            $totalAmount = $faker->randomFloat(2, 10000000, 200000000);
            
            // Random trạng thái thanh toán
            $paymentStatus = $faker->randomElement(['unpaid', 'partial', 'paid']);
            
            // Tính deposit và remaining dựa trên status
            $depositAmount = 0;
            $remainingAmount = $totalAmount;
            
            if ($paymentStatus === 'partial') {
                // Đã đặt cọc 30-70% tổng tiền
                $depositAmount = $totalAmount * $faker->randomFloat(2, 0.3, 0.7);
                $remainingAmount = $totalAmount - $depositAmount;
            } elseif ($paymentStatus === 'paid') {
                // Đã thanh toán đủ
                $depositAmount = $totalAmount;
                $remainingAmount = 0;
            }

            // Phương thức thanh toán
            $paymentMethod = $faker->randomElement(['cash', 'bank_transfer', 'credit_card', 'e-wallet']);
            
            // Mã giao dịch (nếu không phải tiền mặt)
            $transactionCode = null;
            if ($paymentMethod !== 'cash') {
                $transactionCode = strtoupper($faker->bothify('TXN####??####'));
            }

            // Ngày thanh toán
            $paymentDate = $faker->dateTimeBetween('-6 months', 'now');

            // Ghi chú
            $notes = null;
            if ($faker->boolean(30)) {
                $noteOptions = [
                    'Thanh toán qua ngân hàng MB Bank',
                    'Thanh toán qua VNPay',
                    'Thanh toán trực tiếp tại nhà hàng',
                    'Chuyển khoản qua Vietcombank',
                    'Thanh toán qua ví MoMo',
                    'Thanh toán qua ví ZaloPay',
                    'Đặt cọc trước 50%',
                    'Thanh toán đầy đủ ngay',
                ];
                $notes = $faker->randomElement($noteOptions);
            }

            $payments[] = [
                'booking_id' => $bookingId,
                'total_amount' => round($totalAmount, 2),
                'deposit_amount' => round($depositAmount, 2),
                'remaining_amount' => round($remainingAmount, 2),
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'transaction_code' => $transactionCode,
                'payment_date' => $paymentDate,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert dữ liệu
        DB::table('payments')->insert($payments);

        $this->command->info('✅ Đã tạo ' . count($payments) . ' bản ghi payments thành công!');
    }
}
