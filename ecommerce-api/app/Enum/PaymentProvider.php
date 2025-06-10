<?php

namespace App\Enum;

enum PaymentProvider: string
{
    //
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

    // values
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
