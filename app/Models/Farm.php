<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registered farm belonging to a platform user.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $state
 * @property string|null $local_government
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $size_hectares
 * @property string|null $soil_type
 * @property string|null $description
 * @property array<int, mixed>|null $crops
 * @property array<string, mixed>|null $documents
 * @property int $registration_step
 * @property FarmStatus $status
 */
class Farm extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'state',
        'local_government',
        'address',
        'latitude',
        'longitude',
        'size_hectares',
        'soil_type',
        'description',
        'crops',
        'documents',
        'registration_step',
        'status',
        'registered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'size_hectares' => 'decimal:2',
            'crops' => 'array',
            'documents' => 'array',
            'registration_step' => 'integer',
            'status' => FarmStatus::class,
            'registered_at' => 'datetime',
        ];
    }

    /**
     * Owning farmer / user account.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crop cycles registered on this farm.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Crop, $this>
     */
    public function cropRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Crop::class);
    }

    /**
     * Formatted coordinates for display.
     */
    public function formattedCoordinates(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $latHemisphere = $lat >= 0 ? 'N' : 'S';
        $lngHemisphere = $lng >= 0 ? 'E' : 'W';

        return sprintf(
            '%.4f° %s, %.4f° %s',
            abs($lat),
            $latHemisphere,
            abs($lng),
            $lngHemisphere
        );
    }

    /**
     * Determine whether registration is complete.
     */
    public function isRegistered(): bool
    {
        return in_array($this->status, [FarmStatus::Active, FarmStatus::PendingReview], true);
    }
}
