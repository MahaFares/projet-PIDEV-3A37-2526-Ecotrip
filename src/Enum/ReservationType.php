<?php

namespace App\Enum;

enum ReservationType: string
{
    case HEBERGEMENT = 'HEBERGEMENT';
    case ACTIVITY = 'ACTIVITY';
    case TRANSPORT = 'TRANSPORT';

    public function label(): string
    {
        return match($this) {
            self::HEBERGEMENT => 'Hébergement',
            self::ACTIVITY => 'Activité',
            self::TRANSPORT => 'Transport',
        };
    }
}
