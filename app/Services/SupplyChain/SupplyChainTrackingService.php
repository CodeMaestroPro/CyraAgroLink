<?php

declare(strict_types=1);

namespace App\Services\SupplyChain;

use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Services\Logistics\LogisticsNetworkService;
use Illuminate\Support\Collection;

/**
 * End-to-end supply chain tracking over live logistics shipments.
 */
class SupplyChainTrackingService
{
    private const PROGRESSION = [
        'booked' => 'picked_up',
        'picked_up' => 'in_transit',
        'in_transit' => 'in_warehouse',
        'in_warehouse' => 'delivered',
    ];

    /**
     * @var array<string, array{0: float, 1: float}>
     */
    private const CITY_COORDS = [
        'Abeokuta' => [7.1475, 3.3619],
        'Abuja' => [9.0765, 7.3986],
        'Ibadan' => [7.3775, 3.9470],
        'Kaduna' => [10.5105, 7.4165],
        'Kano' => [12.0022, 8.5920],
        'Lagos' => [6.5244, 3.3792],
        'Onitsha' => [6.1470, 6.7885],
        'Port Harcourt' => [4.8156, 7.0498],
    ];

    public function __construct(
        protected LogisticsNetworkService $logisticsNetworkService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrackingData(User $user, ?int $shipmentId = null): array
    {
        $this->logisticsNetworkService->ensureFleetCatalog();
        $this->warmUserShipments($user);

        $shipments = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->with('vehicle')
            ->latest('id')
            ->limit(40)
            ->get();

        $selected = $this->resolveShipment($shipments, $shipmentId);

        return [
            'shipment' => $selected
                ? $this->mapShipment($selected)
                : $this->emptyShipment(),
            'shipments' => $shipments->map(fn (LogisticsShipment $shipment) => [
                'id' => $shipment->id,
                'reference' => $shipment->referenceLabel(),
                'cargo' => $shipment->cargoLabel(),
                'route' => $shipment->routeLabel(),
                'status' => $shipment->displayStatus(),
                'status_key' => $shipment->status,
                'is_open' => $shipment->isOpen(),
                'selected' => $selected !== null && $selected->id === $shipment->id,
                'url' => route('supply-chain.index', ['shipment' => $shipment->id]),
            ])->all(),
            'notifications_count' => max(2, $shipments->filter(fn (LogisticsShipment $s) => $s->isOpen())->count() + 1),
        ];
    }

    public function advanceShipment(User $user, LogisticsShipment $shipment): LogisticsShipment
    {
        return $this->logisticsNetworkService->advanceShipment($user, $shipment);
    }

    public function cancelShipment(User $user, LogisticsShipment $shipment): LogisticsShipment
    {
        return $this->logisticsNetworkService->cancelShipment($user, $shipment);
    }

    protected function warmUserShipments(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (LogisticsShipment::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $vehicle = LogisticsVehicle::query()
            ->where('is_active', true)
            ->where('origin', 'Kano')
            ->first()
            ?? LogisticsVehicle::query()->where('is_active', true)->first();

        if ($vehicle === null) {
            return;
        }

        $reference = LogisticsShipment::query()->where('reference', 'SH12345')->exists()
            ? 'SH'.strtoupper(substr(md5((string) $user->id), 0, 3)).random_int(1000, 9999)
            : 'SH12345';

        LogisticsShipment::query()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'reference' => $reference,
            'cargo_name' => 'Maize',
            'cargo_tons' => 10,
            'origin' => 'Kano',
            'destination' => 'Ibadan',
            'price' => 0,
            'status' => 'in_transit',
            'booked_at' => now()->subDays(2)->setTime(9, 15),
        ]);
    }

