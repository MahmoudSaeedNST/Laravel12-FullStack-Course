<?php

namespace App\Listeners;

use App\Enum\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // switch on the order status
        $order = $event->order;
        // order->status is a enum value
        switch ($order->status) {
            case OrderStatus::PAID:
                $order->user->notify(new OrderConfirmationNotification($order));
                break;

            case OrderStatus::SHIPPED:
                $order->user->notify(new OrderShippedNotification($order));
                break;

            case OrderStatus::DELIVERED:
                $order->user->notify(new OrderDeliveredNotification($order));
                break;

            case OrderStatus::CANCELLED:
                $order->user->notify(new OrderCancelledNotification($order));
                break;

            default:
                // No email needed for PENDING/PROCESSING
                break;
        }
    }
}
