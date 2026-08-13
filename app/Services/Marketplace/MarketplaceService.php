<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Contracts\Repositories\MarketplaceRepositoryInterface;
use App\Enums\UserRole;
use App\Exceptions\BusinessLogicException;
use App\Models\ExchangeOrder;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCommodity;
use App\Models\MarketplaceSupplier;
use App\Models\User;
use App\Services\Exchange\CommodityExchangeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Smart Marketplace catalog orchestration.
 */
class MarketplaceService
{
    public function __construct(
        protected MarketplaceRepositoryInterface $marketplaceRepository
    ) {
    }

    /**
     * Build the marketplace landing payload, seeding demo catalog when empty.
     *
     * @return array<string, mixed>
     */
    public function getCatalog(
        ?string $query = null,
        ?string $category = null,
        ?string $state = null,
        string $view = 'commodities',
        ?User $user = null,
        string $orderStatus = 'all'
    ): array {
        $this->warmCatalogIfAllowed();

        $search = trim((string) $query);
        $categorySlug = trim((string) $category) ?: null;
        $stateFilter = trim((string) $state) ?: null;
        $view = in_array($view, ['commodities', 'suppliers', 'listings', 'orders'], true)
            ? $view
            : 'commodities';

        if (! in_array($orderStatus, ['all', 'open', 'filled', 'cancelled'], true)) {
            $orderStatus = 'all';
        }

        $ordersQuery = $user
            ? ExchangeOrder::query()
                ->with('commodity')
                ->where('user_id', $user->id)
                ->latest('id')
            : null;

        $openOrdersCount = $user
            ? (int) ExchangeOrder::query()
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->count()
            : 0;

        $orders = collect();
        if ($ordersQuery !== null) {
            if ($orderStatus !== 'all') {
                $ordersQuery->where('status', $orderStatus);
            }
            $orders = $ordersQuery->limit(50)->get();
        }

        $ordersValue = (int) $orders
            ->where('status', 'open')
            ->sum(fn (ExchangeOrder $order) => $order->quantity_tons * $order->price_per_ton);

        return [
            'categories' => $this->marketplaceRepository->getActiveCategories(),
            'commodities' => $this->marketplaceRepository->filterCommodities($search, $categorySlug, $stateFilter, 24),
            'suppliers' => $view === 'suppliers'
                ? $this->marketplaceRepository->getActiveSuppliers(24)
                : $this->marketplaceRepository->getTopSuppliers(4),
            'my_listings' => $user
                ? $this->marketplaceRepository->getForUser($user)
                : collect(),
            'orders' => $orders,
            'orders_count' => $openOrdersCount,
            'orders_value' => $ordersValue,
            'order_status' => $orderStatus,
            'query' => $search,
            'category' => $categorySlug,
            'state' => $stateFilter,
            'view' => $view,
            'states' => config('cyra.nigeria_states', []),
            'can_list' => $user !== null && (
                $user->isAdmin()
                || $user->hasRole(UserRole::Farmer)
                || $user->hasRole(UserRole::Supplier)
            ),
        ];
    }

    /**
     * Soft-deactivate an owned listing.
     */
    public function deactivateListing(User $user, MarketplaceCommodity $commodity): MarketplaceCommodity
    {
        $this->assertOwnedListing($user, $commodity);

        $commodity->forceFill(['status' => 'inactive'])->save();

        return $commodity->refresh();
    }

    /**
     * Update price / location on an owned listing.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateListing(User $user, MarketplaceCommodity $commodity, array $data): MarketplaceCommodity
    {
        $this->assertOwnedListing($user, $commodity);

        if ($commodity->status !== 'active') {
            throw new BusinessLogicException('Inactive listings cannot be edited.');
        }

        $commodity->forceFill([
            'price_per_ton' => (int) $data['price_per_ton'],
            'previous_price_per_ton' => $commodity->price_per_ton,
            'city' => $data['city'] ?? $commodity->city,
            'state' => $data['state'] ?? $commodity->state,
            'category_id' => $data['category_id'] ?? $commodity->category_id,
        ])->save();

        return $commodity->refresh();
    }

    /**
     * Place a quick buy order on the live exchange (wallet hold + matching).
     *
     * @param  array{quantity_tons: int}  $data
     */
    public function placeQuickBuy(User $user, MarketplaceCommodity $commodity, array $data): ExchangeOrder
    {
        if ($commodity->status !== 'active') {
            throw new BusinessLogicException('This listing is not available.');
        }

        return app(CommodityExchangeService::class)->placeOrder($user, $commodity, [
            'side' => 'buy',
            'quantity_tons' => (int) $data['quantity_tons'],
            'price_per_ton' => (int) $commodity->price_per_ton,
        ]);
    }

