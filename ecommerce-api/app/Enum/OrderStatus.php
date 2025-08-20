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

    // get allowed transitions
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    // validation can transition to
    
    public function canTransitionTo(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAllowedTransitions());
    }

    // Get the label for the status
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }
}
