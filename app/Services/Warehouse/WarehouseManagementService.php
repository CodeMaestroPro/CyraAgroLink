<?php

declare(strict_types=1);

namespace App\Services\Warehouse;

use App\Exceptions\BusinessLogicException;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse facilities, inventory stock, and stock movements.
 */
class WarehouseManagementService
{
    private const ICONS = ['maize', 'rice', 'cassava', 'others'];

    /**
     * @return array<string, mixed>
     */
    public function getManagementData(User $user, ?int $warehouseId = null, string $tab = 'list'): array
    {
        $this->warmUserWarehouses($user);

        $tab = in_array($tab, ['list', 'details'], true) ? $tab : 'list';

        $warehouses = Warehouse::query()
            ->where('user_id', $user->id)
            ->with(['stocks' => fn ($q) => $q->orderByDesc('quantity_tons')])
            ->latest('id')
            ->get();

        $selected = $warehouseId
            ? $warehouses->firstWhere('id', $warehouseId)
            : $warehouses->first();

        if ($tab === 'details' && ! $selected) {
            $tab = 'list';
        }

        $movements = $selected
            ? WarehouseMovement::query()
                ->where('warehouse_id', $selected->id)
                ->latest('id')
                ->limit(20)
                ->get()
            : collect();

        return [
            'tab' => $tab,
            'warehouses' => $warehouses->map(fn (Warehouse $warehouse) => $this->mapWarehouse($warehouse))->all(),
            'selected' => $selected ? $this->mapWarehouse($selected, true) : null,
            'commodity_options' => $this->commodityOptions(),
            'movements' => $movements->map(fn (WarehouseMovement $movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'label' => $movement->type === 'in' ? 'Stock in' : 'Stock out',
                'commodity' => $movement->commodity_name,
                'quantity' => number_format($movement->quantity_tons).' Tons',
                'note' => $movement->note,
                'time' => $movement->created_at?->diffForHumans() ?? '',
            ])->all(),
            'notifications_count' => max(2, $warehouses->count()),
        ];
    }

    /**
     * Register a new warehouse for the user.
     *
     * @param  array{name: string, city: string, state: string, capacity_tons: int}  $data
     */
    public function createWarehouse(User $user, array $data): Warehouse
    {
        return Warehouse::query()->create([
            'user_id' => $user->id,
            'name' => trim($data['name']),
            'city' => trim($data['city']),
            'state' => trim($data['state']),
            'capacity_tons' => (int) $data['capacity_tons'],
            'status' => 'active',
        ]);
    }

