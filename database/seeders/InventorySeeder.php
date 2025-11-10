<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Lấy danh sách restaurant_id từ bảng restaurants
        $restaurantIds = DB::table('restaurants')->pluck('restaurant_id')->toArray();

        // Các loại nguyên liệu phổ biến trong nhà hàng tiệc cưới
        $items = [
            ['name' => 'Gạo tẻ', 'unit' => 'kg', 'perishable' => false],
            ['name' => 'Gạo nếp', 'unit' => 'kg', 'perishable' => false],
            ['name' => 'Thịt bò', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Thịt heo', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Gà ta', 'unit' => 'con', 'perishable' => true],
            ['name' => 'Tôm sú', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Cá hồi', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Mực', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Rau xà lách', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Cà chua', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Hành tây', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Khoai tây', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Dầu ăn', 'unit' => 'lít', 'perishable' => true],
            ['name' => 'Nước mắm', 'unit' => 'lít', 'perishable' => true],
            ['name' => 'Đường', 'unit' => 'kg', 'perishable' => false],
            ['name' => 'Muối', 'unit' => 'kg', 'perishable' => false],
            ['name' => 'Bột mì', 'unit' => 'kg', 'perishable' => true],
            ['name' => 'Trứng gà', 'unit' => 'vỉ', 'perishable' => true],
            ['name' => 'Sữa tươi', 'unit' => 'lít', 'perishable' => true],
            ['name' => 'Bia Heineken', 'unit' => 'thùng', 'perishable' => true],
            ['name' => 'Bia Saigon', 'unit' => 'thùng', 'perishable' => true],
            ['name' => 'Rượu vang đỏ', 'unit' => 'chai', 'perishable' => true],
            ['name' => 'Coca Cola', 'unit' => 'thùng', 'perishable' => true],
            ['name' => 'Pepsi', 'unit' => 'thùng', 'perishable' => true],
            ['name' => 'Nước suối', 'unit' => 'thùng', 'perishable' => false],
            ['name' => 'Khăn giấy', 'unit' => 'gói', 'perishable' => false],
            ['name' => 'Đĩa sứ', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Chén sứ', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Ly thủy tinh', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Thìa inox', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Dĩa inox', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Khăn trải bàn', 'unit' => 'cái', 'perishable' => false],
            ['name' => 'Nến trang trí', 'unit' => 'hộp', 'perishable' => false],
            ['name' => 'Hoa tươi', 'unit' => 'bó', 'perishable' => true],
            ['name' => 'Bánh mì', 'unit' => 'cái', 'perishable' => true],
        ];

        $inventoryData = [];

        // Chỉ lấy 1 nhà hàng đầu tiên để có khoảng 35 bản ghi (tầm 2 trang)
        $selectedRestaurant = array_slice($restaurantIds, 0, 1);

        // Tạo dữ liệu cho mỗi nhà hàng
        foreach ($selectedRestaurant as $restaurantId) {
            // Mỗi nhà hàng sẽ có TẤT CẢ các loại nguyên liệu (không trùng lặp)
            foreach ($items as $item) {
                // Tính hạn sử dụng cho sản phẩm dễ hỏng
                $expiryDate = null;
                $status = 'available';
                
                if ($item['perishable']) {
                    $randomDays = $faker->numberBetween(-10, 60); // Từ 10 ngày trước đến 60 ngày sau
                    $expiryDate = now()->addDays($randomDays)->format('Y-m-d');
                    
                    // Xác định status
                    if ($randomDays < 0) {
                        $status = 'expired'; // Đã hết hạn
                    } elseif ($randomDays <= 7) {
                        $status = 'near_expiry'; // Sắp hết hạn (còn 7 ngày)
                    } else {
                        $status = 'available'; // Còn hạn tốt
                    }
                }
                
                // Tạo số lượng thực tế hơn
                // 30% sản phẩm có số lượng thấp (cần nhập hàng)
                // Mức đặt lại là số tròn đẹp: 10, 20, 30, 50, 100
                $reorderLevels = [10, 20, 30, 50, 100];
                $reorderLevel = $faker->randomElement($reorderLevels);
                $needsReorder = $faker->boolean(30); // 30% cần nhập hàng
                
                if ($needsReorder) {
                    // Số lượng thấp hơn mức đặt lại (cần nhập)
                    $quantity = $faker->numberBetween(1, $reorderLevel - 1);
                } else {
                    // Số lượng an toàn
                    $quantity = $faker->numberBetween($reorderLevel + 10, 500);
                }
                
                $inventoryData[] = [
                    'restaurant_id' => $restaurantId,
                    'item_name' => $item['name'],
                    'unit' => $item['unit'],
                    'quantity' => $quantity,
                    'reorder_level' => $reorderLevel,
                    'expiry_date' => $expiryDate,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert dữ liệu theo batch để tăng hiệu suất
        foreach (array_chunk($inventoryData, 100) as $chunk) {
            DB::table('inventory')->insert($chunk);
        }

        $this->command->info('✅ Đã tạo ' . count($inventoryData) . ' bản ghi inventory thành công!');
    }
}
