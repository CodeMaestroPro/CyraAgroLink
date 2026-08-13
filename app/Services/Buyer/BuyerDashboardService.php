<?php

declare(strict_types=1);

namespace App\Services\Buyer;

use App\Models\User;

/**
 * Assembles buyer dashboard presentation data.
 *
 * Demo procurement metrics mirror the Buyer Dashboard UI until
 * live orders and supplier relationships are wired through repositories.
 */
class BuyerDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        return [
            'greeting_name' => $this->firstName($user->name),
            'stats' => $this->stats(),
            'recent_orders' => $this->recentOrders(),
            'spend' => $this->spendAnalytics(),
            'favorite_suppliers' => $this->favoriteSuppliers(),
            'notifications_count' => 3,
        ];
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Buyer';
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function stats(): array
    {
        return [
            [
                'label' => 'Total Orders',
                'value' => '24',
                'meta' => '↑ 12% from last month',
                'meta_tone' => 'green',
            ],
            [
                'label' => 'Total Spent',
                'value' => '₦18,560,000',
                'meta' => '↑ 8.4% from last month',
                'meta_tone' => 'green',
            ],
            [
                'label' => 'Active Suppliers',
                'value' => '18',
                'meta' => null,
                'meta_tone' => 'green',
            ],
            [
                'label' => 'Pending Deliveries',
                'value' => '7',
                'meta' => null,
                'meta_tone' => 'amber',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recentOrders(): array
    {
        return [
            [
                'product' => 'Maize (White)',
                'supplier' => 'Green Valley Farms',
                'quantity' => '2,000 Tons',
                'status' => 'In Transit',
                'status_tone' => 'transit',
                'image' => asset('images/marketplace/maize.jpg'),
            ],
            [
                'product' => 'Rice (Parboiled)',
                'supplier' => 'Golden Harvest Ltd',
                'quantity' => '1,500 Tons',
                'status' => 'Delivered',
                'status_tone' => 'delivered',
                'image' => asset('images/marketplace/rice.jpg'),
            ],
            [
                'product' => 'Cashew Nuts',
                'supplier' => "Nature's Pride",
                'quantity' => '800 Tons',
                'status' => 'Processing',
                'status_tone' => 'processing',
                'image' => asset('images/marketplace/cassava.jpg'),
            ],
            [
                'product' => 'Cocoa Beans',
                'supplier' => 'Sunrise Farms',
                'quantity' => '600 Tons',
                'status' => 'Pending',
                'status_tone' => 'pending',
                'image' => asset('images/marketplace/cocoa.jpg'),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function spendAnalytics(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'values' => [980000, 1250000, 1680000, 2100000, 2550000, 3120000],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function favoriteSuppliers(): array
    {
        return [
            [
                'name' => 'Green Valley Farms',
                'badge' => 'Verified Supplier',
                'rating' => '4.8',
                'image' => asset('images/marketplace/supplier-1.jpg'),
            ],
            [
                'name' => 'Golden Harvest Ltd',
                'badge' => 'Verified Supplier',
                'rating' => '4.7',
                'image' => asset('images/marketplace/supplier-3.jpg'),
            ],
            [
                'name' => "Nature's Pride",
                'badge' => 'Verified Supplier',
                'rating' => '4.6',
                'image' => asset('images/marketplace/supplier-4.jpg'),
            ],
        ];
    }
}
