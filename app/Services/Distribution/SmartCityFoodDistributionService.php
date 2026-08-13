<?php

declare(strict_types=1);

namespace App\Services\Distribution;

use App\Exceptions\BusinessLogicException;
use App\Models\SmartCityDelivery;
use App\Models\SmartCityFleetUnit;
use App\Models\SmartCityHub;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Smart City Food Distribution: hubs, fleet, deliveries, and route optimization.
 */
class SmartCityFoodDistributionService
{
    private const TABS = ['overview', 'deliveries', 'fleet'];

    private const PROGRESSION = [
        'scheduled' => 'dispatched',
        'dispatched' => 'in_transit',
        'in_transit' => 'delivered',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getDistributionData(User $user, string $tab = 'overview'): array
    {
        $this->warmNetwork();

        $tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $today = now()->toDateString();

        $deliveries = SmartCityDelivery::query()
            ->where('user_id', $user->id)
            ->with(['originHub', 'destinationHub', 'fleetUnit'])
            ->latest('id')
            ->limit(50)
            ->get();

        $todayDeliveries = $deliveries->filter(
            fn (SmartCityDelivery $d) => $d->delivery_date?->toDateString() === $today
        );

        $hubs = SmartCityHub::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $fleetUnits = SmartCityFleetUnit::query()
            ->where('is_active', true)
            ->with('hub')
            ->orderBy('name')
            ->get();

        $deliveredToday = $todayDeliveries->where('status', 'delivered');
        $onTimeCount = $deliveredToday->where('on_time', true)->count();
        $onTimeRate = $deliveredToday->count() > 0
            ? (int) round(($onTimeCount / $deliveredToday->count()) * 100)
            : 100;

        $inTransit = $todayDeliveries->whereIn('status', ['dispatched', 'in_transit'])->count();

        return [
            'tab' => $tab,
            'overview' => [
                ['label' => 'Deliveries Today', 'value' => (string) $todayDeliveries->count()],
                ['label' => 'In Transit', 'value' => (string) $inTransit],
                ['label' => 'Delivered', 'value' => (string) $deliveredToday->count()],
                ['label' => 'On Time', 'value' => $onTimeRate.'%'],
            ],
            'route_points' => $this->buildRoutePoints($hubs, $deliveries),
            'fleet' => [
                [
                    'label' => 'Available',
                    'value' => (string) $fleetUnits->where('status', 'available')->count(),
                    'icon' => 'available',
                ],
                [
                    'label' => 'In Transit',
                    'value' => (string) $fleetUnits->where('status', 'in_transit')->count(),
                    'icon' => 'transit',
                ],
                [
                    'label' => 'Maintenance',
                    'value' => (string) $fleetUnits->where('status', 'maintenance')->count(),
                    'icon' => 'maintenance',
                ],
            ],
            'hubs' => $hubs->map(fn (SmartCityHub $hub) => [
                'id' => $hub->id,
                'name' => $hub->name,
                'kind' => $hub->kind,
            ])->all(),
            'deliveries' => $deliveries->map(fn (SmartCityDelivery $delivery) => [
                'id' => $delivery->id,
                'reference' => $delivery->referenceLabel(),
                'cargo' => $delivery->cargo_name.' × '.$delivery->quantity,
                'route' => ($delivery->originHub?->name ?? '—').' → '.($delivery->destinationHub?->name ?? '—'),
                'status' => $delivery->displayStatus(),
                'status_key' => $delivery->status,
                'fleet' => $delivery->fleetUnit?->name ?? 'Unassigned',
                'route_order' => $delivery->route_order,
                'can_advance' => array_key_exists($delivery->status, self::PROGRESSION),
                'can_cancel' => $delivery->status === 'scheduled',
            ])->all(),
            'fleet_units' => $fleetUnits->map(fn (SmartCityFleetUnit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'status' => $unit->displayStatus(),
                'status_key' => $unit->status,
                'hub' => $unit->hub?->name ?? 'Depot',
            ])->all(),
            'open_deliveries_count' => $deliveries->filter(fn (SmartCityDelivery $d) => $d->isOpen())->count(),
            'available_fleet_count' => $fleetUnits->where('status', 'available')->count(),
            'notifications_count' => max(3, $deliveries->filter(fn (SmartCityDelivery $d) => $d->isOpen())->count()),
        ];
    }

    /**
     * Schedule a new city delivery.
     *
     * @param  array{cargo_name: string, quantity: int, origin_hub_id: int, destination_hub_id: int}  $data
     */
    public function createDelivery(User $user, array $data): SmartCityDelivery
    {
        $origin = SmartCityHub::query()->whereKey($data['origin_hub_id'])->where('is_active', true)->firstOrFail();
        $destination = SmartCityHub::query()->whereKey($data['destination_hub_id'])->where('is_active', true)->firstOrFail();

        if ((int) $origin->id === (int) $destination->id) {
            throw new BusinessLogicException('Origin and destination hubs must be different.');
        }

        return SmartCityDelivery::query()->create([
            'user_id' => $user->id,
            'origin_hub_id' => $origin->id,
            'destination_hub_id' => $destination->id,
            'reference' => $this->nextReference(),
            'cargo_name' => trim($data['cargo_name']),
            'quantity' => (int) $data['quantity'],
            'status' => 'scheduled',
            'delivery_date' => now()->toDateString(),
        ]);
    }

    /**
     * Optimize open deliveries: order by hub path and assign available fleet.
     *
     * @return array{optimized: int, assigned: int}
     */
    public function optimizeRoutes(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $open = SmartCityDelivery::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['scheduled', 'dispatched'])
                ->with('destinationHub')
                ->lockForUpdate()
                ->get();

            if ($open->isEmpty()) {
                throw new BusinessLogicException('No scheduled or dispatched deliveries to optimize.');
            }

            $sorted = $open->sortBy(fn (SmartCityDelivery $d) => $d->destinationHub?->sort_order ?? 999)->values();

            $availableUnits = SmartCityFleetUnit::query()
                ->where('is_active', true)
                ->where('status', 'available')
                ->lockForUpdate()
                ->orderBy('id')
                ->get()
                ->values();

            $assigned = 0;

            foreach ($sorted as $index => $delivery) {
                $unit = $delivery->fleet_unit_id
                    ? SmartCityFleetUnit::query()->whereKey($delivery->fleet_unit_id)->lockForUpdate()->first()
                    : $availableUnits->shift();

                $payload = [
                    'route_order' => $index + 1,
                    'status' => 'dispatched',
                    'dispatched_at' => $delivery->dispatched_at ?? now(),
                ];

                if ($unit) {
                    $payload['fleet_unit_id'] = $unit->id;
                    if ($unit->status !== 'in_transit') {
                        $unit->forceFill(['status' => 'in_transit'])->save();
                    }
                    $assigned++;
                }

                $delivery->forceFill($payload)->save();
            }

            return [
                'optimized' => $sorted->count(),
                'assigned' => $assigned,
            ];
        });
    }

    /**
     * Advance a delivery to the next status.
     */
    public function advanceDelivery(User $user, SmartCityDelivery $delivery): SmartCityDelivery
    {
        $this->assertOwned($user, $delivery);

        if (! array_key_exists($delivery->status, self::PROGRESSION)) {
            throw new BusinessLogicException('This delivery cannot be advanced further.');
        }

        return DB::transaction(function () use ($delivery): SmartCityDelivery {
            $locked = SmartCityDelivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $next = self::PROGRESSION[$locked->status];

            $payload = ['status' => $next];

            if ($next === 'delivered') {
                $payload['delivered_at'] = now();
                $payload['on_time'] = true;

                if ($locked->fleet_unit_id) {
                    SmartCityFleetUnit::query()
                        ->whereKey($locked->fleet_unit_id)
                        ->update(['status' => 'available']);
                }
            } elseif ($next === 'in_transit' && $locked->fleet_unit_id) {
                SmartCityFleetUnit::query()
                    ->whereKey($locked->fleet_unit_id)
                    ->update(['status' => 'in_transit']);
            }

            $locked->forceFill($payload)->save();

            return $locked->refresh();
        });
    }

    /**
     * Cancel a scheduled delivery.
     */
    public function cancelDelivery(User $user, SmartCityDelivery $delivery): SmartCityDelivery
    {
        $this->assertOwned($user, $delivery);

        if ($delivery->status !== 'scheduled') {
            throw new BusinessLogicException('Only scheduled deliveries can be cancelled.');
        }

        $delivery->forceFill([
            'status' => 'cancelled',
            'route_order' => null,
        ])->save();

        return $delivery->refresh();
    }

    /**
     * Toggle a fleet unit between available and maintenance.
     */
    public function toggleFleetMaintenance(SmartCityFleetUnit $unit): SmartCityFleetUnit
    {
        if ($unit->status === 'in_transit') {
            throw new BusinessLogicException('Units currently in transit cannot enter maintenance.');
        }

        $unit->forceFill([
            'status' => $unit->status === 'maintenance' ? 'available' : 'maintenance',
        ])->save();

        return $unit->refresh();
    }

    protected function warmNetwork(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (! SmartCityHub::query()->exists()) {
            foreach ($this->seedHubs() as $hub) {
                SmartCityHub::query()->create($hub);
            }
        }

        if (! SmartCityFleetUnit::query()->exists()) {
            $hubs = SmartCityHub::query()->orderBy('sort_order')->pluck('id')->all();
            $startHub = $hubs[0] ?? null;

            foreach ($this->seedFleet() as $index => $row) {
                SmartCityFleetUnit::query()->create([
                    ...$row,
                    'hub_id' => $startHub,
                ]);
            }
        }
    }

    /**
     * @return list<array{name: string, lat: float, lng: float, kind: string, sort_order: int, is_active: bool}>
     */
    protected function seedHubs(): array
    {
        return [
            ['name' => 'Warehouse Hub', 'lat' => 6.5244, 'lng' => 3.3792, 'kind' => 'start', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Transit', 'lat' => 6.5350, 'lng' => 3.3500, 'kind' => 'waypoint', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'City Depot', 'lat' => 6.5480, 'lng' => 3.3200, 'kind' => 'waypoint', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'Distribution', 'lat' => 6.5600, 'lng' => 3.2900, 'kind' => 'end', 'sort_order' => 4, 'is_active' => true],
        ];
    }

    /**
     * @return list<array{name: string, status: string, is_active: bool}>
     */
    protected function seedFleet(): array
    {
        $units = [];

        for ($i = 1; $i <= 25; $i++) {
            $units[] = ['name' => 'Van '.$i, 'status' => 'available', 'is_active' => true];
        }
        for ($i = 1; $i <= 35; $i++) {
            $units[] = ['name' => 'Bike '.$i, 'status' => 'in_transit', 'is_active' => true];
        }
        for ($i = 1; $i <= 5; $i++) {
            $units[] = ['name' => 'Service Unit '.$i, 'status' => 'maintenance', 'is_active' => true];
        }

        return $units;
    }

    /**
     * @param  Collection<int, SmartCityHub>  $hubs
     * @param  Collection<int, SmartCityDelivery>  $deliveries
     * @return list<array{lat: float, lng: float, label: string, kind: string}>
     */
    protected function buildRoutePoints(Collection $hubs, Collection $deliveries): array
    {
        $open = $deliveries
            ->filter(fn (SmartCityDelivery $d) => $d->isOpen() && $d->route_order !== null)
            ->sortBy('route_order')
            ->values();

        if ($open->isNotEmpty()) {
            $points = [];
            $start = $hubs->firstWhere('kind', 'start') ?? $hubs->first();
            if ($start) {
                $points[] = $start->toMapPoint();
            }

            foreach ($open as $delivery) {
                if ($delivery->destinationHub) {
                    $point = $delivery->destinationHub->toMapPoint();
                    $point['label'] = $delivery->destinationHub->name;
                    $point['kind'] = 'waypoint';
                    $points[] = $point;
                }
            }

            $end = $hubs->firstWhere('kind', 'end');
            if ($end && ! collect($points)->contains(fn ($p) => $p['label'] === $end->name)) {
                $points[] = $end->toMapPoint();
            }

            // Ensure polyline has at least 2 points.
            if (count($points) >= 2) {
                $points[0]['kind'] = 'start';
                $points[count($points) - 1]['kind'] = 'end';

                return $points;
            }
        }

        return $hubs->map(fn (SmartCityHub $hub) => $hub->toMapPoint())->values()->all();
    }

    protected function nextReference(): string
    {
        do {
            $reference = 'CD'.Str::upper(Str::random(2)).random_int(1000, 9999);
        } while (SmartCityDelivery::query()->where('reference', $reference)->exists());

        return $reference;
    }

    protected function assertOwned(User $user, SmartCityDelivery $delivery): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $delivery->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own deliveries.', 'DELIVERY_FORBIDDEN', 403);
        }
    }
}