    /**
     * Receive stock into a warehouse.
     *
     * @param  array{commodity_name: string, quantity_tons: int, icon?: string, source?: string|null, note?: string|null}  $data
     */
    public function receiveStock(User $user, Warehouse $warehouse, array $data): WarehouseStock
    {
        $this->assertOwned($user, $warehouse);

        if (! $warehouse->isActive()) {
            throw new BusinessLogicException('This warehouse is not active for stock-in.');
        }

        $quantity = (int) $data['quantity_tons'];
        $name = $this->normalizeCommodityName((string) $data['commodity_name']);
        $icon = $this->resolveIcon($name, $data['icon'] ?? null);

        if ($name === '') {
            throw new BusinessLogicException('Commodity name is required for stock-in.');
        }

        if ($quantity < 1) {
            throw new BusinessLogicException('Stock-in quantity must be at least 1 ton.');
        }

        return DB::transaction(function () use ($user, $warehouse, $quantity, $name, $icon, $data): WarehouseStock {
            $locked = Warehouse::query()->whereKey($warehouse->id)->lockForUpdate()->firstOrFail();
            $used = (int) WarehouseStock::query()
                ->where('warehouse_id', $locked->id)
                ->lockForUpdate()
                ->sum('quantity_tons');

            if ($used + $quantity > $locked->capacity_tons) {
                $free = max(0, $locked->capacity_tons - $used);
                throw new BusinessLogicException(
                    $free === 0
                        ? 'Warehouse is at full capacity. Release stock before stocking in.'
                        : "Not enough capacity. Only {$free} tons free."
                );
            }

            // Case-insensitive match so "maize" merges into "Maize".
            $stock = WarehouseStock::query()
                ->where('warehouse_id', $locked->id)
                ->whereRaw('LOWER(commodity_name) = ?', [mb_strtolower($name)])
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->forceFill([
                    'quantity_tons' => $stock->quantity_tons + $quantity,
                ])->save();
            } else {
                $stock = WarehouseStock::query()->create([
                    'warehouse_id' => $locked->id,
                    'commodity_name' => $name,
                    'icon' => $icon,
                    'quantity_tons' => $quantity,
                ]);
            }

            $noteParts = array_filter([
                filled($data['source'] ?? null) ? 'Source: '.trim((string) $data['source']) : null,
                filled($data['note'] ?? null) ? trim((string) $data['note']) : null,
            ]);

            WarehouseMovement::query()->create([
                'warehouse_id' => $locked->id,
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'type' => 'in',
                'commodity_name' => $stock->commodity_name,
                'quantity_tons' => $quantity,
                'note' => $noteParts !== [] ? implode(' · ', $noteParts) : 'Stock received',
            ]);

            return $stock->refresh();
        });
    }

    /**
     * Remove stock from a warehouse.
     *
     * @param  array{quantity_tons: int, note?: string|null}  $data
     */
    public function releaseStock(User $user, WarehouseStock $stock, array $data): WarehouseStock
    {
        $warehouse = $stock->warehouse;
        if (! $warehouse) {
            throw new BusinessLogicException('Warehouse not found.');
        }

        $this->assertOwned($user, $warehouse);

        $quantity = (int) $data['quantity_tons'];

        return DB::transaction(function () use ($user, $stock, $warehouse, $quantity, $data): WarehouseStock {
            $locked = WarehouseStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();

            if ($quantity > $locked->quantity_tons) {
                throw new BusinessLogicException('Cannot release more stock than available.');
            }

            $locked->forceFill([
                'quantity_tons' => $locked->quantity_tons - $quantity,
            ])->save();

            WarehouseMovement::query()->create([
                'warehouse_id' => $warehouse->id,
                'stock_id' => $locked->id,
                'user_id' => $user->id,
                'type' => 'out',
                'commodity_name' => $locked->commodity_name,
                'quantity_tons' => $quantity,
                'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : 'Stock released',
            ]);

            if ($locked->quantity_tons === 0) {
                $locked->delete();

                return $locked;
            }

            return $locked->refresh();
        });
    }

    protected function warmUserWarehouses(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (Warehouse::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $warehouse = Warehouse::query()->create([
            'user_id' => $user->id,
            'name' => 'Ibadan Central Warehouse',
            'city' => 'Ibadan',
            'state' => 'Oyo State',
            'capacity_tons' => 1000,
            'status' => 'active',
        ]);

        $seed = [
            ['commodity_name' => 'Maize', 'quantity_tons' => 350, 'icon' => 'maize'],
            ['commodity_name' => 'Rice', 'quantity_tons' => 200, 'icon' => 'rice'],
            ['commodity_name' => 'Cassava', 'quantity_tons' => 150, 'icon' => 'cassava'],
            ['commodity_name' => 'Others', 'quantity_tons' => 50, 'icon' => 'others'],
        ];

        foreach ($seed as $row) {
            $stock = WarehouseStock::query()->create([
                'warehouse_id' => $warehouse->id,
                ...$row,
            ]);

            WarehouseMovement::query()->create([
                'warehouse_id' => $warehouse->id,
                'stock_id' => $stock->id,
                'user_id' => $user->id,
                'type' => 'in',
                'commodity_name' => $row['commodity_name'],
                'quantity_tons' => $row['quantity_tons'],
                'note' => 'Opening balance',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapWarehouse(Warehouse $warehouse, bool $withStocksDetail = false): array
    {
        $stocks = $warehouse->relationLoaded('stocks')
            ? $warehouse->stocks
            : $warehouse->stocks()->orderByDesc('quantity_tons')->get();

        $used = (int) $stocks->sum('quantity_tons');
        $occupancy = $warehouse->capacity_tons > 0
            ? (int) min(100, round(($used / $warehouse->capacity_tons) * 100))
            : 0;

        return [
            'id' => $warehouse->id,
            'name' => $warehouse->name,
            'location' => $warehouse->locationLabel(),
            'occupancy' => $occupancy,
            'capacity_tons' => $warehouse->capacity_tons,
            'used_tons' => $used,
            'free_tons' => max(0, $warehouse->capacity_tons - $used),
            'status' => $warehouse->isActive() ? 'Active' : 'Inactive',
            'inventory' => $stocks->map(fn (WarehouseStock $stock) => [
                'id' => $stock->id,
                'name' => $stock->commodity_name,
                'quantity' => $stock->quantityLabel(),
                'quantity_raw' => $stock->quantity_tons,
                'icon' => $stock->icon,
            ])->all(),
            'details_url' => route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouse->id]),
        ];
    }

    protected function normalizeCommodityName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        if ($name === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
    }

    protected function resolveIcon(string $commodityName, ?string $icon): string
    {
        if ($icon && in_array($icon, self::ICONS, true)) {
            return $icon;
        }

        $lower = mb_strtolower($commodityName);

        return match (true) {
            str_contains($lower, 'maize') => 'maize',
            str_contains($lower, 'rice') => 'rice',
            str_contains($lower, 'cassava') => 'cassava',
            default => 'others',
        };
    }

    /**
     * Common commodities offered in the stock-in picker.
     *
     * @return list<string>
     */
    public function commodityOptions(): array
    {
        return ['Maize', 'Rice', 'Cassava', 'Yam', 'Cocoa', 'Sorghum', 'Soybean', 'Others'];
    }

    protected function assertOwned(User $user, Warehouse $warehouse): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $warehouse->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own warehouses.', 'WAREHOUSE_FORBIDDEN', 403);
        }
    }
}
