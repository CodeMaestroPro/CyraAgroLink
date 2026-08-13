<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Farm investment listing on the Investment Marketplace.
 *
 * @property int $id
 * @property string $title
 * @property string $location
 * @property string|null $summary
 * @property float $roi_percent
 * @property int $duration_months
 * @property int $amount
 * @property int $funded_percent
 * @property string|null $image_path
 * @property list<string>|null $images
 * @property bool $is_featured
 * @property string $status
 * @property int $sort_order
 */
class InvestmentOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'location',
        'summary',
        'roi_percent',
        'duration_months',
        'amount',
        'funded_percent',
        'image_path',
        'images',
        'is_featured',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'roi_percent' => 'float',
            'duration_months' => 'integer',
            'amount' => 'integer',
            'funded_percent' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'images' => 'array',
        ];
    }

    /**
     * @return HasMany<UserInvestment, $this>
     */
    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * @return HasMany<InvestmentReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(InvestmentReview::class);
    }

    public function formattedAmount(): string
    {
        return '₦'.number_format($this->amount);
    }

    public function raisedAmount(): int
    {
        return (int) $this->investments()
            ->whereIn('status', ['active', 'matured'])
            ->where('is_seeded', false)
            ->sum('amount');
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->amount - $this->raisedAmount());
    }

    public function refreshFundedPercent(): void
    {
        if ($this->amount < 1) {
            $this->forceFill(['funded_percent' => 0])->save();

            return;
        }

        $percent = (int) min(100, max(0, (int) round(($this->raisedAmount() / $this->amount) * 100)));

        $this->forceFill(['funded_percent' => $percent])->save();
    }

    public function durationLabel(): string
    {
        return __('ui.duration_months', ['count' => $this->duration_months]);
    }

    public function fundedLabel(): string
    {
        return $this->funded_percent.'%';
    }

    /**
     * Stable key used to look up translated title/location/summary copy.
     */
    public function contentKey(): string
    {
        return Str::slug($this->title, '_');
    }

    public function localizedTitle(): string
    {
        return $this->localizedField('title', $this->title);
    }

    public function localizedLocation(): string
    {
        return $this->localizedField('location', $this->location);
    }

    public function localizedSummary(): string
    {
        $fallback = filled($this->summary)
            ? (string) $this->summary
            : __('ui.verified_farm_opportunity');

        return $this->localizedField('summary', $fallback);
    }

    protected function localizedField(string $field, string $fallback): string
    {
        $key = 'opportunities.'.$this->contentKey().'.'.$field;

        return Lang::has($key) ? (string) __($key) : $fallback;
    }

    /**
     * Up to three gallery image paths for this farm.
     *
     * @return list<string>
     */
    public function galleryPaths(): array
    {
        $images = collect($this->images ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();

        if ($images === [] && filled($this->image_path)) {
            $images = [(string) $this->image_path];
        }

        return array_slice($images, 0, 3);
    }

    /**
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        $urls = array_map(fn (string $path) => asset($path), $this->galleryPaths());

        if ($urls === []) {
            return [asset('images/investments/maize-expansion.jpg')];
        }

        return $urls;
    }

    public function imageUrl(): string
    {
        return $this->galleryUrls()[0];
    }

    public function averageRating(): float
    {
        $avg = $this->reviews()->avg('rating');

        return $avg === null ? 0.0 : round((float) $avg, 1);
    }

    public function reviewsCount(): int
    {
        return (int) $this->reviews()->count();
    }
}
