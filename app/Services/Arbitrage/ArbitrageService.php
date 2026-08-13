<?php

declare(strict_types=1);

namespace App\Services\Arbitrage;

use App\Models\ArbitrageOpportunity;

/**
 * AI arbitrage opportunity discovery and presentation.
 */
class ArbitrageService
{
    /**
     * Build dashboard payload for the best active opportunity.
     *
     * @return array{
     *     opportunity: ArbitrageOpportunity,
     *     costs: list<array{label: string, amount: int}>,
     *     total_cost: int
     * }
     */
    public function getDashboard(): array
    {
        $opportunity = $this->resolveBestOpportunity();

        $costs = [
            ['label' => 'Transportation', 'amount' => $opportunity->transport_cost],
            ['label' => 'Warehouse', 'amount' => $opportunity->warehouse_cost],
            ['label' => 'Charges & Fees', 'amount' => $opportunity->fees_cost],
        ];

        return [
            'opportunity' => $opportunity,
            'costs' => $costs,
            'total_cost' => $opportunity->totalCost(),
        ];
    }

    /**
     * Resolve a specific opportunity or fall back to the best one.
     */
    public function resolveOpportunity(?int $id = null): ArbitrageOpportunity
    {
        if ($id !== null) {
            return ArbitrageOpportunity::query()
                ->where('status', 'active')
                ->findOrFail($id);
        }

        return $this->resolveBestOpportunity();
    }

    /**
     * Ensure demo opportunity exists, then return the best active row.
     */
    protected function resolveBestOpportunity(): ArbitrageOpportunity
    {
        $this->ensureDemoOpportunity();

        $opportunity = ArbitrageOpportunity::query()
            ->where('status', 'active')
            ->where('is_best', true)
            ->latest('id')
            ->first()
            ?? ArbitrageOpportunity::query()
                ->where('status', 'active')
                ->latest('id')
                ->first();

        if ($opportunity === null) {
            throw new \App\Exceptions\BusinessLogicException(
                'No active arbitrage opportunities are available yet.',
                'ARBITRAGE_EMPTY',
                404
            );
        }

        return $opportunity;
    }

    /**
     * Seed the Kano → Lagos Maize opportunity for first-run UI fidelity.
     */
    protected function ensureDemoOpportunity(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (ArbitrageOpportunity::query()->exists()) {
            return;
        }

        ArbitrageOpportunity::query()->create([
            'commodity_name' => 'Maize',
            'buy_market' => 'Kano',
            'sell_market' => 'Lagos',
            'buy_price_per_ton' => 300000,
            'sell_price_per_ton' => 345600,
            'transport_cost' => 22000,
            'warehouse_cost' => 8000,
            'fees_cost' => 6000,
            'roi_percent' => 18.7,
            'recommendation_title' => 'High opportunity!',
            'recommendation_body' => 'Demand in Lagos is high this week. Consider executing within 3 days.',
            'is_best' => true,
            'status' => 'active',
        ]);
    }
}
