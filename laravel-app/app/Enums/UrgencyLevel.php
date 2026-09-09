<?php

namespace App\Enums;

enum UrgencyLevel: string
{
    case Critical = 'critical';
    case High = 'high';
    case Average = 'average';
    case Low = 'low';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::High => 'orange',
            self::Average => 'yellow',
            self::Low => 'green',
        };
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Average => 2,
            self::Low => 1,
        };
    }
}
