<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CropActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Logged crop management activity.
 *
 * @property int $id
 * @property int $crop_id
 * @property int $user_id
 * @property CropActivityType $type
 * @property string $title
 * @property string|null $notes
 * @property string|null $quantity
 */
class CropActivity extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'crop_id',
        'user_id',
        'type',
        'title',
        'notes',
        'quantity',
        'occurred_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CropActivityType::class,
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Crop, $this>
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
