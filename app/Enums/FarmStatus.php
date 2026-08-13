<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Farm lifecycle status values.
 */
enum FarmStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
