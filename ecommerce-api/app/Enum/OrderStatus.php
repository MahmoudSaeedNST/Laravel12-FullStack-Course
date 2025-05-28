<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';    // Initial state when order is created
    case PAID = 'paid';          // Payment received
    case PROCESSING = 'processing'; // Preparing the order
    case SHIPPED = 'shipped';    // Order sent to delivery
    case DELIVERED = 'delivered'; // Order received by customer
    case CANCELLED = 'cancelled'; // Order cancelled

    // values
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
