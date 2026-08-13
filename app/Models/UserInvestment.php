<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A user's holding in a farm investment opportunity.
 *
 * @property int $id
 * @property int $user_id
 * @property int $investment_opportunity_id
 * @property int $amount
 * @property int $accrued_earnings
 * @property string $status
 * @property bool $is_seeded
 */
class UserInvestment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['active', 'matured', 'cancelled'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'investment_opportunity_id',
        'amount',
        'accrued_earnings',
        'status',
        'is_seeded',
        'invested_at',
        'matured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'accrued_earnings' => 'integer',
            'is_seeded' => 'boolean',
            'invested_at' => 'datetime',
            'matured_at' => 'datetime',
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
     * @return BelongsTo<InvestmentOpportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(InvestmentOpportunity::class, 'investment_opportunity_id');
    }

    /**
     * @return HasMany<InvestmentPayout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(InvestmentPayout::class);
    }

    public function currentValue(): int
    {
        return $this->amount + $this->accrued_earnings;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function formattedAmount(): string
    {
        return '₦'.number_format($this->amount);
    }

    public function formattedValue(): string
    {
        return '₦'.number_format($this->currentValue());
    }
}
