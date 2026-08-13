<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Consumer marketplace checkout order.
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property int $total_amount
 * @property string|null $delivery_note
 */
class ConsumerOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'delivery_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
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
     * @return HasMany<ConsumerOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ConsumerOrderItem::class, 'order_id');
    }

    public function formattedTotal(): string
    {
        return '₦'.number_format($this->total_amount);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'paid'], true);
    }

    /**
     * Human-readable receipt reference shown on printed slips.
     */
    public function receiptReference(): string
    {
        return 'CYRA-ORD-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Tamper-evident signature embedded in the receipt QR code.
     */
    public function receiptSignature(): string
    {
        $payload = implode('|', [
            (string) $this->id,
            (string) $this->user_id,
            (string) $this->total_amount,
            (string) ($this->created_at?->getTimestamp() ?? 0),
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
