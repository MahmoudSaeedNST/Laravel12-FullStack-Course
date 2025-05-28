<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['pending', 'paid', 'shipped', 'cancelled']);
        return [
            'user_id' => User::factory(),
            'status' => $status,
            'shipping_name' => $this->faker->name(),
            'shipping_address' => $this->faker->address(),
            'shipping_city' => $this->faker->city(),
            'shipping_state' => $this->faker->state(),
            'shipping_zipcode' => $this->faker->postcode(),
            'shipping_country' => $this->faker->country(),
            'shipping_phone' => $this->faker->phoneNumber(),
            'subtotal' => $this->faker->randomFloat(2, 20, 2000),
            'tax' => $this->faker->randomFloat(2, 0, 200),
            'shipping_cost' => $this->faker->randomFloat(2, 0, 100),
            'total' => $this->faker->randomFloat(2, 20, 2200),
            'payment_method' => $this->faker->randomElement(['cod', 'card', 'paypal']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'order_number' => Order::generateOrderNumber(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
