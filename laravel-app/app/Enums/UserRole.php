<?php

namespace App\Enums;

enum UserRole: string
{
    case Resident = 'resident';
    case Personnel = 'personnel';
    case Official = 'official';

    public function label(): string
    {
        return match ($this) {
            self::Resident => 'Resident',
            self::Personnel => 'Response Personnel',
            self::Official => 'Barangay Official',
        };
    }
}
