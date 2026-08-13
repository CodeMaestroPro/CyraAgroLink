<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Repayment installment against an approved agricultural loan.
 *
 * @property int $id
 * @property int $loan_application_id
 * @property int|null $user_id
 * @property int $amount
 */
class LoanRepayment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'loan_application_id',
        'user_id',
        'amount',
        'note',
        'paid_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LoanApplication, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
