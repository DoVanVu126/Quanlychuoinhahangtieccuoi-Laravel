<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [];

        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'username' => 'user' . $i,
                'password_hash' => Hash::make('password' . $i),
                'email' => 'user' . $i . '@example.com',
                'image_url' => null,
                'role' => $i === 1 ? 'admin' : ($i <= 3 ? 'staff' : 'customer'),
                'created_at' => now(),
            ];
        }

        DB::table('users')->insert($users);
    }
}
