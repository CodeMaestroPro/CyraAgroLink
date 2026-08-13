<?php

declare(strict_types=1);

namespace App\Services\Investment;

use App\Exceptions\BusinessLogicException;
use App\Models\InvestmentOpportunity;
use App\Models\InvestmentReview;
use App\Models\User;
use App\Models\UserInboxNotification;
use App\Models\UserInvestment;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Investment Marketplace catalog, galleries, reviews, and wallet-funded investing.
 */
class InvestmentMarketplaceService
{
    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * Build marketplace listing payload.
     *
     * @return array<string, mixed>
     */
    public function getCatalog(User $user, bool $showAll = false, ?string $search = null): array
    {
        $this->ensureCatalog();
        $this->syncFundedPercents();

        $search = trim((string) $search);
        $hasSearch = $search !== '';

        $baseQuery = InvestmentOpportunity::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('status', 'active');

        if ($hasSearch) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $baseQuery->where(function ($builder) use ($like): void {
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('summary', 'like', $like);
            });
        }

        $featured = (clone $baseQuery)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        // Searching always returns the full matching set.
        $opportunities = ($showAll || $hasSearch)
            ? (clone $baseQuery)->orderBy('sort_order')->orderBy('id')->get()
            : $featured;

        $walletBalance = $this->walletService->getBalance($user);

