<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use App\Exceptions\BusinessLogicException;
use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Logistics fleet catalog, booking, tracking, and wallet payment.
 */
class LogisticsNetworkService
{
    private const PROGRESSION = [
        'booked' => 'picked_up',
        'picked_up' => 'in_transit',
        'in_transit' => 'in_warehouse',
        'in_warehouse' => 'delivered',
    ];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getNetworkData(User $user, string $tab = 'trucks', ?int $shipmentId = null): array
    {
        $this->warmFleet();

        $tab = in_array($tab, ['trucks', 'shipments'], true) ? $tab : 'trucks';

        $vehicles = LogisticsVehicle::query()
            ->where('is_active', true)
            ->orderBy('capacity_tons')
            ->get();

        $shipments = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->with('vehicle')
            ->latest('id')
            ->limit(40)
            ->get();

        $trackingShipment = $this->resolveTrackingShipment($shipments, $shipmentId);
        $walletBalance = $this->walletService->getBalance($user);

        return [
            'tab' => $tab,
            'vehicles' => $vehicles->map(fn (LogisticsVehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'route' => $vehicle->routeLabel(),
                'price' => $vehicle->formattedPrice(),
                'price_raw' => $vehicle->price,
                'capacity_tons' => $vehicle->capacity_tons,
                'status' => ucfirst($vehicle->status),
                'available' => $vehicle->isAvailable(),
                'image' => $vehicle->imageUrl(),
            ])->all(),
            'shipments' => $shipments->map(fn (LogisticsShipment $shipment) => [
                'id' => $shipment->id,
                'name' => $shipment->referenceLabel(),
                'route' => $shipment->routeLabel(),
                'price' => $shipment->cargoLabel(),
                'status' => $shipment->displayStatus(),
                'status_key' => $shipment->status,
                'is_open' => $shipment->isOpen(),
                'image' => $shipment->vehicle?->imageUrl() ?? asset('images/logistics/truck-10t.jpg'),
                'can_advance' => array_key_exists($shipment->status, self::PROGRESSION),
            ])->all(),
            'tracking' => $trackingShipment
                ? $this->buildTracking($trackingShipment)
                : [
                    'shipment_id' => null,
                    'reference' => 'No active shipment',
                    'cargo' => 'Book a truck to start tracking.',
                    'steps' => $this->emptySteps(),
                    'can_advance' => false,
                    'can_cancel' => false,
                ],
            'wallet_balance' => $walletBalance,
            'shipments_count' => $shipments->count(),
            'open_shipments_count' => $shipments->filter(fn (LogisticsShipment $s) => $s->isOpen())->count(),
            'notifications_count' => max(2, $shipments->filter(fn (LogisticsShipment $s) => $s->isOpen())->count() + 1),
        ];
    }

