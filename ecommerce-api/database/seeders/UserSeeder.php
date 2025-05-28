<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'type' => 'admin',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Customer User',
                'email' => 'customer@example.com',
                'type' => 'customer',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Delivery User',
                'email' => 'delivery@example.com',
                'type' => 'delivery',
                'password' => bcrypt('password'),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            $user->assignRole($userData['type']);
        }
    }
}
