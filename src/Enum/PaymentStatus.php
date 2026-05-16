<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::COMPLETED => 'Complété',
            self::FAILED => 'Échoué',
        };
    }

    public function badge(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
}
