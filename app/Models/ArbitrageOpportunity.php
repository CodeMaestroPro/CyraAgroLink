<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI-detected commodity arbitrage opportunity between markets.
 *
 * @property int $id
 * @property string $commodity_name
 * @property string $buy_market
 * @property string $sell_market
 * @property int $buy_price_per_ton
 * @property int $sell_price_per_ton
 * @property int $transport_cost
 * @property int $warehouse_cost
 * @property int $fees_cost
 * @property float $roi_percent
 * @property string|null $recommendation_title
 * @property string|null $recommendation_body
 * @property bool $is_best
 * @property string $status
 */
class ArbitrageOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'commodity_name',
        'buy_market',
        'sell_market',
        'buy_price_per_ton',
        'sell_price_per_ton',
        'transport_cost',
        'warehouse_cost',
        'fees_cost',
        'roi_percent',
        'recommendation_title',
        'recommendation_body',
        'is_best',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buy_price_per_ton' => 'integer',
            'sell_price_per_ton' => 'integer',
            'transport_cost' => 'integer',
            'warehouse_cost' => 'integer',
            'fees_cost' => 'integer',
            'roi_percent' => 'float',
            'is_best' => 'boolean',
        ];
    }

    /**
     * Gross spread per ton (sell − buy).
     */
    public function potentialProfitPerTon(): int
    {
        return max(0, $this->sell_price_per_ton - $this->buy_price_per_ton);
    }

    /**
     * Sum of logistics and fee costs per ton.
     */
    public function totalCost(): int
    {
        return $this->transport_cost + $this->warehouse_cost + $this->fees_cost;
    }

    /**
     * Route label e.g. "Kano → Lagos".
     */
    public function routeLabel(): string
    {
        return "{$this->buy_market} → {$this->sell_market}";
    }

    public function formattedBuyPrice(): string
    {
        return '₦'.number_format($this->buy_price_per_ton).' / Ton';
    }

    public function formattedSellPrice(): string
    {
        return '₦'.number_format($this->sell_price_per_ton).' / Ton';
    }

    public function formattedPotentialProfit(): string
    {
        return '₦'.number_format($this->potentialProfitPerTon()).' / Ton';
    }
}
