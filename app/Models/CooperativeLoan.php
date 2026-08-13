<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Group loan request funded from cooperative savings.
 *
 * @property int $id
 * @property int $cooperative_id
 * @property int $user_id
 * @property string $reference
 * @property int $amount_ngn
 * @property string $purpose
 * @property string $status
 */
class CooperativeLoan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'user_id',
        'reference',
        'amount_ngn',
        'purpose',
        'status',
        'reviewed_by',
        'reviewed_at',
        'disbursed_at',
        'repaid_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_ngn' => 'integer',
            'reviewed_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'repaid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cooperative, $this>
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
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
}
