<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Submitted = 'submitted';
    case NeedsReview = 'needs_review';
    case Validated = 'validated';
    case Assigned = 'assigned';
    case EnRoute = 'en_route';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::NeedsReview => 'Needs Review',
            self::Validated => 'Validated',
            self::Assigned => 'Assigned',
            self::EnRoute => 'En Route',
            self::Resolved => 'Resolved',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Submitted => 'gray',
            self::NeedsReview => 'yellow',
            self::Validated => 'blue',
            self::Assigned => 'indigo',
            self::EnRoute => 'purple',
            self::Resolved => 'green',
            self::Cancelled => 'red',
        };
    }
}
