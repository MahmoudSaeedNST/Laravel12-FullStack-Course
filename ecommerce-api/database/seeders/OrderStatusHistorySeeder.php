<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderStatusHistory;

class OrderStatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            OrderStatusHistory::factory()->create([
                'order_id' => $order->id,
                'from_status' => $order->status, // Assuming the initial status is the current status
                'to_status' => $order->status, // You can modify this to simulate status changes
                'user_id' => $order->user_id, // Assuming the user who created the order is the one changing the status
                'note' => 'Initial status recorded', // You can customize this note
            ]);
        }
    }
}