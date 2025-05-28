<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';    // Payment is initiated but not completed
    case COMPLETED = 'completed'; // Payment has been successfully completed
    case FAILED = 'failed';       // Payment failed
    case REFUNDED = 'refunded';   // Payment has been refunded

    // values
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
