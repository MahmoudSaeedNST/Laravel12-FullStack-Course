<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        Cart::factory(20)->create();
    }
}
