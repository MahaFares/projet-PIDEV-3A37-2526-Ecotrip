<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case CARD = 'CARD';
    case PAYPAL = 'PAYPAL';
    case CASH = 'CASH';

    public function label(): string
    {
        return match($this) {
            self::CARD => 'Carte bancaire',
            self::PAYPAL => 'PayPal',
            self::CASH => 'Espèces',
        };
    }
}
