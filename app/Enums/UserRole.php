<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Platform user roles for CyraAgroLink.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Farmer = 'farmer';
    case Investor = 'investor';
    case Buyer = 'buyer';
    case Supplier = 'supplier';
    case Agent = 'agent';

    /**
     * Human-readable role label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Super Admin',
            self::Farmer => 'Farmer',
            self::Investor => 'Investor',
            self::Buyer => 'Buyer',
            self::Supplier => 'Supplier',
            self::Agent => 'Agent',
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