    /**
     * @param  Collection<int, LogisticsShipment>  $shipments
     */
    protected function resolveShipment(Collection $shipments, ?int $shipmentId): ?LogisticsShipment
    {
        if ($shipmentId !== null) {
            $match = $shipments->firstWhere('id', $shipmentId);
            if ($match) {
                return $match;
            }
        }

        return $shipments->first(fn (LogisticsShipment $s) => $s->isOpen())
            ?? $shipments->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapShipment(LogisticsShipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'reference' => $shipment->referenceLabel(),
            'cargo' => $shipment->cargoLabel(),
            'origin' => $shipment->origin,
            'destination' => $shipment->destination,
            'route_label' => $shipment->routeLabel(),
            'status' => $shipment->displayStatus(),
            'status_key' => $shipment->status,
            'is_open' => $shipment->isOpen(),
            'can_advance' => array_key_exists($shipment->status, self::PROGRESSION),
            'can_cancel' => $shipment->status === 'booked',
            'steps' => $this->buildSteps($shipment),
            'route' => [
                'points' => $this->routePoints($shipment->origin, $shipment->destination),
            ],
            'logistics_url' => route('logistics.index', [
                'tab' => 'shipments',
                'shipment' => $shipment->id,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyShipment(): array
    {
        return [
            'id' => null,
            'reference' => 'No shipment yet',
            'cargo' => 'Book a truck in Logistics to start tracking.',
            'origin' => '',
            'destination' => '',
            'route_label' => '',
            'status' => 'None',
            'status_key' => 'none',
            'is_open' => false,
            'can_advance' => false,
            'can_cancel' => false,
            'steps' => [
                ['label' => 'Harvested', 'location' => '—', 'date' => '—', 'icon' => 'harvested', 'complete' => false],
                ['label' => 'Picked Up', 'location' => '—', 'date' => '—', 'icon' => 'picked_up', 'complete' => false],
                ['label' => 'In Transit', 'location' => '—', 'date' => '—', 'icon' => 'in_transit', 'complete' => false],
                ['label' => 'In Warehouse', 'location' => '—', 'date' => '—', 'icon' => 'warehouse', 'complete' => false],
                ['label' => 'Delivered', 'location' => '—', 'date' => '—', 'icon' => 'delivered', 'complete' => false],
            ],
            'route' => ['points' => []],
            'logistics_url' => route('logistics.index', ['tab' => 'trucks']),
        ];
    }

    /**
     * @return list<array{label: string, location: string, date: string, icon: string, complete: bool}>
     */
    protected function buildSteps(LogisticsShipment $shipment): array
    {
        if ($shipment->status === 'cancelled') {
            return [
                [
                    'label' => 'Cancelled',
                    'location' => $shipment->origin,
                    'date' => $shipment->updated_at?->format('j M') ?? '',
                    'icon' => 'delivered',
                    'complete' => true,
                ],
            ];
        }

        $order = ['booked', 'picked_up', 'in_transit', 'in_warehouse', 'delivered'];
        $currentIndex = array_search($shipment->status, $order, true);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $base = $shipment->booked_at ?? $shipment->created_at ?? now();
        $midpoint = $this->routeMidpointLabel($shipment->origin, $shipment->destination);

        $definitions = [
            [
                'label' => 'Harvested',
                'location' => $shipment->origin,
                'date' => $base->copy()->subDay()->format('j M'),
                'icon' => 'harvested',
                'complete' => $currentIndex >= 0,
            ],
            [
                'label' => 'Picked Up',
                'location' => $shipment->origin,
                'date' => $base->format('j M'),
                'icon' => 'picked_up',
                'complete' => $currentIndex >= 1,
            ],
            [
                'label' => 'In Transit',
                'location' => $midpoint,
                'date' => $base->copy()->addDay()->format('j M'),
                'icon' => 'in_transit',
                'complete' => $currentIndex >= 2,
            ],
            [
                'label' => 'In Warehouse',
                'location' => $shipment->destination,
                'date' => $base->copy()->addDays(2)->format('j M'),
                'icon' => 'warehouse',
                'complete' => $currentIndex >= 3,
            ],
            [
                'label' => 'Delivered',
                'location' => $shipment->destination,
                'date' => $shipment->delivered_at?->format('j M')
                    ?? $base->copy()->addDays(3)->format('j M'),
                'icon' => 'delivered',
                'complete' => $currentIndex >= 4,
            ],
        ];

        return $definitions;
    }

    /**
     * @return list<array{name: string, lat: float, lng: float}>
     */
    protected function routePoints(string $origin, string $destination): array
    {
        $points = [];

        foreach ($this->routeCityNames($origin, $destination) as $city) {
            $coords = self::CITY_COORDS[$city] ?? null;
            if ($coords === null) {
                continue;
            }

            $points[] = [
                'name' => $city,
                'lat' => $coords[0],
                'lng' => $coords[1],
            ];
        }

        if (count($points) < 2) {
            $originCoords = self::CITY_COORDS[$origin] ?? [9.0820, 8.6753];
            $destinationCoords = self::CITY_COORDS[$destination] ?? [
                $originCoords[0] - 2.5,
                $originCoords[1] - 2.0,
            ];

            return [
                ['name' => $origin, 'lat' => $originCoords[0], 'lng' => $originCoords[1]],
                ['name' => $destination, 'lat' => $destinationCoords[0], 'lng' => $destinationCoords[1]],
            ];
        }

        return $points;
    }

    /**
     * @return list<string>
     */
    protected function routeCityNames(string $origin, string $destination): array
    {
        $corridors = [
            'Kano|Ibadan' => ['Kano', 'Kaduna', 'Ibadan'],
            'Kano|Lagos' => ['Kano', 'Kaduna', 'Ibadan', 'Lagos'],
            'Kaduna|Onitsha' => ['Kaduna', 'Abuja', 'Onitsha'],
            'Lagos|Ibadan' => ['Lagos', 'Ibadan'],
            'Port Harcourt|Abuja' => ['Port Harcourt', 'Onitsha', 'Abuja'],
            'Ibadan|Abeokuta' => ['Ibadan', 'Abeokuta'],
        ];

        $key = $origin.'|'.$destination;
        if (isset($corridors[$key])) {
            return $corridors[$key];
        }

        $reverse = $destination.'|'.$origin;
        if (isset($corridors[$reverse])) {
            return array_reverse($corridors[$reverse]);
        }

        return [$origin, $destination];
    }

    protected function routeMidpointLabel(string $origin, string $destination): string
    {
        $cities = $this->routeCityNames($origin, $destination);

        if (count($cities) >= 3) {
            return $cities[(int) floor(count($cities) / 2)];
        }

        return 'En route';
    }
}
