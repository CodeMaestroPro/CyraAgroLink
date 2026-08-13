<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\User;

/**
 * Assembles CyraAI Command Center presentation data.
 *
 * Demo prompts and insights mirror the CyraAI Command Center UI
 * until live advisory feeds are wired through repositories.
 */
class CyraAiCommandCenterService
{
    /**
     * @return array<string, mixed>
     */
    public function getCommandCenterData(User $user): array
    {
        return [
            'greeting_name' => $this->firstName($user->name),
            'prompts' => $this->prompts(),
            'insights' => $this->insights(),
            'notifications_count' => 2,
        ];
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Farmer';
    }

    /**
     * @return list<array{label: string, icon: string, href: string}>
     */
    protected function prompts(): array
    {
        return [
            [
                'label' => 'Analyze my farm performance',
                'icon' => 'chart',
                'href' => route('ai.assistant'),
            ],
            [
                'label' => 'Predict market prices for maize',
                'icon' => 'network',
                'href' => route('market.intelligence'),
            ],
            [
                'label' => 'Check weather for my location',
                'icon' => 'weather',
                'href' => route('weather.intelligence'),
            ],
            [
                'label' => 'Recommend crops to plant',
                'icon' => 'crop',
                'href' => route('ai.assistant'),
            ],
        ];
    }

    /**
     * @return list<array{message: string, icon: string}>
     */
    protected function insights(): array
    {
        return [
            [
                'message' => 'Maize price likely to increase by 12% in next 2 weeks.',
                'icon' => 'grain',
            ],
            [
                'message' => 'Heavy rainfall expected in Kaduna State.',
                'icon' => 'rain',
            ],
        ];
    }
}
