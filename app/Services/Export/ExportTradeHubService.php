<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\ExportOrder;
use App\Models\Farm;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Live export & international trade hub: orders, destinations, and process tracking.
 */
class ExportTradeHubService
{
    public const USD_TO_NGN = 1550;

    /**
     * Ordered process stages for an export shipment.
     *
     * @var list<string>
     */
    public const STAGES = [
        'request_received',
        'quality_inspection',
        'documentation',
        'customs_clearance',
        'in_transit',
        'delivered',
    ];

    /**
     * @var array<string, array{country: string, code: string}>
     */
    protected array $destinationsCatalog = [
        'NL' => ['country' => 'Netherlands', 'code' => 'NL'],
        'AE' => ['country' => 'United Arab Emirates', 'code' => 'AE'],
        'GB' => ['country' => 'United Kingdom', 'code' => 'GB'],
        'SA' => ['country' => 'Saudi Arabia', 'code' => 'SA'],
        'US' => ['country' => 'United States', 'code' => 'US'],
        'CN' => ['country' => 'China', 'code' => 'CN'],
        'DE' => ['country' => 'Germany', 'code' => 'DE'],
    ];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getHubData(User $user, ?int $focusOrderId = null): array
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            if (! \App\Support\DemoSeeding::allowed()) {
                throw new BusinessLogicException('Register a farm before using the Export Trade Hub.', 'FARM_REQUIRED', 422);
            }
            $this->ensureSeedFarm($user);
            $farms = $this->farmsForUser($user);
        }

        $this->ensureStarterOrders($user, $farms);

        $orders = ExportOrder::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(40)
            ->get();

        $focus = $this->resolveFocusOrder($orders, $focusOrderId);

        return [
            'kpis' => $this->kpis($orders),
            'destinations' => $this->topDestinations($orders),
            'process' => $this->exportProcess($focus),
            'orders' => $orders->map(fn (ExportOrder $order) => $this->presentOrder($order))->all(),
            'focus_order_id' => $focus?->id,
            'farms' => $farms->map(fn (Farm $farm) => [
                'id' => $farm->id,
                'name' => $farm->name ?: 'Untitled farm',
            ])->values()->all(),
            'destination_options' => collect($this->destinationsCatalog)
                ->map(fn (array $item) => [
                    'code' => $item['code'],
                    'country' => $item['country'],
                ])
                ->values()
                ->all(),
            'product_options' => $this->productOptions($farms),
            'actions' => [
                'store_url' => route('export.orders.store'),
                'logistics_url' => route('logistics.index'),
            ],
            'notifications_count' => 5,
        ];
    }

    /**
     * @param  array{product: string, quantity_tons: float|int|string, destination_code: string, farm_id?: int|null, value_usd?: int|null}  $data
     */
    public function createOrder(User $user, array $data): ExportOrder
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            throw new BusinessLogicException('Register a farm before creating an export order.');
        }

        $code = strtoupper((string) $data['destination_code']);
        if (! isset($this->destinationsCatalog[$code])) {
            throw new BusinessLogicException('Select a supported export destination.');
        }

        $quantity = round((float) $data['quantity_tons'], 2);
        if ($quantity < 0.5) {
            throw new BusinessLogicException('Export quantity must be at least 0.5 tons.');
        }

        $farmId = isset($data['farm_id']) ? (int) $data['farm_id'] : null;
        $farm = $farmId
            ? $farms->firstWhere('id', $farmId)
            : $farms->first();

        if ($farm === null) {
            throw new BusinessLogicException('Selected farm was not found.', 'EXPORT_FORBIDDEN', 403);
        }

        $product = trim((string) $data['product']);
        if ($product === '') {
            throw new BusinessLogicException('Product is required.');
        }

        $valueUsd = isset($data['value_usd']) && (int) $data['value_usd'] > 0
            ? (int) $data['value_usd']
            : $this->estimateValueUsd($product, $quantity);

        $destination = $this->destinationsCatalog[$code];

        return ExportOrder::query()->create([
            'user_id' => $user->id,
            'farm_id' => $farm->id,
            'reference' => $this->nextReference($user),
            'product' => Str::limit($product, 120, ''),
            'quantity_tons' => $quantity,
            'destination_country' => $destination['country'],
            'destination_code' => $destination['code'],
            'value_usd' => $valueUsd,
            'status' => 'request_received',
            'meta' => ['source' => 'hub'],
        ]);
    }

    /**
     * Move an export order to the next process stage.
     */
    public function advanceOrder(User $user, ExportOrder $order): ExportOrder
    {
        if ($order->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to update this export order.', 'EXPORT_FORBIDDEN', 403);
        }

        if (! $order->isOpen()) {
            throw new BusinessLogicException('This export order is already closed.');
        }

        $index = array_search($order->status, self::STAGES, true);
        if ($index === false || $index >= count(self::STAGES) - 1) {
            throw new BusinessLogicException('No further process steps are available.');
        }

        $next = self::STAGES[$index + 1];

        return DB::transaction(function () use ($user, $order, $next): ExportOrder {
            /** @var ExportOrder $locked */
            $locked = ExportOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new BusinessLogicException('This export order is already closed.');
            }

            $payload = ['status' => $next];

            if ($next === 'in_transit') {
                $payload['shipped_at'] = now();
            }

            if ($next === 'delivered') {
                $payload['delivered_at'] = now();
            }

            $locked->forceFill($payload)->save();

            if ($next === 'delivered') {
                $ngn = max(1, (int) round($locked->value_usd * self::USD_TO_NGN));
                $this->walletService->creditExportProceeds(
                    $user,
                    $ngn,
                    $locked,
                    $locked->reference.' delivered to '.$locked->destination_country
                );
            }

            return $locked->refresh();
        });
    }

    /**
     * @param  Collection<int, ExportOrder>  $orders
     * @return list<array{label: string, value: string}>
     */
    protected function kpis(Collection $orders): array
    {
        $active = $orders->filter(fn (ExportOrder $order) => $order->isOpen());
        $inTransit = $orders->filter(fn (ExportOrder $order) => $order->isInTransit());
        $countries = $orders->pluck('destination_code')->unique()->count();
        $totalValue = (int) $orders->sum('value_usd');

        return [
            ['label' => 'Active Exports', 'value' => (string) $active->count()],
            ['label' => 'Total Value', 'value' => $this->formatUsdCompact($totalValue)],
            ['label' => 'Countries', 'value' => (string) max($countries, 0)],
            ['label' => 'Orders in Transit', 'value' => (string) $inTransit->count()],
        ];
    }

    /**
     * @param  Collection<int, ExportOrder>  $orders
     * @return list<array{country: string, value: string, code: string}>
     */
    protected function topDestinations(Collection $orders): array
    {
        $grouped = $orders
            ->groupBy('destination_code')
            ->map(function (Collection $group, string $code): array {
                $first = $group->first();

                return [
                    'country' => $first?->destination_country ?? ($this->destinationsCatalog[$code]['country'] ?? $code),
                    'code' => $code,
                    'value_raw' => (int) $group->sum('value_usd'),
                ];
            })
            ->sortByDesc('value_raw')
            ->take(5)
            ->values();

        if ($grouped->isEmpty()) {
            return collect($this->destinationsCatalog)
                ->take(5)
                ->map(fn (array $item) => [
                    'country' => $item['country'],
                    'code' => $item['code'],
                    'value' => '$0',
                ])
                ->values()
                ->all();
        }

        return $grouped->map(fn (array $item) => [
            'country' => $item['country'],
            'code' => $item['code'],
            'value' => $this->formatUsd($item['value_raw']),
        ])->all();
    }

    /**
     * @return list<array{label: string, done: bool, current: bool, key: string}>
     */
    protected function exportProcess(?ExportOrder $order): array
    {
        $currentIndex = $order
            ? array_search($order->status, self::STAGES, true)
            : false;
        $currentIndex = $currentIndex === false ? -1 : (int) $currentIndex;

        return collect(self::STAGES)->map(fn (string $key, int $index) => [
            'key' => $key,
            'label' => $this->stageLabel($key),
            'done' => $currentIndex >= $index,
            'current' => $currentIndex === $index,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentOrder(ExportOrder $order): array
    {
        $index = array_search($order->status, self::STAGES, true);
        $canAdvance = $order->isOpen() && $index !== false && $index < count(self::STAGES) - 1;
        $nextKey = $canAdvance ? self::STAGES[(int) $index + 1] : null;

        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'product' => $order->product,
            'quantity' => number_format((float) $order->quantity_tons, 1).' t',
            'destination' => $order->destination_country,
            'code' => $order->destination_code,
            'value' => $this->formatUsd($order->value_usd),
            'status' => $this->stageLabel($order->status),
            'status_key' => $order->status,
            'can_advance' => $canAdvance,
            'next_stage' => $nextKey ? $this->stageLabel($nextKey) : null,
            'advance_label' => $nextKey ? 'Advance to '.$this->stageLabel($nextKey) : 'Advance stage',
            'advance_url' => route('export.orders.advance', $order),
            'focus_url' => route('export.hub', ['order' => $order->id]),
        ];
    }

    /**
     * @param  Collection<int, ExportOrder>  $orders
     */
    protected function resolveFocusOrder(Collection $orders, ?int $focusOrderId): ?ExportOrder
    {
        if ($focusOrderId !== null) {
            $match = $orders->firstWhere('id', $focusOrderId);
            if ($match !== null) {
                return $match;
            }
        }

        return $orders->first(fn (ExportOrder $order) => $order->isOpen())
            ?? $orders->first();
    }

    /**
     * @param  Collection<int, Farm>  $farms
     */
    protected function ensureStarterOrders(User $user, Collection $farms): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (ExportOrder::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $farm = $farms->first();
        $product = $this->productOptions($farms)[0] ?? 'Maize';

        $seeds = [
            ['code' => 'NL', 'status' => 'in_transit', 'tons' => 120, 'value' => 850000, 'days' => 18],
            ['code' => 'AE', 'status' => 'customs_clearance', 'tons' => 60, 'value' => 420000, 'days' => 12],
            ['code' => 'GB', 'status' => 'documentation', 'tons' => 45, 'value' => 310000, 'days' => 9],
            ['code' => 'SA', 'status' => 'quality_inspection', 'tons' => 40, 'value' => 280000, 'days' => 6],
            ['code' => 'US', 'status' => 'request_received', 'tons' => 35, 'value' => 260000, 'days' => 3],
            ['code' => 'DE', 'status' => 'delivered', 'tons' => 90, 'value' => 330000, 'days' => 40],
            ['code' => 'CN', 'status' => 'documentation', 'tons' => 55, 'value' => 340000, 'days' => 11],
        ];

        foreach ($seeds as $index => $seed) {
            $destination = $this->destinationsCatalog[$seed['code']];
            $order = new ExportOrder([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'reference' => 'EXP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'product' => $product,
                'quantity_tons' => $seed['tons'],
                'destination_country' => $destination['country'],
                'destination_code' => $destination['code'],
                'value_usd' => $seed['value'],
                'status' => $seed['status'],
                'meta' => ['seeded' => true],
            ]);

            if (in_array($seed['status'], ['in_transit', 'delivered'], true)) {
                $order->shipped_at = now()->subDays(max(1, $seed['days'] - 2));
            }

            if ($seed['status'] === 'delivered') {
                $order->delivered_at = now()->subDays($seed['days'] - 5);
            }

            $order->created_at = now()->subDays($seed['days']);
            $order->updated_at = now()->subDays(max(0, $seed['days'] - 1));
            $order->save();
        }
    }

    /**
     * @return Collection<int, Farm>
     */
    protected function farmsForUser(User $user): Collection
    {
        return Farm::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview, FarmStatus::Draft])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->get();
    }

    protected function ensureSeedFarm(User $user): Farm
    {
        return Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Valley Farm',
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Export demo field',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '12.00',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Cocoa', 'Catfish'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return list<string>
     */
    protected function productOptions(Collection $farms): array
    {
        $fromFarms = $farms
            ->flatMap(fn (Farm $farm) => $farm->crops ?? [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values();

        $defaults = ['Maize', 'Cocoa', 'Sesame', 'Cashew', 'Dried catfish', 'Palm oil'];

        return $fromFarms->merge($defaults)->unique()->take(10)->values()->all();
    }

    protected function nextReference(User $user): string
    {
        $count = ExportOrder::query()->where('user_id', $user->id)->count() + 1;

        return 'EXP-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function estimateValueUsd(string $product, float $tons): int
    {
        $name = Str::lower($product);
        $perTon = match (true) {
            Str::contains($name, ['cocoa']) => 2800,
            Str::contains($name, ['cashew']) => 1600,
            Str::contains($name, ['sesame']) => 1400,
            Str::contains($name, ['palm']) => 900,
            Str::contains($name, ['fish', 'catfish', 'tilapia']) => 2200,
            Str::contains($name, ['poultry', 'chicken', 'egg']) => 1800,
            default => 700,
        };

        return max(1000, (int) round($tons * $perTon));
    }

    protected function stageLabel(string $status): string
    {
        return match ($status) {
            'request_received' => 'Request Received',
            'quality_inspection' => 'Quality Inspection',
            'documentation' => 'Documentation',
            'customs_clearance' => 'Customs Clearance',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => Str::headline($status),
        };
    }

    protected function formatUsd(int $amount): string
    {
        return '$'.number_format($amount);
    }

    protected function formatUsdCompact(int $amount): string
    {
        if ($amount >= 1_000_000) {
            $millions = $amount / 1_000_000;

            return '$'.number_format($millions, $millions >= 10 ? 1 : 2).'M';
        }

        if ($amount >= 1_000) {
            return '$'.number_format($amount / 1_000, 1).'K';
        }

        return $this->formatUsd($amount);
    }
}
