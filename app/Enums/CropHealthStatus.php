<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Crop health status indicators.
 */
enum CropHealthStatus: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Poor => 'Poor',
            self::Critical => 'Critical',
        };
    }

    public function isPositive(): bool
    {
        return $this === self::Good || $this === self::Fair;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
