<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $price = $this->faker->randomFloat(2, 10, 2000);
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->words(2, true),
            'product_sku' => strtoupper($this->faker->bothify('??#####')),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $quantity * $price,
        ];
    }
}
