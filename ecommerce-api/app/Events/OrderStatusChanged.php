<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Order $order,
        public ?string $previousStatus = null,
        public ?string $changedBy = null,
    )
    {
        $this->order->load(['user', 'items.product']);

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Each customer listens to their own orders
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),

            // admin listens to all orders
            new Channel('admin.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
         return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'current_status' => $this->order->status->value,
            'current_status_label' => $this->order->status->getLabel(),
            'previous_status' => $this->previousStatus,
            'changed_by' => $this->changedBy,
            'total' => $this->order->total,
            'updated_at' => $this->order->updated_at->toISOString(),
            'user' => [
                'id' => $this->order->user->id,
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
            ],
            'items_count' => $this->order->items->count(),
            'items_summary' => $this->order->items->take(3)->map(fn($item) => [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
            ])->toArray(),
        ];
    }
}
