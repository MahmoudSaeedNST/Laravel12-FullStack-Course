<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(), // Link to an existing order
            'from_status' => $this->faker->randomElement(['PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED']),
            'to_status' => $this->faker->randomElement(['PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED']),
            'user_id' => $this->faker->randomNumber(), // Assuming user IDs are numeric
            'note' => $this->faker->sentence(),
        ];
    }
}