<?php

namespace App;

enum ServiceType: string
{
    case TireExchange = 'tire_exchange';
    case EmergencyTow = 'emergency_tow';

    public function label(): string
    {
        return match ($this) {
            self::TireExchange => 'Tire Exchange',
            self::EmergencyTow => 'Emergency Tow',
        };
    }
}
