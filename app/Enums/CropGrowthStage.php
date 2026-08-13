<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Crop growth stages used in Crop Management.
 */
enum CropGrowthStage: string
{
    case Seedling = 'seedling';
    case Vegetative = 'vegetative';
    case Flowering = 'flowering';
    case Maturity = 'maturity';

    public function label(): string
    {
        return match ($this) {
            self::Seedling => 'Seedling',
            self::Vegetative => 'Vegetative',
            self::Flowering => 'Flowering',
            self::Maturity => 'Maturity',
        };
    }

    /**
     * Progress weight for timeline visualization (0-100).
     */
    public function timelinePercent(): int
    {
        return match ($this) {
            self::Seedling => 25,
            self::Vegetative => 50,
            self::Flowering => 75,
            self::Maturity => 100,
        };
    }

    /**
     * @return list<self>
     */
    public static function timeline(): array
    {
        return [
            self::Seedling,
            self::Vegetative,
            self::Flowering,
            self::Maturity,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
