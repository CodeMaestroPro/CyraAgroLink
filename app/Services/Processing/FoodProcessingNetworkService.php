<?php

declare(strict_types=1);

namespace App\Services\Processing;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Farm;
use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\ProcessingFactory;
use App\Models\ProcessingRequest;
use App\Models\User;
use App\Services\Logistics\LogisticsNetworkService;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Live food processing network: factories, service catalog, and job requests.
 *
 * Each request books a logistics shipment so farm produce is delivered to the factory
 * before processing can start.
 */
class FoodProcessingNetworkService
{
    /**
     * @var list<string>
     */
    public const SERVICES = [
        'milling',
        'packaging',
        'drying',
        'cold_storage',
        'juicing',
        'other',
    ];

    /**
     * @var array<string, string>
     */
    protected array $serviceLabels = [
        'milling' => 'Milling',
        'packaging' => 'Packaging',
        'drying' => 'Drying',
        'cold_storage' => 'Cold Storage',
        'juicing' => 'Juicing',
        'other' => 'Others',
    ];

    /**
     * queued → in_progress → completed
     *
     * @var array<string, string>
     */
    protected array $progression = [
        'queued' => 'in_progress',
        'in_progress' => 'completed',
    ];

    public function __construct(
        protected DigitalWalletService $walletService,
        protected LogisticsNetworkService $logisticsNetworkService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getNetworkData(User $user): array
    {
        $this->ensureFactoryCatalog();

        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty() && \App\Support\DemoSeeding::allowed()) {
            $this->ensureSeedFarm($user);
            $farms = $this->farmsForUser($user);
        }

        $this->ensureStarterRequests($user, $farms);

        $factories = ProcessingFactory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $requests = ProcessingRequest::query()
            ->where('user_id', $user->id)
            ->with(['factory', 'logisticsShipment', 'farm'])
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            'kpis' => $this->kpis($factories, $requests),
            'services' => $this->popularServices(),
            'requests' => $requests->map(fn (ProcessingRequest $request) => $this->presentRequest($request))->all(),
            'factories' => $factories->take(8)->map(fn (ProcessingFactory $factory) => [
                'id' => $factory->id,
                'name' => $factory->name,
                'state' => $factory->state ?: 'Nigeria',
                'utilization' => $factory->utilization_percent.'%',
                'services' => collect($factory->services ?? [])
                    ->map(fn (string $key) => $this->serviceLabels[$key] ?? Str::headline($key))
                    ->implode(', '),
            ])->values()->all(),
            'farms' => $farms->map(fn (Farm $farm) => [
                'id' => $farm->id,
                'name' => $farm->name ?: 'Untitled farm',
            ])->values()->all(),
            'factory_options' => $factories->map(fn (ProcessingFactory $factory) => [
                'id' => $factory->id,
                'name' => $factory->name.' ('.$factory->state.')',
            ])->values()->all(),
            'service_options' => collect(self::SERVICES)->map(fn (string $key) => [
                'value' => $key,
                'label' => $this->serviceLabels[$key],
            ])->values()->all(),
            'product_options' => $this->productOptions($farms),
            'actions' => [
                'store_url' => route('processing.requests.store'),
                'equipment_url' => route('equipment.marketplace'),
                'warehouse_url' => route('warehouse.index'),
                'logistics_url' => route('logistics.index', ['tab' => 'shipments']),
            ],
            'wallet_balance' => $this->walletService->getBalance($user),
            'notifications_count' => 3,
        ];
    }

    /**
     * @param  array{service: string, product: string, quantity_tons: float|int|string, factory_id?: int|null, farm_id?: int|null}  $data
     */
    public function createRequest(User $user, array $data): ProcessingRequest
    {
        $service = (string) $data['service'];
        if (! in_array($service, self::SERVICES, true)) {
            throw new BusinessLogicException('Select a valid processing service.');
        }

        $quantity = round((float) $data['quantity_tons'], 2);
        if ($quantity < 0.5) {
            throw new BusinessLogicException('Quantity must be at least 0.5 tons.');
        }

        $product = trim((string) $data['product']);
        if ($product === '') {
            throw new BusinessLogicException('Product is required.');
        }

        $farms = $this->farmsForUser($user);
        $farmId = isset($data['farm_id']) ? (int) $data['farm_id'] : null;
        $farm = $farmId
            ? $farms->firstWhere('id', $farmId)
            : $farms->first();

        $this->ensureFactoryCatalog();
        $factoryId = isset($data['factory_id']) ? (int) $data['factory_id'] : null;
        $factory = $factoryId
            ? ProcessingFactory::query()->where('is_active', true)->whereKey($factoryId)->first()
            : ProcessingFactory::query()->where('is_active', true)->orderBy('utilization_percent')->first();

        if ($factory === null) {
            throw new BusinessLogicException('No processing factory is available right now.');
        }

        $fee = $this->estimateFee($service, $quantity);
        $origin = $this->farmOriginLabel($farm);
        $destination = $this->factoryDestinationLabel($factory);

        return DB::transaction(function () use ($user, $farm, $factory, $service, $product, $quantity, $fee, $origin, $destination): ProcessingRequest {
            $this->walletService->ensureWallet($user);

            $shipment = $this->logisticsNetworkService->bookFactoryDelivery(
                $user,
                $product.' for processing',
                $quantity,
                $origin,
                $destination
            );

            $request = ProcessingRequest::query()->create([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'factory_id' => $factory->id,
                'logistics_shipment_id' => $shipment->id,
                'reference' => $this->nextReference($user),
                'service' => $service,
                'product' => Str::limit($product, 120, ''),
                'quantity_tons' => $quantity,
                'status' => 'queued',
                'fee_ngn' => $fee,
                'meta' => [
                    'source' => 'network',
                    'logistics_reference' => $shipment->reference,
                    'delivery_route' => $origin.' → '.$destination,
                ],
            ]);

            if ($fee > 0) {
                $this->walletService->payForProcessing(
                    $user,
                    $fee,
                    $request,
                    $request->reference.' '.$this->serviceLabels[$service].' fee'
                );
            }

            $factory->forceFill([
                'active_jobs' => $factory->active_jobs + 1,
                'utilization_percent' => min(98, $factory->utilization_percent + 1),
            ])->save();

            return $request->load(['factory', 'logisticsShipment', 'farm']);
        });
    }

    /**
     * Advance a processing request: queued → in progress → completed.
     * Starting processing requires the linked logistics delivery to be completed.
     */
    public function advanceRequest(User $user, ProcessingRequest $request): ProcessingRequest
    {
        if ($request->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to update this request.', 'PROCESSING_FORBIDDEN', 403);
        }

        if (! array_key_exists($request->status, $this->progression)) {
            throw new BusinessLogicException('This processing request cannot be advanced further.');
        }

        $next = $this->progression[$request->status];

        return DB::transaction(function () use ($request, $next): ProcessingRequest {
            /** @var ProcessingRequest $locked */
            $locked = ProcessingRequest::query()
                ->with('logisticsShipment')
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! array_key_exists($locked->status, $this->progression)) {
                throw new BusinessLogicException('This processing request cannot be advanced further.');
            }

            if ($next === 'in_progress' && ! $locked->produceDelivered()) {
                throw new BusinessLogicException(
                    'Deliver produce to the factory via logistics before starting processing.'
                );
            }

            $payload = ['status' => $next];
            if ($next === 'in_progress') {
                $payload['started_at'] = now();
            }
            if ($next === 'completed') {
                $payload['completed_at'] = now();
            }

            $locked->forceFill($payload)->save();

            if ($next === 'completed' && $locked->factory_id) {
                $factory = ProcessingFactory::query()->whereKey($locked->factory_id)->lockForUpdate()->first();
                if ($factory) {
                    $factory->forceFill([
                        'active_jobs' => max(0, $factory->active_jobs - 1),
                        'completed_jobs' => $factory->completed_jobs + 1,
                        'utilization_percent' => max(35, $factory->utilization_percent - 1),
                    ])->save();
                }
            }

            return $locked->refresh()->load(['factory', 'logisticsShipment', 'farm']);
        });
    }

    /**
     * Advance the logistics leg that delivers produce for a processing request.
     */
    public function advanceDelivery(User $user, ProcessingRequest $request): LogisticsShipment
    {
        if ($request->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to update this request.', 'PROCESSING_FORBIDDEN', 403);
        }

        $shipment = $request->logisticsShipment;
        if ($shipment === null) {
            throw new BusinessLogicException('This processing request has no logistics delivery booked.');
        }

        return $this->logisticsNetworkService->advanceShipment($user, $shipment);
    }

    /**
     * @param  Collection<int, ProcessingFactory>  $factories
     * @param  Collection<int, ProcessingRequest>  $requests
     * @return list<array{label: string, value: string, tone: string}>
     */
    protected function kpis(Collection $factories, Collection $requests): array
    {
        $activeNetwork = (int) $factories->sum('active_jobs');
        $userActive = $requests->filter(fn (ProcessingRequest $item) => $item->isOpen())->count();
        $active = max($activeNetwork, $userActive);
        $avgUtil = $factories->isEmpty()
            ? 0
            : (int) round($factories->avg('utilization_percent'));
        $completed = (int) $factories->sum('completed_jobs');

        return [
            ['label' => 'Total Factories', 'value' => (string) $factories->count(), 'tone' => 'ink'],
            ['label' => 'Active Requests', 'value' => (string) $active, 'tone' => 'ink'],
            ['label' => 'Processing Capacity', 'value' => $avgUtil.'%', 'tone' => 'green'],
            ['label' => 'Jobs Completed', 'value' => number_format($completed), 'tone' => 'ink'],
        ];
    }

    /**
     * @return list<array{label: string, icon: string, tone: string, value: string}>
     */
    protected function popularServices(): array
    {
        return [
            ['label' => 'Milling', 'icon' => 'milling', 'tone' => 'green', 'value' => 'milling'],
            ['label' => 'Packaging', 'icon' => 'packaging', 'tone' => 'soil', 'value' => 'packaging'],
            ['label' => 'Drying', 'icon' => 'drying', 'tone' => 'green', 'value' => 'drying'],
            ['label' => 'Cold Storage', 'icon' => 'cold', 'tone' => 'blue', 'value' => 'cold_storage'],
            ['label' => 'Juicing', 'icon' => 'juicing', 'tone' => 'amber', 'value' => 'juicing'],
            ['label' => 'Others', 'icon' => 'others', 'tone' => 'green', 'value' => 'other'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentRequest(ProcessingRequest $request): array
    {
        $shipment = $request->logisticsShipment;
        $delivered = $request->produceDelivered();
        $next = array_key_exists($request->status, $this->progression)
            ? $this->progression[$request->status]
            : null;
        $canAdvanceProcessing = $next !== null
            && ($next !== 'in_progress' || $delivered);

        [$statusTone, $icon, $statusLabel] = match ($request->status) {
            'completed' => ['done', 'check', 'Completed'],
            'in_progress' => ['progress', 'gear', 'In Progress'],
            'cancelled' => ['queued', 'box', 'Cancelled'],
            default => ['queued', 'box', $delivered ? 'At factory' : 'Awaiting delivery'],
        };

        $tons = number_format((float) $request->quantity_tons, (float) $request->quantity_tons == floor((float) $request->quantity_tons) ? 0 : 1);
        $route = $shipment
            ? $shipment->routeLabel()
            : (($request->meta['delivery_route'] ?? null) ?: 'Farm → Factory');

        $logisticsCanAdvance = $shipment !== null
            && $shipment->isOpen()
            && array_key_exists($shipment->status, [
                'booked' => true,
                'picked_up' => true,
                'in_transit' => true,
                'in_warehouse' => true,
            ]);

        return [
            'id' => $request->id,
            'reference' => $request->reference,
            'title' => $this->requestTitle($request),
            'detail' => $tons.' Tons, '.$statusLabel,
            'status' => $request->status === 'completed' ? 'Completed' : null,
            'status_tone' => $statusTone,
            'status_label' => $statusLabel,
            'icon' => $icon,
            'factory' => $request->factory?->name,
            'fee' => '₦'.number_format($request->fee_ngn),
            'can_advance' => $canAdvanceProcessing,
            'advance_label' => $next === 'in_progress' ? 'Start processing' : ($next === 'completed' ? 'Mark completed' : 'Advance'),
            'advance_url' => route('processing.requests.advance', $request),
            'awaiting_delivery' => $request->status === 'queued' && ! $delivered,
            'logistics' => $shipment ? [
                'reference' => $shipment->referenceLabel(),
                'status' => $shipment->displayStatus(),
                'route' => $route,
                'delivered' => $delivered,
                'can_advance' => $logisticsCanAdvance,
                'advance_label' => $this->logisticsAdvanceLabel($shipment->status),
                'advance_url' => route('processing.requests.deliver', $request),
                'track_url' => route('logistics.index', ['tab' => 'shipments', 'shipment' => $shipment->id]),
            ] : null,
        ];
    }

    protected function logisticsAdvanceLabel(string $status): string
    {
        return match ($status) {
            'booked' => 'Mark picked up',
            'picked_up' => 'Mark in transit',
            'in_transit' => 'Arrive at hub',
            'in_warehouse' => 'Deliver to factory',
            default => 'Advance delivery',
        };
    }

    protected function farmOriginLabel(?Farm $farm): string
    {
        if ($farm === null) {
            return 'Farm gate';
        }

        $parts = array_filter([
            $farm->name ?: 'Farm',
            $farm->local_government,
            $farm->state,
        ]);

        return Str::limit(implode(', ', $parts) ?: 'Farm gate', 80, '');
    }

    protected function factoryDestinationLabel(ProcessingFactory $factory): string
    {
        return Str::limit(
            trim($factory->name.($factory->state ? ', '.$factory->state : '')),
            80,
            ''
        );
    }

    protected function requestTitle(ProcessingRequest $request): string
    {
        $service = $this->serviceLabels[$request->service] ?? Str::headline($request->service);
        $product = $request->product;

        if (Str::contains(Str::lower($product), Str::lower($service))) {
            return $product;
        }

        return trim($product.' '.$service);
    }

    protected function ensureFactoryCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (ProcessingFactory::query()->exists()) {
            return;
        }

        $states = ['Oyo', 'Lagos', 'Ogun', 'Kaduna', 'Kano', 'Benue', 'Rivers', 'Enugu'];
        $serviceSets = [
            ['milling', 'packaging', 'drying'],
            ['cold_storage', 'packaging'],
            ['juicing', 'packaging', 'other'],
            ['milling', 'drying'],
            ['cold_storage', 'other'],
            ['packaging', 'drying', 'other'],
        ];

        $names = [
            'AgroMill Hub', 'GreenCrush Plant', 'Valley Dryers', 'FreshPack Works', 'PalmPress Co',
            'Cassava Line', 'ColdChain Depot', 'Sunrise Juicers', 'Sahel Grain Mill', 'Delta Oil Works',
            'Harvest Pack', 'RootProcess NG', 'Savanna Dryers', 'Metro Cold Store', 'Cocoa Finishers',
            'Fish Smoke House', 'Poultry Cut Plant', 'Rice Polish Mill', 'Nut Roast Co', 'Feed Blend Yard',
        ];

        for ($i = 0; $i < 42; $i++) {
            // First 28 factories carry one active job each (demo "28" active requests).
            $active = $i < 28 ? 1 : 0;
            $completed = (int) floor(1245 / 42) + ($i < (1245 % 42) ? 1 : 0);
            // Spread utilization around 78% (74–82).
            $util = 78 + (($i % 9) - 4);

            ProcessingFactory::query()->create([
                'name' => ($names[$i % count($names)]).' '.chr(65 + (int) floor($i / count($names))),
                'state' => $states[$i % count($states)],
                'services' => $serviceSets[$i % count($serviceSets)],
                'capacity_tons_per_day' => 15 + ($i % 40),
                'utilization_percent' => $util,
                'active_jobs' => $active,
                'completed_jobs' => $completed,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  Collection<int, Farm>  $farms
     */
    protected function ensureStarterRequests(User $user, Collection $farms): void
    {
        if (! \App\Support\DemoSeeding::allowed() || $farms->isEmpty()) {
            return;
        }

        if (ProcessingRequest::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $this->logisticsNetworkService->ensureFleetCatalog();

        $farm = $farms->first();
        $factories = ProcessingFactory::query()->where('is_active', true)->orderBy('id')->limit(3)->get();
        $vehicle = LogisticsVehicle::query()->where('is_active', true)->orderBy('capacity_tons')->first();
        if ($factories->isEmpty() || $vehicle === null) {
            return;
        }

        $seeds = [
            ['service' => 'milling', 'product' => 'Maize', 'tons' => 10, 'status' => 'in_progress', 'shipment' => 'delivered', 'days' => 2],
            ['service' => 'other', 'product' => 'Cassava Processing', 'tons' => 5, 'status' => 'completed', 'shipment' => 'delivered', 'days' => 8],
            ['service' => 'other', 'product' => 'Palm Oil Processing', 'tons' => 12, 'status' => 'queued', 'shipment' => 'booked', 'days' => 1],
        ];

        foreach ($seeds as $index => $seed) {
            $factory = $factories[$index % $factories->count()];
            $origin = $this->farmOriginLabel($farm);
            $destination = $this->factoryDestinationLabel($factory);

            $shipment = new LogisticsShipment([
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'reference' => 'PRC'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).random_int(10, 99),
                'cargo_name' => $seed['product'].' for processing',
                'cargo_tons' => (int) ceil((float) $seed['tons']),
                'origin' => $origin,
                'destination' => $destination,
                'price' => 0,
                'status' => $seed['shipment'],
                'booked_at' => now()->subDays($seed['days'] + 1),
                'delivered_at' => $seed['shipment'] === 'delivered' ? now()->subDays($seed['days']) : null,
            ]);
            $shipment->created_at = now()->subDays($seed['days'] + 1);
            $shipment->updated_at = now()->subDays($seed['days']);
            $shipment->save();

            $request = new ProcessingRequest([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'factory_id' => $factory->id,
                'logistics_shipment_id' => $shipment->id,
                'reference' => 'PRC-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'service' => $seed['service'],
                'product' => $seed['product'],
                'quantity_tons' => $seed['tons'],
                'status' => $seed['status'],
                'fee_ngn' => $this->estimateFee($seed['service'], (float) $seed['tons']),
                'meta' => [
                    'seeded' => true,
                    'logistics_reference' => $shipment->reference,
                    'delivery_route' => $origin.' → '.$destination,
                ],
            ]);

            if ($seed['status'] === 'in_progress') {
                $request->started_at = now()->subDays(1);
            }
            if ($seed['status'] === 'completed') {
                $request->started_at = now()->subDays($seed['days'] + 1);
                $request->completed_at = now()->subDays($seed['days'] - 1);
            }

            $request->created_at = now()->subDays($seed['days']);
            $request->updated_at = now()->subDays(max(0, $seed['days'] - 1));
            $request->save();
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
            'address' => 'Processing demo field',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '6.00',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Cassava', 'Palm oil'],
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

        return $fromFarms
            ->merge(['Maize', 'Cassava', 'Palm oil', 'Rice', 'Tomato', 'Catfish'])
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    protected function nextReference(User $user): string
    {
        $count = ProcessingRequest::query()->where('user_id', $user->id)->count() + 1;

        return 'PRC-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function estimateFee(string $service, float $tons): int
    {
        $perTon = match ($service) {
            'milling' => 12000,
            'packaging' => 8000,
            'drying' => 9000,
            'cold_storage' => 15000,
            'juicing' => 18000,
            default => 10000,
        };

        return max(5000, (int) round($tons * $perTon));
    }
}
