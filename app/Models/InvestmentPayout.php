<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Earnings payout credited from an investment holding.
 *
 * @property int $id
 * @property int $user_id
 * @property int $user_investment_id
 * @property int|null $investment_opportunity_id
 * @property string $title
 * @property string $location
 * @property int $amount
 */
class InvestmentPayout extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_investment_id',
        'investment_opportunity_id',
        'title',
        'location',
        'amount',
        'paid_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<UserInvestment, $this>
     */
    public function investment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class, 'user_investment_id');
    }

    /**
     * @return BelongsTo<InvestmentOpportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(InvestmentOpportunity::class, 'investment_opportunity_id');
    }

    public function formattedAmount(): string
    {
        return '₦'.number_format($this->amount);
    }
}
