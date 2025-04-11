<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // call the User seeder
        User::factory(10)->create();
         // call the posts seeder to seed the posts table
         $this->call(PostSeeder::class);

        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

       

        // call the ProductSeeder to seed the products table
        
    }
}
