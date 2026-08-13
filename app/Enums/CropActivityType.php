<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Logged crop-care activity categories.
 */
enum CropActivityType: string
{
    case Activity = 'activity';
    case Irrigation = 'irrigation';
    case Fertilizer = 'fertilizer';
    case Health = 'health';
    case Harvest = 'harvest';

    public function label(): string
    {
        return match ($this) {
            self::Activity => 'Activity',
            self::Irrigation => 'Irrigation',
            self::Fertilizer => 'Fertilizer',
            self::Health => 'Health',
            self::Harvest => 'Harvest',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
