<?php

declare(strict_types=1);

namespace App\Services\Farmer;

use App\Enums\CropActivityType;
use App\Enums\FarmStatus;
use App\Models\Crop;
use App\Models\CropActivity;
use App\Models\ExchangeOrder;
use App\Models\Farm;
use App\Models\User;
use App\Models\UserInboxNotification;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;

/**
 * Assembles live farmer dashboard presentation data.
 */
class FarmerDashboardService
{
    /**
     * Build the complete dashboard payload for a farmer.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        $farms = Farm::query()
            ->where('user_id', $user->id)
            ->with(['cropRecords' => fn ($q) => $q->where('status', 'active')->latest('id')])
            ->latest('id')
            ->get();

        $activeCrops = Crop::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('farm')
            ->latest('id')
            ->get();

        $pendingOrders = $this->pendingOrdersCount($user);
        $totalCredits = (int) WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        $unread = UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return [
            'greeting_name' => $this->firstName($user->name),
            'stats' => $this->stats($user, $farms, $activeCrops->count(), $totalCredits, $pendingOrders),
            'farms' => $this->mapFarms($farms),
            'activities' => $this->activities($user),
            'earnings' => $this->earnings($user, $totalCredits),
            'ai_recommendation' => $this->aiRecommendation($activeCrops->first()),
            'notifications_count' => $unread,
        ];
    }

    /**
     * Extract a greeting-friendly first name.
     */
    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Farmer';
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return list<array<string, mixed>>
     */
    protected function stats(User $user, Collection $farms, int $activeCrops, int $totalCredits, int $pendingOrders): array
    {
        $registeredFarms = $farms->filter(
            fn (Farm $farm) => in_array($farm->status, [FarmStatus::Active, FarmStatus::PendingReview], true)
        )->count();

        $farmsThisMonth = $farms->filter(
            fn (Farm $farm) => $farm->created_at !== null && $farm->created_at->gte(now()->startOfMonth())
        )->count();

        $creditsThisMonth = (int) WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'credit')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $priorMonthCredits = (int) WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'credit')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');

        $revenueChange = $this->percentChange($priorMonthCredits, $creditsThisMonth);

        return [
            [
                'label' => 'Total Farms',
                'value' => (string) $registeredFarms,
                'meta' => $farmsThisMonth > 0 ? '+ '.$farmsThisMonth.' this month' : 'No new farms this month',
                'meta_tone' => 'amber',
                'meta_href' => route('farms.register'),
            ],
            [
                'label' => 'Active Crops',
                'value' => (string) $activeCrops,
                'meta' => $activeCrops > 0 ? 'In season' : 'Start a crop cycle',
                'meta_tone' => 'amber',
                'meta_href' => route('crops.manage'),
            ],
            [
                'label' => 'Total Revenue',
                'value' => '₦'.number_format($totalCredits),
                'meta' => $this->formatSignedPercent($revenueChange),
                'meta_tone' => $revenueChange >= 0 ? 'green' : 'amber',
                'meta_href' => route('wallet.index'),
            ],
            [
                'label' => 'Pending Orders',
                'value' => (string) $pendingOrders,
                'meta' => $pendingOrders > 0 ? 'View marketplace' : 'No open orders',
                'meta_tone' => 'amber',
                'meta_href' => route('marketplace.index'),
            ],
        ];
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return list<array<string, mixed>>
     */
    protected function mapFarms(Collection $farms): array
    {
        return $farms
            ->take(6)
            ->values()
            ->map(function (Farm $farm): array {
                $crop = $farm->cropRecords->first();
                $cropName = $crop?->name
                    ?? (is_array($farm->crops) && $farm->crops !== [] ? (string) $farm->crops[0] : 'No crop yet');
                $stage = $crop?->growth_stage?->label() ?? match ($farm->status) {
                    FarmStatus::PendingReview => 'Pending review',
                    FarmStatus::Draft => 'Registration',
                    FarmStatus::Inactive => 'Inactive',
                    default => 'Ready',
                };
                $progress = $crop?->progress_percent
                    ?? match ($farm->status) {
                        FarmStatus::Active => 100,
                        FarmStatus::PendingReview => 80,
                        FarmStatus::Draft => max(10, min(90, $farm->registration_step * 20)),
                        default => 0,
                    };

                $state = $farm->state
                    ? (str_contains(strtolower((string) $farm->state), 'state')
                        ? $farm->state
                        : $farm->state.' State')
                    : null;

                $locationParts = array_filter([$farm->local_government, $state]);

                return [
                    'name' => $farm->name ?: ('Farm #'.$farm->id),
                    'location' => $locationParts !== [] ? implode(', ', $locationParts) : 'Location not set',
                    'crop' => $cropName,
                    'stage' => $stage,
                    'progress' => (int) $progress,
                    'image' => $this->resolveFarmImage($farm, $cropName),
                ];
            })
            ->all();
    }

    /**
     * Pick a representative photo for a farm card from name/crop assets.
     */
    protected function resolveFarmImage(Farm $farm, string $cropName): string
    {
        $farmName = strtolower(trim((string) $farm->name));
        $cropKey = strtolower(trim($cropName));

        $namedFarms = [
            'green valley' => 'images/farms/green-valley.jpg',
            'sunrise' => 'images/farms/sunrise.jpg',
        ];

        foreach ($namedFarms as $needle => $path) {
            if ($farmName !== '' && str_contains($farmName, $needle)) {
                return asset($path);
            }
        }

        $candidates = array_values(array_filter([
            $cropKey !== '' && $cropKey !== 'no crop yet' ? $cropKey : null,
            ...(is_array($farm->crops) ? array_map(
                static fn ($crop): string => strtolower(trim((string) $crop)),
                $farm->crops
            ) : []),
        ]));

        $cropImages = [
            'maize' => 'images/marketplace/maize.jpg',
            'corn' => 'images/marketplace/maize.jpg',
            'rice' => 'images/marketplace/rice.jpg',
            'cassava' => 'images/marketplace/cassava.jpg',
            'yam' => 'images/marketplace/yam.jpg',
            'sorghum' => 'images/marketplace/sorghum.jpg',
            'soybean' => 'images/marketplace/soybean.jpg',
            'soya' => 'images/marketplace/soybean.jpg',
            'groundnut' => 'images/marketplace/groundnut.jpg',
            'peanut' => 'images/marketplace/groundnut.jpg',
            'tomato' => 'images/marketplace/tomato.jpg',
            'millet' => 'images/marketplace/millet.jpg',
            'sesame' => 'images/marketplace/sesame.jpg',
            'plantain' => 'images/marketplace/plantain.jpg',
            'cocoa' => 'images/marketplace/cocoa.jpg',
            'layer' => 'images/investments/layers-1.jpg',
            'poultry' => 'images/investments/layers-1.jpg',
            'broiler' => 'images/investments/broilers-1.jpg',
            'fish' => 'images/investments/fish-1.jpg',
            'catfish' => 'images/investments/fish-1.jpg',
            'aquaculture' => 'images/investments/fish-1.jpg',
        ];

        foreach ($candidates as $candidate) {
            foreach ($cropImages as $needle => $path) {
                if ($candidate !== '' && str_contains($candidate, $needle)) {
                    return asset($path);
                }
            }
        }

        return asset('images/investments/hero-field.jpg');
    }

    /**
     * @return list<array{title: string, detail: string, time: string, icon: string}>
     */
    protected function activities(User $user): array
    {
        $items = collect();

        $orders = ExchangeOrder::query()
            ->with('commodity')
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('commodity', fn ($q) => $q->where('user_id', $user->id));
            })
            ->latest('id')
            ->limit(6)
            ->get();

        foreach ($orders as $order) {
            $commodity = $order->commodity?->name ?? 'Commodity';
            $notional = (int) $order->quantity_tons * (int) $order->price_per_ton;
            $items->push([
                'title' => ucfirst($order->side).' order '.$order->status,
                'detail' => $commodity.' · '.$order->quantity_tons.'t · ₦'.number_format($notional),
                'time' => $order->created_at?->diffForHumans(short: true) ?? '',
                'icon' => 'order',
                'sort' => $order->created_at?->timestamp ?? 0,
            ]);
        }

        $payments = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'credit')
            ->latest('id')
            ->limit(6)
            ->get();

        foreach ($payments as $payment) {
            $items->push([
                'title' => $payment->title ?: 'Payment received',
                'detail' => ($payment->detail ?: ucfirst($payment->category)).' · ₦'.number_format((int) $payment->amount),
                'time' => $payment->created_at?->diffForHumans(short: true) ?? '',
                'icon' => 'payment',
                'sort' => $payment->created_at?->timestamp ?? 0,
            ]);
        }

        $cropEvents = CropActivity::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(6)
            ->get();

        foreach ($cropEvents as $event) {
            $items->push([
                'title' => $event->title,
                'detail' => $event->notes ?: ($event->type?->value ?? 'Crop activity'),
                'time' => ($event->occurred_at ?? $event->created_at)?->diffForHumans(short: true) ?? '',
                'icon' => $event->type === CropActivityType::Harvest ? 'harvest' : 'ai',
                'sort' => ($event->occurred_at ?? $event->created_at)?->timestamp ?? 0,
            ]);
        }

        $mapped = $items
            ->sortByDesc('sort')
            ->take(6)
            ->values()
            ->map(fn (array $row) => [
                'title' => $row['title'],
                'detail' => $row['detail'],
                'time' => $row['time'],
                'icon' => $row['icon'],
            ])
            ->all();

        if ($mapped === []) {
            return [
                [
                    'title' => 'Welcome to your farm hub',
                    'detail' => 'Register a farm or list a crop to see live activity here.',
                    'time' => 'now',
                    'icon' => 'ai',
                ],
            ];
        }

        return $mapped;
    }

    /**
     * @return array<string, mixed>
     */
    protected function earnings(User $user, int $totalCredits): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $labels[] = $month->format('M');
            $values[] = (int) WalletTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'credit')
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('amount');
        }

        $thisMonth = $values[count($values) - 1] ?? 0;
        $lastMonth = $values[count($values) - 2] ?? 0;
        $change = $this->percentChange($lastMonth, $thisMonth);

        return [
            'total' => '₦'.number_format($totalCredits),
            'trend_label' => $this->formatSignedPercent($change).' from last month',
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function aiRecommendation(?Crop $crop): array
    {
        if ($crop === null) {
            return [
                'message' => 'Register a crop cycle to receive personalized field advisories for your farms.',
                'action_label' => 'Manage Crops',
                'action_href' => route('crops.manage'),
            ];
        }

        return [
            'message' => $crop->ai_recommendation
                ?: 'Keep monitoring '.$crop->name.' — next up: '.($crop->next_activity ?: 'routine field checks').'.',
            'action_label' => 'Ask AI Assistant',
            'action_href' => route('ai.assistant'),
        ];
    }

    protected function pendingOrdersCount(User $user): int
    {
        $onListings = (int) ExchangeOrder::query()
            ->where('status', 'open')
            ->where('side', 'buy')
            ->whereHas('commodity', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $ownOpen = (int) ExchangeOrder::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->count();

        return $onListings + $ownOpen;
    }

    protected function percentChange(int $previous, int $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function formatSignedPercent(float $value): string
    {
        $prefix = $value > 0 ? '↑ ' : ($value < 0 ? '↓ ' : '');

        return $prefix.rtrim(rtrim(number_format(abs($value), 1), '0'), '.').'%';
    }
}
