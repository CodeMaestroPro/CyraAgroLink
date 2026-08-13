<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single member ballot on a cooperative vote.
 *
 * @property int $id
 * @property int $cooperative_vote_id
 * @property int $user_id
 * @property string $choice
 */
class CooperativeBallot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_vote_id',
        'user_id',
        'choice',
    ];

    /**
     * @return BelongsTo<CooperativeVote, $this>
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(CooperativeVote::class, 'cooperative_vote_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