    /**
     * Book a vehicle and pay from the user's wallet.
     *
     * @param  array{cargo_name: string, cargo_tons: int, origin?: string, destination?: string}  $data
     */
    public function bookVehicle(User $user, LogisticsVehicle $vehicle, array $data): LogisticsShipment
    {
        if (! $vehicle->isAvailable()) {
            throw new BusinessLogicException('This vehicle is not available for booking.');
        }

        $tons = (int) $data['cargo_tons'];

        if ($tons > $vehicle->capacity_tons) {
            throw new BusinessLogicException(
                "This truck only carries up to {$vehicle->capacity_tons} tons."
            );
        }

        return DB::transaction(function () use ($user, $vehicle, $data, $tons): LogisticsShipment {
            $locked = LogisticsVehicle::query()->whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isAvailable()) {
                throw new BusinessLogicException('This vehicle is not available for booking.');
            }

            $shipment = LogisticsShipment::query()->create([
                'user_id' => $user->id,
                'vehicle_id' => $locked->id,
                'reference' => $this->nextReference(),
                'cargo_name' => trim($data['cargo_name']),
                'cargo_tons' => $tons,
                'origin' => trim((string) ($data['origin'] ?? $locked->origin)),
                'destination' => trim((string) ($data['destination'] ?? $locked->destination)),
                'price' => $locked->price,
                'status' => 'booked',
                'booked_at' => now(),
            ]);

            $this->walletService->payForPurchase(
                $user,
                (int) $locked->price,
                $shipment,
                'Logistics '.$shipment->referenceLabel()
            );

            return $shipment->fresh('vehicle') ?? $shipment;
        });
    }

    /**
     * Book the smallest available truck for a farm → factory produce delivery.
     */
    public function bookFactoryDelivery(
        User $user,
        string $cargoName,
        float|int $tons,
        string $origin,
        string $destination
    ): LogisticsShipment {
        $this->warmFleet();

        $neededTons = max(1, (int) ceil((float) $tons));

        $vehicle = LogisticsVehicle::query()
            ->where('is_active', true)
            ->where('status', 'available')
            ->where('capacity_tons', '>=', $neededTons)
            ->orderBy('capacity_tons')
            ->orderBy('price')
            ->first();

        if ($vehicle === null) {
            // Fall back to the largest available truck when cargo exceeds listed capacities.
            $vehicle = LogisticsVehicle::query()
                ->where('is_active', true)
                ->where('status', 'available')
                ->orderByDesc('capacity_tons')
                ->first();
        }

        if ($vehicle === null) {
            throw new BusinessLogicException('No logistics truck is available to deliver produce to the factory.');
        }

        $cargoTons = min($neededTons, (int) $vehicle->capacity_tons);

        return $this->bookVehicle($user, $vehicle, [
            'cargo_name' => $cargoName,
            'cargo_tons' => $cargoTons,
            'origin' => Str::limit($origin, 80, ''),
            'destination' => Str::limit($destination, 80, ''),
        ]);
    }

    /**
     * Advance an open shipment to the next logistics milestone.
     */
    public function advanceShipment(User $user, LogisticsShipment $shipment): LogisticsShipment
    {
        $this->assertOwned($user, $shipment);

        if (! array_key_exists($shipment->status, self::PROGRESSION)) {
            throw new BusinessLogicException('This shipment cannot be advanced further.');
        }

        $next = self::PROGRESSION[$shipment->status];

        $shipment->forceFill([
            'status' => $next,
            'delivered_at' => $next === 'delivered' ? now() : $shipment->delivered_at,
        ])->save();

        return $shipment->refresh();
    }

    /**
     * Cancel a booked shipment and refund the wallet.
     */
    public function cancelShipment(User $user, LogisticsShipment $shipment): LogisticsShipment
    {
        $this->assertOwned($user, $shipment);

        if ($shipment->status !== 'booked') {
            throw new BusinessLogicException('Only newly booked shipments can be cancelled for a refund.');
        }

        return DB::transaction(function () use ($user, $shipment): LogisticsShipment {
            $locked = LogisticsShipment::query()->whereKey($shipment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'booked') {
                throw new BusinessLogicException('Only newly booked shipments can be cancelled for a refund.');
            }

            if ((int) $locked->price > 0) {
                $this->walletService->refundPurchase(
                    $user,
                    (int) $locked->price,
                    $locked,
                    'Refund '.$locked->referenceLabel()
                );
            }

            $locked->forceFill(['status' => 'cancelled'])->save();

            return $locked->refresh();
        });
    }

    /**
     * Ensure the shared vehicle catalog exists (used by logistics and supply-chain).
     */
    public function ensureFleetCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        $this->warmFleet();
    }

    protected function warmFleet(): void
    {
        if (LogisticsVehicle::query()->exists()) {
            return;
        }

        foreach ($this->seedVehicles() as $row) {
            LogisticsVehicle::query()->create($row);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function seedVehicles(): array
    {
        return [
            [
                'name' => '10 Ton Truck',
                'capacity_tons' => 10,
                'origin' => 'Lagos',
                'destination' => 'Ibadan',
                'price' => 150000,
                'image_path' => 'images/logistics/truck-10t.jpg',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => '20 Ton Truck',
                'capacity_tons' => 20,
                'origin' => 'Kano',
                'destination' => 'Lagos',
                'price' => 280000,
                'image_path' => 'images/logistics/truck-20t.jpg',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => '15 Ton Truck',
                'capacity_tons' => 15,
                'origin' => 'Port Harcourt',
                'destination' => 'Abuja',
                'price' => 200000,
                'image_path' => 'images/logistics/truck-15t.jpg',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => '8 Ton Truck',
                'capacity_tons' => 8,
                'origin' => 'Ibadan',
                'destination' => 'Abeokuta',
                'price' => 95000,
                'image_path' => 'images/logistics/truck-10t.jpg',
                'status' => 'available',
                'is_active' => true,
            ],
            [
                'name' => '25 Ton Truck',
                'capacity_tons' => 25,
                'origin' => 'Kaduna',
                'destination' => 'Onitsha',
                'price' => 340000,
                'image_path' => 'images/logistics/truck-20t.jpg',
                'status' => 'available',
                'is_active' => true,
            ],
        ];
    }

    /**
     * @param  Collection<int, LogisticsShipment>  $shipments
     */
    protected function resolveTrackingShipment(Collection $shipments, ?int $shipmentId): ?LogisticsShipment
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
    protected function buildTracking(LogisticsShipment $shipment): array
    {
        $order = ['booked', 'picked_up', 'in_transit', 'in_warehouse', 'delivered'];
        $currentIndex = array_search($shipment->status, $order, true);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $labels = [
            'booked' => ['Booked', $shipment->origin, $shipment->booked_at?->format('g:i A') ?: 'Pending'],
            'picked_up' => ['Picked Up', $shipment->origin, $currentIndex >= 1 ? 'Completed' : 'Pending'],
            'in_transit' => ['In Transit', 'On the way', $currentIndex >= 2 ? 'En route' : 'Pending'],
            'in_warehouse' => ['In Warehouse', $shipment->destination, $currentIndex >= 3 ? 'At hub' : 'Pending'],
            'delivered' => ['Delivered', $shipment->destination, $shipment->status === 'delivered' ? 'Completed' : 'Pending'],
        ];

        $steps = [];
        foreach ($order as $index => $key) {
            // Skip "booked" in the public timeline labels that tests expect starting at Picked Up —
            // keep booked as first step but map display: show Picked Up as first visible milestone.
            if ($key === 'booked') {
                continue;
            }

            $meta = $labels[$key];
            // complete if we've reached this status (picked_up index in order is 1)
            $complete = $currentIndex >= $index;

            // For booked-only shipments, show first step incomplete
            if ($shipment->status === 'booked' && $key === 'picked_up') {
                $complete = false;
            }

            $steps[] = [
                'label' => $meta[0],
                'detail' => $meta[1],
                'time' => $meta[2],
                'complete' => $complete,
            ];
        }

        if ($shipment->status === 'cancelled') {
            $steps = [
                [
                    'label' => 'Cancelled',
                    'detail' => 'Booking cancelled and refunded',
                    'time' => $shipment->updated_at?->format('g:i A') ?: '',
                    'complete' => true,
                ],
            ];
        }

        return [
            'shipment_id' => $shipment->id,
            'reference' => $shipment->referenceLabel(),
            'cargo' => $shipment->cargoLabel(),
            'steps' => $steps,
            'can_advance' => array_key_exists($shipment->status, self::PROGRESSION),
            'can_cancel' => $shipment->status === 'booked',
            'status' => $shipment->displayStatus(),
        ];
    }

    /**
     * @return list<array{label: string, detail: string, time: string, complete: bool}>
     */
    protected function emptySteps(): array
    {
        return [
            ['label' => 'Picked Up', 'detail' => '—', 'time' => '—', 'complete' => false],
            ['label' => 'In Transit', 'detail' => '—', 'time' => '—', 'complete' => false],
            ['label' => 'In Warehouse', 'detail' => '—', 'time' => '—', 'complete' => false],
            ['label' => 'Delivered', 'detail' => '—', 'time' => '—', 'complete' => false],
        ];
    }

    protected function nextReference(): string
    {
        do {
            $reference = 'SH'.Str::upper(Str::random(3)).random_int(1000, 9999);
        } while (LogisticsShipment::query()->where('reference', $reference)->exists());

        return $reference;
    }

    protected function assertOwned(User $user, LogisticsShipment $shipment): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $shipment->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own shipments.', 'SHIPMENT_FORBIDDEN', 403);
        }
    }
}
