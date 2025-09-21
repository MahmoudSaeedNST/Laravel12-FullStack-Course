# Module 5: Order Management and Realtime Updates - Part 2: Real-time Updates with Laravel Reverb

## Introduction

Real-time updates make e-commerce feel alive! Instead of customers refreshing the page to check order status, we can push updates instantly when an admin changes an order from *paid* to *shipped*.

Laravel **Reverb** is Laravel's official WebSocket server. It provides a direct connection between your backend and clients so that when something changes, all connected clients are notified instantly. In this part, we’ll focus only on the **API side**: broadcasting events and securing channels.

---

## Step 1: Install and Configure Laravel Reverb

```bash
php artisan install:broadcasting
```

This will:

* Install Laravel Reverb
* Set up broadcasting configuration
* Add `.env` variables
* Install Echo dependencies (for when we connect the frontend later)

Update your `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## Step 2: Create the Order Status Changed Event

We’ll broadcast whenever an order status changes.

```bash
php artisan make:event OrderStatusChanged
```

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $previousStatus = null,
        public ?string $changedBy = null
    ) {
        $this->order->load(['user', 'items.product']);
    }

    public function broadcastOn(): array
    {
        return [
            // Each customer listens to their own orders
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),

            // Admins can listen globally
            new PrivateChannel('admin.orders'),
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
```

---

## Step 3: Fire the Event from the Order Model

Update the `transitionTo()` method to dispatch the event:

```php
use App\Events\OrderStatusChanged;

public function transitionTo(OrderStatus $newStatus, $changedBy = null, ?string $notes = null): bool
{
    if ($this->status === $newStatus) {
        return true;
    }

    if (!$this->status->canTransitionTo($newStatus)) {
        throw new \Exception("Invalid transition from {$this->status->value} to {$newStatus->value}");
    }

    $oldStatus = $this->status;
    $this->status = $newStatus;
    $this->save();

    $this->statusHistory()->create([
        'from_status' => $oldStatus,
        'to_status' => $newStatus,
        'changed_by' => $changedBy?->id ?? Auth::id(),
        'notes' => $notes,
    ]);

    // Broadcast real-time event
    OrderStatusChanged::dispatch(
        $this,
        $oldStatus->value,
        $changedBy?->name ?? Auth::user()?->name
    );

    return true;
}
```

---

## Step 4: Channel Authorization

Secure the channels in `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('user.{userId}.orders', function (User $user, int $userId) {
    return $user->id === $userId;
});

Broadcast::channel('admin.orders', function (User $user) {
    return $user->hasRole('admin');
});
```

---

## Step 5: Running the System

To run everything:

```bash
php artisan serve        # Laravel app
php artisan reverb:start # WebSocket server
```

---

## What We Built

* **Reverb integration** for real-time communication
* **OrderStatusChanged event** that fires on every status change
* **Order model broadcasting** so events are automatic
* **Secure channels** so customers only get their own updates and admins get global updates

---

## Real-World Challenge

**Exercise**: Add a second event `OrderCreated` that broadcasts whenever a new order is placed.

* Broadcast to the customer’s private channel (`user.{id}.orders`).
* Broadcast to the admin channel for monitoring.

This ensures both customer and admin see new orders in real-time.

---
