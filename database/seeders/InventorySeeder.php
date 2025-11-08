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
            ['name' => 'Gạo tẻ', 'unit' => 'kg'],
            ['name' => 'Gạo nếp', 'unit' => 'kg'],
            ['name' => 'Thịt bò', 'unit' => 'kg'],
            ['name' => 'Thịt heo', 'unit' => 'kg'],
            ['name' => 'Gà ta', 'unit' => 'con'],
            ['name' => 'Tôm sú', 'unit' => 'kg'],
            ['name' => 'Cá hồi', 'unit' => 'kg'],
            ['name' => 'Mực', 'unit' => 'kg'],
            ['name' => 'Rau xà lách', 'unit' => 'kg'],
            ['name' => 'Cà chua', 'unit' => 'kg'],
            ['name' => 'Hành tây', 'unit' => 'kg'],
            ['name' => 'Khoai tây', 'unit' => 'kg'],
            ['name' => 'Dầu ăn', 'unit' => 'lít'],
            ['name' => 'Nước mắm', 'unit' => 'lít'],
            ['name' => 'Đường', 'unit' => 'kg'],
            ['name' => 'Muối', 'unit' => 'kg'],
            ['name' => 'Bột mì', 'unit' => 'kg'],
            ['name' => 'Trứng gà', 'unit' => 'vỉ'],
            ['name' => 'Sữa tươi', 'unit' => 'lít'],
            ['name' => 'Bia Heineken', 'unit' => 'thùng'],
            ['name' => 'Bia Saigon', 'unit' => 'thùng'],
            ['name' => 'Rượu vang đỏ', 'unit' => 'chai'],
            ['name' => 'Coca Cola', 'unit' => 'thùng'],
            ['name' => 'Pepsi', 'unit' => 'thùng'],
            ['name' => 'Nước suối', 'unit' => 'thùng'],
            ['name' => 'Khăn giấy', 'unit' => 'gói'],
            ['name' => 'Đĩa sứ', 'unit' => 'cái'],
            ['name' => 'Chén sứ', 'unit' => 'cái'],
            ['name' => 'Ly thủy tinh', 'unit' => 'cái'],
            ['name' => 'Thìa inox', 'unit' => 'cái'],
            ['name' => 'Dĩa inox', 'unit' => 'cái'],
            ['name' => 'Khăn trải bàn', 'unit' => 'cái'],
            ['name' => 'Nến trang trí', 'unit' => 'hộp'],
            ['name' => 'Hoa tươi', 'unit' => 'bó'],
            ['name' => 'Bánh mì', 'unit' => 'cái'],
        ];

        $inventoryData = [];

        // Tạo dữ liệu cho mỗi nhà hàng
        foreach ($restaurantIds as $restaurantId) {
            // Mỗi nhà hàng sẽ có ngẫu nhiên 15-25 loại nguyên liệu
            $selectedItems = $faker->randomElements($items, $faker->numberBetween(15, 25));
            
            foreach ($selectedItems as $item) {
                $inventoryData[] = [
                    'restaurant_id' => $restaurantId,
                    'item_name' => $item['name'],
                    'unit' => $item['unit'],
                    'quantity' => $faker->randomFloat(2, 10, 500),
                    'reorder_level' => $faker->randomFloat(2, 5, 50),
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
