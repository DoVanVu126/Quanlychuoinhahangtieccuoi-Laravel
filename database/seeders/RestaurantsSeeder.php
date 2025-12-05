<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RestaurantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ để tránh trùng lặp khi chạy lại
        DB::table('restaurants')->truncate();

        $restaurants = [
            [
                'name' => 'White Palace Convention Center',
                'description' => 'Trung tâm hội nghị tiệc cưới sang trọng với không gian hiện đại, chuẩn 5 sao.',
                'ward' => 'Phường 15',
                'city' => 'Quận Phú Nhuận, TP.HCM',
                'phone' => '0901234567',
                'email' => 'info@whitepalace.com.vn',
                'capacity' => 1200,
                'price_table' => 5500000.00, // Giá cao để test tổng tiền lớn
                'star_rating' => 4.8,
                'review_count' => 150,
                'image_url' => 'https://placehold.co/600x400?text=White+Palace', // Ảnh giả lập
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Nhà Hàng Tiệc Cưới Đông Phương',
                'description' => 'Chuỗi nhà hàng tiệc cưới bình dân, phù hợp với đại đa số khách hàng.',
                'ward' => 'Phường 12',
                'city' => 'Quận Tân Bình, TP.HCM',
                'phone' => '0909888777',
                'email' => 'contact@dongphuong.vn',
                'capacity' => 800,
                'price_table' => 3200000.00, // Giá trung bình
                'star_rating' => 4.2,
                'review_count' => 320,
                'image_url' => 'https://placehold.co/600x400?text=Dong+Phuong',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Riverside Palace',
                'description' => 'Nhà hàng ven sông với kiến trúc Châu Âu cổ điển, lãng mạn.',
                'ward' => 'Phường 1',
                'city' => 'Quận 4, TP.HCM',
                'phone' => '0912341234',
                'email' => 'booking@riverside.com',
                'capacity' => 600,
                'price_table' => 4800000.00,
                'star_rating' => 4.5,
                'review_count' => 90,
                'image_url' => 'https://placehold.co/600x400?text=Riverside',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Vườn Ẩm Thực Bên Sông',
                'description' => 'Không gian sân vườn thoáng mát, thích hợp tiệc cưới ngoài trời.',
                'ward' => 'Phường Hiệp Bình Chánh',
                'city' => 'TP. Thủ Đức',
                'phone' => '0987654321',
                'email' => 'vuonamthuc@gmail.com',
                'capacity' => 400,
                'price_table' => 2500000.00, // Giá thấp để test
                'star_rating' => 3.8,
                'review_count' => 45,
                'image_url' => 'https://placehold.co/600x400?text=Vuon+Am+Thuc',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Gala Center',
                'description' => 'Trung tâm tiệc cưới công nghệ cao với hệ thống ánh sáng hiện đại.',
                'ward' => 'Phường 4',
                'city' => 'Quận Tân Bình, TP.HCM',
                'phone' => '02838111111',
                'email' => 'sales@galacenter.vn',
                'capacity' => 1000,
                'price_table' => 4200000.00,
                'star_rating' => 4.4,
                'review_count' => 200,
                'image_url' => 'https://placehold.co/600x400?text=Gala+Center',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('restaurants')->insert($restaurants);
    }
}