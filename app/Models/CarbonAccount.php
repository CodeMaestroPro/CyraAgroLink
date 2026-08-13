<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user carbon credit balance and sustainability score.
 *
 * @property int $id
 * @property int $user_id
 * @property string $balance_tco2e
 * @property string $lifetime_earned_tco2e
 * @property int $sustainability_score
 */
class CarbonAccount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance_tco2e',
        'lifetime_earned_tco2e',
        'sustainability_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance_tco2e' => 'decimal:2',
            'lifetime_earned_tco2e' => 'decimal:2',
            'sustainability_score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
