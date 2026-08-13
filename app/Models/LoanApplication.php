<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Agricultural loan application for financial institution workflows.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $borrower
 * @property string $sector
 * @property int $amount
 * @property ApplicationStatus $status
 */
class LoanApplication extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'borrower',
        'sector',
        'purpose',
        'amount',
        'amount_repaid',
        'status',
        'reviewed_at',
        'disbursed_at',
        'closed_at',
        'reviewed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_repaid' => 'integer',
            'status' => ApplicationStatus::class,
            'reviewed_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'closed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<LoanRepayment, $this>
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function formattedAmount(): string
    {
        return '₦'.number_format($this->amount);
    }

    public function outstandingAmount(): int
    {
        return max(0, $this->amount - $this->amount_repaid);
    }

    public function isDisbursed(): bool
    {
        return $this->status === ApplicationStatus::Approved && $this->disbursed_at !== null;
    }
}
