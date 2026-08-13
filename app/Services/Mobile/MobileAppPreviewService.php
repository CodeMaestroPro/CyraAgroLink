<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\User;

/**
 * Assembles mobile app screen preview presentation data.
 */
class MobileAppPreviewService
{
    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(User $user): array
    {
        return [
            'greeting_name' => $this->firstName($user->name) ?: 'Adewale',
            'dashboard' => $this->dashboard(),
            'marketplace' => $this->marketplace(),
            'investments' => $this->investments(),
            'ai_suggestions' => $this->aiSuggestions(),
            'wallet' => $this->wallet(),
            'notifications_count' => 3,
        ];
    }

    protected function firstName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return (string) ($parts[0] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboard(): array
    {
        return [
            'farms' => '3',
            'farms_change' => '+15.2%',
            'crops' => '8',
            'crops_change' => '+16.3%',
            'wallet_balance' => '₦850,000.50',
            'activity' => [
                'title' => 'Payment received',
                'meta' => 'TXN-20481 · Today',
                'amount' => '+₦120,000',
            ],
        ];
    }

    /**
     * @return array{filters: list<string>, products: list<array<string, mixed>>}
     */
    protected function marketplace(): array
    {
        return [
            'filters' => ['All', 'Maize', 'Rice', 'Cassava'],
            'products' => [
                [
                    'name' => 'Maize (White)',
                    'price' => '₦325,000/Ton',
                    'farm' => 'Green Valley Farms',
                    'rating' => '4.8',
                    'image' => 'images/marketplace/maize.jpg',
                ],
                [
                    'name' => 'Rice (Ofada)',
                    'price' => '₦480,000/Ton',
                    'farm' => 'Sunrise Farms',
                    'rating' => '4.9',
                    'image' => 'images/marketplace/rice.jpg',
                ],
                [
                    'name' => 'Cassava',
                    'price' => '₦210,000/Ton',
                    'farm' => 'Green Valley Farms',
                    'rating' => '4.7',
                    'image' => 'images/marketplace/cassava.jpg',
                ],
                [
                    'name' => 'Cocoa Beans',
                    'price' => '₦920,000/Ton',
                    'farm' => 'Nature\'s Pride',
                    'rating' => '4.6',
                    'image' => 'images/marketplace/cocoa.jpg',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function investments(): array
    {
        return [
            'invested' => '₦4,560,000',
            'invested_change' => '+8.5%',
            'returns' => '₦856,000',
            'returns_change' => '+43.2%',
            'active' => [
                [
                    'name' => 'Green Valley Farm',
                    'roi' => '18.4%',
                    'progress' => '15.4%',
                    'image' => 'images/farms/green-valley.jpg',
                ],
                [
                    'name' => 'Sunrise Farm',
                    'roi' => '16.2%',
                    'progress' => '12.8%',
                    'image' => 'images/farms/sunrise.jpg',
                ],
                [
                    'name' => 'Rice Expansion',
                    'roi' => '14.9%',
                    'progress' => '11.1%',
                    'image' => 'images/investments/rice-production.jpg',
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, icon: string}>
     */
    protected function aiSuggestions(): array
    {
        return [
            ['label' => 'Diagnose plant disease', 'icon' => 'leaf'],
            ['label' => 'Fertilizer recommendation', 'icon' => 'flask'],
            ['label' => 'Best planting time', 'icon' => 'calendar'],
            ['label' => 'Weather advice', 'icon' => 'cloud'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function wallet(): array
    {
        return [
            'balance' => '₦850,000.50',
            'transactions' => [
                [
                    'title' => 'Payment Received',
                    'meta' => 'Today · 09:24',
                    'amount' => '+₦120,000',
                    'tone' => 'credit',
                ],
                [
                    'title' => 'Transport Payment',
                    'meta' => 'Yesterday · 16:10',
                    'amount' => '-₦50,000',
                    'tone' => 'debit',
                ],
                [
                    'title' => 'Investment Return',
                    'meta' => '18 May · 11:05',
                    'amount' => '+₦71,000',
                    'tone' => 'credit',
                ],
            ],
        ];
    }
}