    /**
     * Cancel an open order owned by the user (releases any exchange buy hold).
     */
    public function cancelOrder(User $user, ExchangeOrder $order): ExchangeOrder
    {
        return app(CommodityExchangeService::class)->cancelOrder($user, $order);
    }

    /**
     * Update quantity on an open order.
     *
     * @param  array{quantity_tons: int}  $data
     */
    public function updateOrder(User $user, ExchangeOrder $order, array $data): ExchangeOrder
    {
        $this->assertOwnedOrder($user, $order);

        if ($order->status !== 'open') {
            throw new BusinessLogicException('Only open orders can be updated.');
        }

        if ($order->isBuy() && (int) $order->reserved_amount > 0) {
            throw new BusinessLogicException(
                'Live buy orders with wallet holds cannot change quantity. Cancel and place a new order.'
            );
        }

        $quantity = (int) $data['quantity_tons'];
        $order->forceFill([
            'quantity_tons' => $quantity,
            'original_quantity_tons' => max((int) $order->original_quantity_tons, $quantity),
        ])->save();

        return $order->refresh();
    }

    protected function assertOwnedListing(User $user, MarketplaceCommodity $commodity): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $commodity->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own listings.', 'MARKET_FORBIDDEN', 403);
        }
    }

    protected function assertOwnedOrder(User $user, ExchangeOrder $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $order->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own orders.', 'ORDER_FORBIDDEN', 403);
        }
    }

    /**
     * Featured listings for the public home page.
     *
     * @return Collection<int, MarketplaceCommodity>
     */
    public function getFeaturedForHome(int $limit = 12): Collection
    {
        $this->warmCatalogIfAllowed();

        return $this->marketplaceRepository->getFeaturedCommodities($limit);
    }

    /**
     * Publish a commodity listing uploaded from the dashboard.
     *
     * @param  array{
     *     name: string,
     *     category_id?: int|null,
     *     price_per_ton: int,
     *     city?: string|null,
     *     state?: string|null,
     *     scientific_name?: string|null,
     *     user_id?: int|null
     * }  $data
     */
    public function createListing(array $data, ?UploadedFile $image = null): MarketplaceCommodity
    {
        if (\App\Support\DemoSeeding::allowed()) {
            $this->ensureDemoCatalog();
        }

        $imagePath = null;

        if ($image instanceof UploadedFile) {
            $stored = $image->store('marketplace', 'public');
            $imagePath = 'storage/'.$stored;
        }

        return MarketplaceCommodity::query()->create([
            'user_id' => $data['user_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'scientific_name' => $data['scientific_name'] ?? null,
            'price_per_ton' => (int) $data['price_per_ton'],
            'previous_price_per_ton' => (int) $data['price_per_ton'],
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'image_path' => $imagePath,
            'is_featured' => false,
            'status' => 'active',
        ]);
    }

    /**
     * Seed / enrich demo catalog only outside production (never on GET in prod).
     */
    protected function warmCatalogIfAllowed(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        $this->ensureDemoCatalog();
        $this->ensureFeaturedCommodities();
        $this->enrichMarketMetadata();
        $this->syncCatalogImagePaths();
    }

    /**
     * Backfill exchange metadata for already-seeded commodities.
     */
    protected function enrichMarketMetadata(): void
    {
        $metadata = [
            'Maize' => [
                'scientific_name' => 'Zea mays',
                'previous_price_per_ton' => 309328,
                'day_high' => 335000,
                'day_low' => 318000,
                'volume_tons' => 5620,
                'open_interest_tons' => 2250,
            ],
            'Rice' => [
                'scientific_name' => 'Oryza sativa',
                'previous_price_per_ton' => 760000,
                'day_high' => 795000,
                'day_low' => 752000,
                'volume_tons' => 4120,
                'open_interest_tons' => 1890,
            ],
            'Cassava' => [
                'scientific_name' => 'Manihot esculenta',
                'previous_price_per_ton' => 180000,
                'day_high' => 192000,
                'day_low' => 178000,
                'volume_tons' => 6840,
                'open_interest_tons' => 2410,
            ],
            'Cocoa' => [
                'scientific_name' => 'Theobroma cacao',
                'previous_price_per_ton' => 2380000,
                'day_high' => 2510000,
                'day_low' => 2365000,
                'volume_tons' => 980,
                'open_interest_tons' => 640,
            ],
            'Yam' => [
                'scientific_name' => 'Dioscorea spp.',
                'previous_price_per_ton' => 410000,
                'day_high' => 445000,
                'day_low' => 402000,
                'volume_tons' => 3180,
                'open_interest_tons' => 1420,
            ],
            'Sorghum' => [
                'scientific_name' => 'Sorghum bicolor',
                'previous_price_per_ton' => 265000,
                'day_high' => 285000,
                'day_low' => 258000,
                'volume_tons' => 2740,
                'open_interest_tons' => 980,
            ],
            'Soybean' => [
                'scientific_name' => 'Glycine max',
                'previous_price_per_ton' => 520000,
                'day_high' => 548000,
                'day_low' => 505000,
                'volume_tons' => 1960,
                'open_interest_tons' => 870,
            ],
            'Groundnut' => [
                'scientific_name' => 'Arachis hypogaea',
                'previous_price_per_ton' => 610000,
                'day_high' => 640000,
                'day_low' => 595000,
                'volume_tons' => 1540,
                'open_interest_tons' => 720,
            ],
            'Tomato' => [
                'scientific_name' => 'Solanum lycopersicum',
                'previous_price_per_ton' => 290000,
                'day_high' => 325000,
                'day_low' => 278000,
                'volume_tons' => 2210,
                'open_interest_tons' => 640,
            ],
            'Millet' => [
                'scientific_name' => 'Pennisetum glaucum',
                'previous_price_per_ton' => 240000,
                'day_high' => 255000,
                'day_low' => 232000,
                'volume_tons' => 1880,
                'open_interest_tons' => 610,
            ],
            'Sesame' => [
                'scientific_name' => 'Sesamum indicum',
                'previous_price_per_ton' => 890000,
                'day_high' => 925000,
                'day_low' => 870000,
                'volume_tons' => 740,
                'open_interest_tons' => 390,
            ],
            'Plantain' => [
                'scientific_name' => 'Musa paradisiaca',
                'previous_price_per_ton' => 175000,
                'day_high' => 198000,
                'day_low' => 168000,
                'volume_tons' => 2650,
                'open_interest_tons' => 880,
            ],
        ];

        foreach ($metadata as $name => $fields) {
            MarketplaceCommodity::query()
                ->where('name', $name)
                ->whereNull('scientific_name')
                ->update($fields);
        }
    }

    /**
     * Prefer real UI mockup photos over placeholder SVG assets.
     */
    protected function syncCatalogImagePaths(): void
    {
        $commodityImages = [
            'Maize' => 'images/marketplace/maize.jpg',
            'Rice' => 'images/marketplace/rice.jpg',
            'Cassava' => 'images/marketplace/cassava.jpg',
            'Cocoa' => 'images/marketplace/cocoa.jpg',
            'Yam' => 'images/marketplace/yam.jpg',
            'Sorghum' => 'images/marketplace/sorghum.jpg',
            'Soybean' => 'images/marketplace/soybean.jpg',
            'Groundnut' => 'images/marketplace/groundnut.jpg',
            'Tomato' => 'images/marketplace/tomato.jpg',
            'Millet' => 'images/marketplace/millet.jpg',
            'Sesame' => 'images/marketplace/sesame.jpg',
            'Plantain' => 'images/marketplace/plantain.jpg',
        ];

        foreach ($commodityImages as $name => $path) {
            MarketplaceCommodity::query()
                ->where('name', $name)
                ->where(function ($query): void {
                    $query
                        ->whereNull('image_path')
                        ->orWhere('image_path', 'not like', 'storage/%');
                })
                ->update(['image_path' => $path]);
        }

        $supplierImages = [
            'Green Valley Farms' => 'images/marketplace/supplier-1.jpg',
            'Sunrise Farms' => 'images/marketplace/supplier-2.jpg',
            'Gold Harvest Ltd' => 'images/marketplace/supplier-3.jpg',
            "Nature's Pride" => 'images/marketplace/supplier-4.jpg',
        ];

        foreach ($supplierImages as $name => $path) {
            MarketplaceSupplier::query()
                ->where('name', $name)
                ->update(['image_path' => $path]);
        }
    }

    /**
     * Seed baseline marketplace data for first-run UI fidelity.
     */
    protected function ensureDemoCatalog(): void
    {
        if (MarketplaceCategory::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $categories = [
                'Tubers', 'Legumes', 'Oil Seeds', 'Fruits', 'Vegetables', 'Spices', 'Cereals',
            ];

            $categoryIds = [];

            foreach ($categories as $index => $name) {
                $category = MarketplaceCategory::query()->create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
                $categoryIds[$name] = $category->id;
            }

            foreach ($this->featuredCommodityDefinitions() as $item) {
                MarketplaceCommodity::query()->create([
                    'category_id' => $categoryIds[$item['category']] ?? null,
                    'name' => $item['name'],
                    'scientific_name' => $item['scientific_name'],
                    'price_per_ton' => $item['price'],
                    'previous_price_per_ton' => $item['previous'],
                    'day_high' => $item['day_high'],
                    'day_low' => $item['day_low'],
                    'volume_tons' => $item['volume'],
                    'open_interest_tons' => $item['open_interest'],
                    'city' => $item['city'],
                    'state' => $item['state'],
                    'image_path' => $item['image'],
                    'is_featured' => true,
                    'status' => 'active',
                ]);
            }

            $suppliers = [
                ['name' => 'Green Valley Farms', 'state' => 'Oyo', 'rating' => 4.8, 'reviews' => 128, 'image' => 'images/marketplace/supplier-1.jpg'],
                ['name' => 'Sunrise Farms', 'state' => 'Kaduna', 'rating' => 4.6, 'reviews' => 96, 'image' => 'images/marketplace/supplier-2.jpg'],
                ['name' => 'Gold Harvest Ltd', 'state' => 'Ogun', 'rating' => 4.9, 'reviews' => 210, 'image' => 'images/marketplace/supplier-3.jpg'],
                ['name' => "Nature's Pride", 'state' => 'Ondo', 'rating' => 4.7, 'reviews' => 154, 'image' => 'images/marketplace/supplier-4.jpg'],
            ];

            foreach ($suppliers as $supplier) {
                MarketplaceSupplier::query()->create([
                    'name' => $supplier['name'],
                    'state' => $supplier['state'],
                    'rating' => $supplier['rating'],
                    'review_count' => $supplier['reviews'],
                    'image_path' => $supplier['image'],
                    'is_top' => true,
                    'status' => 'active',
                ]);
            }
        });
    }

    /**
     * Backfill additional featured commodities on existing installs.
     */
    protected function ensureFeaturedCommodities(): void
    {
        $categoryIds = MarketplaceCategory::query()
            ->pluck('id', 'name')
            ->all();

        if ($categoryIds === []) {
            return;
        }

        // Ensure newer categories exist for expanded catalog.
        foreach (['Cereals'] as $index => $name) {
            if (! isset($categoryIds[$name])) {
                $category = MarketplaceCategory::query()->create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sort_order' => 10 + $index,
                    'is_active' => true,
                ]);
                $categoryIds[$name] = $category->id;
            }
        }

        foreach ($this->featuredCommodityDefinitions() as $item) {
            $exists = MarketplaceCommodity::query()
                ->where('name', $item['name'])
                ->exists();

            if ($exists) {
                MarketplaceCommodity::query()
                    ->where('name', $item['name'])
                    ->where('status', 'active')
                    ->update([
                        'is_featured' => true,
                        'category_id' => $categoryIds[$item['category']] ?? null,
                    ]);

                continue;
            }

            MarketplaceCommodity::query()->create([
                'category_id' => $categoryIds[$item['category']] ?? null,
                'name' => $item['name'],
                'scientific_name' => $item['scientific_name'],
                'price_per_ton' => $item['price'],
                'previous_price_per_ton' => $item['previous'],
                'day_high' => $item['day_high'],
                'day_low' => $item['day_low'],
                'volume_tons' => $item['volume'],
                'open_interest_tons' => $item['open_interest'],
                'city' => $item['city'],
                'state' => $item['state'],
                'image_path' => $item['image'],
                'is_featured' => true,
                'status' => 'active',
            ]);
        }
    }

    /**
     * Featured commodity seed definitions for marketplace + home page.
     *
     * @return list<array<string, mixed>>
     */
    protected function featuredCommodityDefinitions(): array
    {
        return [
            [
                'name' => 'Maize',
                'scientific_name' => 'Zea mays',
                'price' => 320000,
                'previous' => 309328,
                'day_high' => 335000,
                'day_low' => 318000,
                'volume' => 5620,
                'open_interest' => 2250,
                'city' => 'Ibadan',
                'state' => 'Oyo',
                'image' => 'images/marketplace/maize.jpg',
                'category' => 'Cereals',
            ],
            [
                'name' => 'Rice',
                'scientific_name' => 'Oryza sativa',
                'price' => 780000,
                'previous' => 760000,
                'day_high' => 795000,
                'day_low' => 752000,
                'volume' => 4120,
                'open_interest' => 1890,
                'city' => 'Zaria',
                'state' => 'Kaduna',
                'image' => 'images/marketplace/rice.jpg',
                'category' => 'Cereals',
            ],
            [
                'name' => 'Cassava',
                'scientific_name' => 'Manihot esculenta',
                'price' => 185000,
                'previous' => 180000,
                'day_high' => 192000,
                'day_low' => 178000,
                'volume' => 6840,
                'open_interest' => 2410,
                'city' => 'Abeokuta',
                'state' => 'Ogun',
                'image' => 'images/marketplace/cassava.jpg',
                'category' => 'Tubers',
            ],
            [
                'name' => 'Cocoa',
                'scientific_name' => 'Theobroma cacao',
                'price' => 2450000,
                'previous' => 2380000,
                'day_high' => 2510000,
                'day_low' => 2365000,
                'volume' => 980,
                'open_interest' => 640,
                'city' => 'Ondo',
                'state' => 'Ondo',
                'image' => 'images/marketplace/cocoa.jpg',
                'category' => 'Oil Seeds',
            ],
            [
                'name' => 'Yam',
                'scientific_name' => 'Dioscorea spp.',
                'price' => 425000,
                'previous' => 410000,
                'day_high' => 445000,
                'day_low' => 402000,
                'volume' => 3180,
                'open_interest' => 1420,
                'city' => 'Makurdi',
                'state' => 'Benue',
                'image' => 'images/marketplace/yam.jpg',
                'category' => 'Tubers',
            ],
            [
                'name' => 'Sorghum',
                'scientific_name' => 'Sorghum bicolor',
                'price' => 275000,
                'previous' => 265000,
                'day_high' => 285000,
                'day_low' => 258000,
                'volume' => 2740,
                'open_interest' => 980,
                'city' => 'Kano',
                'state' => 'Kano',
                'image' => 'images/marketplace/sorghum.jpg',
                'category' => 'Cereals',
            ],
            [
                'name' => 'Soybean',
                'scientific_name' => 'Glycine max',
                'price' => 535000,
                'previous' => 520000,
                'day_high' => 548000,
                'day_low' => 505000,
                'volume' => 1960,
                'open_interest' => 870,
                'city' => 'Jos',
                'state' => 'Plateau',
                'image' => 'images/marketplace/soybean.jpg',
                'category' => 'Legumes',
            ],
            [
                'name' => 'Groundnut',
                'scientific_name' => 'Arachis hypogaea',
                'price' => 625000,
                'previous' => 610000,
                'day_high' => 640000,
                'day_low' => 595000,
                'volume' => 1540,
                'open_interest' => 720,
                'city' => 'Bauchi',
                'state' => 'Bauchi',
                'image' => 'images/marketplace/groundnut.jpg',
                'category' => 'Oil Seeds',
            ],
            [
                'name' => 'Tomato',
                'scientific_name' => 'Solanum lycopersicum',
                'price' => 310000,
                'previous' => 290000,
                'day_high' => 325000,
                'day_low' => 278000,
                'volume' => 2210,
                'open_interest' => 640,
                'city' => 'Kaduna',
                'state' => 'Kaduna',
                'image' => 'images/marketplace/tomato.jpg',
                'category' => 'Vegetables',
            ],
            [
                'name' => 'Millet',
                'scientific_name' => 'Pennisetum glaucum',
                'price' => 248000,
                'previous' => 240000,
                'day_high' => 255000,
                'day_low' => 232000,
                'volume' => 1880,
                'open_interest' => 610,
                'city' => 'Sokoto',
                'state' => 'Sokoto',
                'image' => 'images/marketplace/millet.jpg',
                'category' => 'Cereals',
            ],
            [
                'name' => 'Sesame',
                'scientific_name' => 'Sesamum indicum',
                'price' => 910000,
                'previous' => 890000,
                'day_high' => 925000,
                'day_low' => 870000,
                'volume' => 740,
                'open_interest' => 390,
                'city' => 'Jalingo',
                'state' => 'Taraba',
                'image' => 'images/marketplace/sesame.jpg',
                'category' => 'Oil Seeds',
            ],
            [
                'name' => 'Plantain',
                'scientific_name' => 'Musa paradisiaca',
                'price' => 188000,
                'previous' => 175000,
                'day_high' => 198000,
                'day_low' => 168000,
                'volume' => 2650,
                'open_interest' => 880,
                'city' => 'Benin City',
                'state' => 'Edo',
                'image' => 'images/marketplace/plantain.jpg',
                'category' => 'Fruits',
            ],
        ];
    }
}
