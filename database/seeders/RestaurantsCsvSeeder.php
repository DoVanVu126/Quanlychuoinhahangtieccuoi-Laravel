<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RestaurantsCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('restaurants_100.csv');

        if (!file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");
            $this->command->info('Please add the file `database/restaurants_100.csv` and run this seeder again.');
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines || count($lines) < 2) {
            $this->command->error('CSV is empty or missing data rows.');
            return;
        }

        $header = str_getcsv(array_shift($lines));
        $rows = [];

        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) !== count($header)) {
                // skip malformed lines
                continue;
            }

            $assoc = array_combine($header, $data);

            $createdAt = $assoc['created_at'] ?? null;
            $updatedAt = $assoc['updated_at'] ?? null;

            // Normalize dates
            try {
                $createdAt = $createdAt ? Carbon::parse($createdAt)->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $createdAt = now()->format('Y-m-d H:i:s');
            }
            try {
                $updatedAt = $updatedAt ? Carbon::parse($updatedAt)->format('Y-m-d H:i:s') : $createdAt;
            } catch (\Throwable $e) {
                $updatedAt = $createdAt;
            }

            $rows[] = [
                'restaurant_id' => isset($assoc['id']) && is_numeric($assoc['id']) ? (int)$assoc['id'] : null,
                'name' => $assoc['name'] ?? null,
                'description' => $assoc['description'] ?: null,
                'ward' => $assoc['ward'] ?: null,
                'city' => $assoc['city'] ?: null,
                'phone' => $assoc['phone'] ?: null,
                'email' => $assoc['email'] ?: null,
                'capacity' => isset($assoc['capacity']) && is_numeric($assoc['capacity']) ? (int)$assoc['capacity'] : null,
                'price_table' => isset($assoc['price_table']) && is_numeric($assoc['price_table']) ? (float)$assoc['price_table'] : null,
                'star_rating' => isset($assoc['star_rating']) && is_numeric($assoc['star_rating']) ? (float)$assoc['star_rating'] : 0,
                'review_count' => isset($assoc['review_count']) && is_numeric($assoc['review_count']) ? (int)$assoc['review_count'] : 0,
                'image_url' => $assoc['image_url'] ?: null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        if (empty($rows)) {
            $this->command->error('No valid rows to import.');
            return;
        }

        // Use upsert to insert or update by restaurant_id
        DB::table('restaurants')->upsert($rows, ['restaurant_id'], ['name','description','ward','city','phone','email','capacity','price_table','star_rating','review_count','image_url','updated_at']);

        $this->command->info('Imported ' . count($rows) . ' restaurants (upserted).');
    }
}