        return [
            'featured' => $featured,
            'opportunities' => $opportunities,
            'show_all' => $showAll || $hasSearch,
            'query' => $search,
            'wallet_balance' => $walletBalance,
            'can_invest' => $walletBalance >= 10000,
            'notifications_count' => $this->unreadNotificationsCount($user),
        ];
    }

    /**
     * Build a single farm opportunity detail page payload.
     *
     * @return array<string, mixed>
     */
    public function getOpportunityDetails(User $user, InvestmentOpportunity $opportunity): array
    {
        $this->ensureCatalog();
        $opportunity->refresh();
        $this->syncOpportunityGalleries();
        $opportunity->refresh();

        $reviews = $opportunity->reviews()
            ->with('user:id,name')
            ->latest('id')
            ->limit(40)
            ->get();

        $userReview = $opportunity->reviews()
            ->where('user_id', $user->id)
            ->first();

        $hasHolding = UserInvestment::query()
            ->where('user_id', $user->id)
            ->where('investment_opportunity_id', $opportunity->id)
            ->whereIn('status', ['active', 'matured'])
            ->exists();

        $opportunity->refreshFundedPercent();
        $remaining = $opportunity->remainingCapacity();
        $walletBalance = $this->walletService->getBalance($user);
        $minTicket = $remaining > 0 && $remaining < 10000 ? $remaining : 10000;

        return [
            'opportunity' => $opportunity,
            'gallery' => $opportunity->galleryUrls(),
            'reviews' => $reviews->map(fn (InvestmentReview $review) => [
                'id' => $review->id,
                'author' => $review->user?->name ?? 'Investor',
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'when' => $review->created_at?->diffForHumans() ?? '',
                'is_mine' => (int) $review->user_id === (int) $user->id,
            ])->all(),
            'user_review' => $userReview,
            'average_rating' => $opportunity->averageRating(),
            'reviews_count' => $opportunity->reviewsCount(),
            'wallet_balance' => $walletBalance,
            'remaining' => $remaining,
            'min_ticket' => $minTicket,
            'has_holding' => $hasHolding,
            'can_review' => $hasHolding,
            'can_invest' => $opportunity->status === 'active' && $remaining >= 1000 && $walletBalance >= $minTicket,
            'is_open' => $opportunity->status === 'active',
            'notifications_count' => $this->unreadNotificationsCount($user),
        ];
    }

    /**
     * Ensure the shared opportunity catalog exists.
     */
    public function ensureCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        $this->ensureDemoCatalog();
        $this->syncOpportunityGalleries();
    }

    /**
     * Invest wallet funds into an opportunity.
     *
     * @param  array{amount: int}  $data
     */
    public function invest(User $user, InvestmentOpportunity $opportunity, array $data): UserInvestment
    {
        if ($opportunity->status !== 'active') {
            throw new BusinessLogicException('This opportunity is not open for investment.');
        }

        $amount = (int) $data['amount'];
        $remainingPreview = $opportunity->remainingCapacity();
        $minTicket = $remainingPreview > 0 && $remainingPreview < 10000 ? $remainingPreview : 10000;

        if ($amount < $minTicket) {
            throw new BusinessLogicException(
                $minTicket < 10000
                    ? 'Invest the remaining ₦'.number_format($minTicket).' to close this raise.'
                    : 'Minimum investment is ₦10,000.'
            );
        }

        return DB::transaction(function () use ($user, $opportunity, $amount): UserInvestment {
            $locked = InvestmentOpportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'active') {
                throw new BusinessLogicException('This opportunity is not open for investment.');
            }

            $remaining = $locked->remainingCapacity();

            if ($amount > $remaining) {
                throw new BusinessLogicException(
                    $remaining < 1
                        ? 'This opportunity is fully funded.'
                        : 'Only ₦'.number_format($remaining).' remains available on this opportunity.'
                );
            }

            $investment = UserInvestment::query()->create([
                'user_id' => $user->id,
                'investment_opportunity_id' => $locked->id,
                'amount' => $amount,
                'accrued_earnings' => 0,
                'status' => 'active',
                'is_seeded' => false,
                'invested_at' => now(),
            ]);

            $this->walletService->payForInvestment(
                $user,
                $amount,
                $investment,
                'Invest '.$locked->title
            );

            $locked->refreshFundedPercent();

            UserInboxNotification::query()->create([
                'user_id' => $user->id,
                'title' => 'Investment confirmed',
                'body' => 'You invested ₦'.number_format($amount).' in '.$locked->title.'.',
                'tone' => 'success',
                'category' => 'investment',
                'notification_key' => 'invest-'.$investment->id,
                'read_at' => null,
            ]);

            return $investment->fresh('opportunity') ?? $investment;
        });
    }

    /**
     * Create or update the authenticated user's review for an opportunity.
     *
     * @param  array{rating: int, title?: string|null, body: string}  $data
     */
    public function storeReview(User $user, InvestmentOpportunity $opportunity, array $data): InvestmentReview
    {
        if ($opportunity->status !== 'active') {
            throw new BusinessLogicException('Reviews are only open for active farm opportunities.');
        }

        $hasHolding = UserInvestment::query()
            ->where('user_id', $user->id)
            ->where('investment_opportunity_id', $opportunity->id)
            ->whereIn('status', ['active', 'matured'])
            ->exists();

        if (! $hasHolding) {
            throw new BusinessLogicException('Invest in this farm before leaving a review.');
        }

        /** @var InvestmentReview $review */
        $review = InvestmentReview::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'investment_opportunity_id' => $opportunity->id,
            ],
            [
                'rating' => (int) $data['rating'],
                'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : null,
                'body' => trim((string) $data['body']),
            ]
        );

        return $review->fresh('user') ?? $review;
    }

    protected function syncFundedPercents(): void
    {
        InvestmentOpportunity::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->each(fn (InvestmentOpportunity $opportunity) => $opportunity->refreshFundedPercent());
    }

    /**
     * Fill missing cover/gallery/summary for seeded farm titles without overwriting edits.
     */
    protected function syncOpportunityGalleries(): void
    {
        InvestmentOpportunity::query()
            ->where('title', 'Cassava Farm')
            ->where(function ($query): void {
                $query->whereNull('location')->orWhere('location', '=', '');
            })
            ->update(['location' => 'Kogi State']);

        foreach ($this->galleryMap() as $title => $meta) {
            InvestmentOpportunity::query()
                ->where('title', $title)
                ->get()
                ->each(function (InvestmentOpportunity $opportunity) use ($meta): void {
                    $needsImages = blank($opportunity->image_path)
                        || ! is_array($opportunity->images)
                        || count($opportunity->images) < 1;
                    $needsSummary = blank($opportunity->summary);

                    if (! $needsImages && ! $needsSummary) {
                        return;
                    }

                    $opportunity->forceFill(array_filter([
                        'image_path' => $needsImages ? $meta['images'][0] : null,
                        'images' => $needsImages ? $meta['images'] : null,
                        'summary' => $needsSummary ? $meta['summary'] : null,
                    ], fn ($value) => $value !== null))->save();
                });
        }
    }

    /**
     * @return array<string, array{images: list<string>, summary: string}>
     */
    protected function galleryMap(): array
    {
        return [
            'Maize Expansion' => [
                'images' => [
                    'images/investments/maize-1.jpg',
                    'images/investments/maize-2.jpg',
                    'images/investments/maize-3.jpg',
                ],
                'summary' => 'Expand maize production with improved seed, fertilizer, and post-harvest handling across Oyo farm clusters.',
            ],
            'Cassava Farm' => [
                'images' => [
                    'images/investments/cassava-1.jpg',
                    'images/investments/cassava-2.jpg',
                    'images/investments/cassava-3.jpg',
                ],
                'summary' => 'Cassava cultivation and processing-ready roots for starch and garri markets in Kogi State.',
            ],
            'Rice Production' => [
                'images' => [
                    'images/investments/rice-1.jpg',
                    'images/investments/rice-2.jpg',
                    'images/investments/rice-3.jpg',
                ],
                'summary' => 'Irrigated rice paddies with milling linkages serving Niger State and nearby food markets.',
            ],
            'Layers Poultry Unit' => [
                'images' => [
                    'images/investments/layers-1.jpg',
                    'images/investments/layers-2.jpg',
                    'images/investments/layers-3.jpg',
                ],
                'summary' => 'Battery and deep-litter layers unit focused on egg production, feed efficiency, and flock health.',
            ],
            'Broiler Poultry Farm' => [
                'images' => [
                    'images/investments/broilers-1.jpg',
                    'images/investments/broilers-2.jpg',
                    'images/investments/broilers-3.jpg',
                ],
                'summary' => 'Short-cycle broiler birds raised for meat markets with biosecurity and buyer offtake contracts.',
            ],
            'Fish Farming (Catfish)' => [
                'images' => [
                    'images/investments/fish-1.jpg',
                    'images/investments/fish-2.jpg',
                    'images/investments/fish-3.jpg',
                ],
                'summary' => 'Catfish aquaculture in earthen ponds and tanks with fingerling stocking and harvest cycles.',
            ],
        ];
    }

    protected function ensureDemoCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (InvestmentOpportunity::query()->exists()) {
            $this->ensureExtendedCatalog();

            return;
        }

        DB::transaction(function (): void {
            foreach ($this->catalogSeed() as $item) {
                InvestmentOpportunity::query()->create($item);
            }
        });
    }

    protected function ensureExtendedCatalog(): void
    {
        $existing = InvestmentOpportunity::query()->pluck('title')->all();

        foreach ($this->catalogSeed() as $item) {
            if (in_array($item['title'], $existing, true)) {
                continue;
            }

            InvestmentOpportunity::query()->create($item);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function catalogSeed(): array
    {
        $galleries = $this->galleryMap();

        return [
            [
                'title' => 'Maize Expansion',
                'location' => 'Oyo State',
                'summary' => $galleries['Maize Expansion']['summary'],
                'roi_percent' => 24,
                'duration_months' => 6,
                'amount' => 2500000,
                'funded_percent' => 0,
                'image_path' => $galleries['Maize Expansion']['images'][0],
                'images' => $galleries['Maize Expansion']['images'],
                'sort_order' => 1,
                'is_featured' => true,
                'status' => 'active',
            ],
            [
                'title' => 'Cassava Farm',
                'location' => 'Kogi State',
                'summary' => $galleries['Cassava Farm']['summary'],
                'roi_percent' => 20,
                'duration_months' => 8,
                'amount' => 3000000,
                'funded_percent' => 0,
                'image_path' => $galleries['Cassava Farm']['images'][0],
                'images' => $galleries['Cassava Farm']['images'],
                'sort_order' => 2,
                'is_featured' => true,
                'status' => 'active',
            ],
            [
                'title' => 'Rice Production',
                'location' => 'Niger State',
                'summary' => $galleries['Rice Production']['summary'],
                'roi_percent' => 22,
                'duration_months' => 7,
                'amount' => 5000000,
                'funded_percent' => 0,
                'image_path' => $galleries['Rice Production']['images'][0],
                'images' => $galleries['Rice Production']['images'],
                'sort_order' => 3,
                'is_featured' => true,
                'status' => 'active',
            ],
            [
                'title' => 'Layers Poultry Unit',
                'location' => 'Ogun State',
                'summary' => $galleries['Layers Poultry Unit']['summary'],
                'roi_percent' => 21,
                'duration_months' => 6,
                'amount' => 2800000,
                'funded_percent' => 0,
                'image_path' => $galleries['Layers Poultry Unit']['images'][0],
                'images' => $galleries['Layers Poultry Unit']['images'],
                'sort_order' => 4,
                'is_featured' => false,
                'status' => 'active',
            ],
            [
                'title' => 'Broiler Poultry Farm',
                'location' => 'Oyo State',
                'summary' => $galleries['Broiler Poultry Farm']['summary'],
                'roi_percent' => 23,
                'duration_months' => 4,
                'amount' => 2200000,
                'funded_percent' => 0,
                'image_path' => $galleries['Broiler Poultry Farm']['images'][0],
                'images' => $galleries['Broiler Poultry Farm']['images'],
                'sort_order' => 5,
                'is_featured' => false,
                'status' => 'active',
            ],
            [
                'title' => 'Fish Farming (Catfish)',
                'location' => 'Delta State',
                'summary' => $galleries['Fish Farming (Catfish)']['summary'],
                'roi_percent' => 20,
                'duration_months' => 8,
                'amount' => 2600000,
                'funded_percent' => 0,
                'image_path' => $galleries['Fish Farming (Catfish)']['images'][0],
                'images' => $galleries['Fish Farming (Catfish)']['images'],
                'sort_order' => 6,
                'is_featured' => false,
                'status' => 'active',
            ],
        ];
    }

    protected function unreadNotificationsCount(User $user): int
    {
        return UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
