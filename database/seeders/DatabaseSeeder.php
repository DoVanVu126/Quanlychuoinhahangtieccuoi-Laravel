<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,          // 1
            RestaurantsSeeder::class,         // 2
            HallSeeder::class,                // 3
            CustomersSeeder::class,           // 4
            BookingsSeeder::class,            // 5
            FoodTypeSeeder::class,            // 6
            FoodSeeder::class,                // 7
            ServicesTableSeeder::class,       // 8
            PromotionsTableSeeder::class,     // 9
            ReviewsSeeder::class,             // 10
            InventorySeeder::class,           // 11
            PaymentsSeeder::class,            // 12
        ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(HallSeeder::class);
        $this->call([
    NotificationSeeder::class,
]);

    }
}
