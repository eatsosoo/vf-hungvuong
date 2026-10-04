<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Won = 'won';
    case Lost = 'lost';

    /** @return array<int, self> */
    public static function forType(string $type): array
    {
        return $type === 'test_drive'
            ? [self::New, self::Contacted, self::Confirmed, self::Completed, self::Cancelled]
            : [self::New, self::Contacted, self::Won, self::Lost, self::Cancelled];
    }
}
