<?php

declare(strict_types=1);

namespace App\Services\Equipment;

use App\Exceptions\BusinessLogicException;
use App\Models\EquipmentCartItem;
use App\Models\EquipmentFavorite;
use App\Models\EquipmentListing;
use App\Models\EquipmentOrder;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live agricultural equipment marketplace: catalog, cart, favorites, and wallet checkout.
 */
class EquipmentMarketplaceService
{
    public const USD_TO_NGN = 1550;

    /**
     * @var list<string>
     */
    public const CATEGORIES = [
        'Tractors',
        'Harvesters',
        'Irrigation',
        'Implements',
        'Sprayers',
        'Processing',
        'Parts & Tools',
        'Others',
    ];

    /**
     * @var list<string>
     */
    public const TABS = ['sale', 'rent', 'parts'];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMarketplaceData(
        User $user,
        ?string $tab = 'sale',
        ?string $category = null,
        ?string $query = null,
        string $view = 'browse'
    ): array {
        $this->ensureCatalog();

        $view = in_array($view, ['browse', 'cart', 'orders'], true) ? $view : 'browse';
        $tab = in_array($tab, self::TABS, true) ? $tab : 'sale';
        $category = $category && in_array($category, self::CATEGORIES, true) ? $category : null;
        $query = trim((string) $query);

        $favoriteIds = EquipmentFavorite::query()
            ->where('user_id', $user->id)
            ->pluck('listing_id')
            ->all();

        $listings = EquipmentListing::query()
            ->where('is_active', true)
            ->where('listing_type', $tab)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($query !== '', function ($q) use ($query): void {
                $q->where(function ($inner) use ($query): void {
                    $inner->where('name', 'like', '%'.$query.'%')
                        ->orWhere('category', 'like', '%'.$query.'%')
                        ->orWhere('location', 'like', '%'.$query.'%')
                        ->orWhere('description', 'like', '%'.$query.'%');
                });
            })
            ->orderByDesc('rating')
            ->orderBy('name')
            ->get();

        $cartItems = $this->cartItemsFor($user);
        $cartCount = (int) $cartItems->sum('quantity');
        $cartTotal = (int) $cartItems->sum(
            fn (EquipmentCartItem $item) => $item->lineTotalNgn(self::USD_TO_NGN)
        );
        $walletBalance = $this->walletService->getBalance($user);

        $orders = EquipmentOrder::query()
            ->where('user_id', $user->id)
            ->with('listing')
            ->latest('id')
            ->limit(30)
            ->get();

        return [
            'view' => $view,
            'categories' => collect(self::CATEGORIES)->map(fn (string $name) => [
                'name' => $name,
                'active' => $category === $name,
                'url' => route('equipment.marketplace', array_filter([
                    'view' => 'browse',
                    'tab' => $tab,
                    'category' => $name,
                    'q' => $query !== '' ? $query : null,
                ])),
            ])->all(),
            'tabs' => collect(self::TABS)->map(fn (string $id) => [
                'id' => $id,
                'label' => match ($id) {
                    'sale' => 'For Sale',
                    'rent' => 'For Rent',
                    default => 'Spare Parts',
                },
                'active' => $tab === $id,
                'url' => route('equipment.marketplace', array_filter([
                    'view' => 'browse',
                    'tab' => $id,
                    'category' => $category,
                    'q' => $query !== '' ? $query : null,
                ])),
            ])->all(),
            'listings' => $listings->map(fn (EquipmentListing $item) => $this->presentListing($item, $favoriteIds))->all(),
            'cart_items' => $cartItems->map(fn (EquipmentCartItem $item) => $this->presentCartItem($item))->all(),
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
            'wallet_can_pay_cart' => $walletBalance >= $cartTotal && $cartTotal > 0,
            'orders' => $orders->map(fn (EquipmentOrder $order) => $this->presentOrder($order))->all(),
            'active_tab' => $tab,
            'active_category' => $category,
            'query' => $query,
            'favorites_count' => count($favoriteIds),
            'orders_count' => $orders->count(),
            'wallet_balance' => $walletBalance,
            'actions' => [
                'search_url' => route('equipment.marketplace'),
                'wallet_url' => route('wallet.index'),
                'cart_url' => route('equipment.marketplace', ['view' => 'cart']),
                'orders_url' => route('equipment.marketplace', ['view' => 'orders']),
                'browse_url' => route('equipment.marketplace', array_filter([
                    'view' => 'browse',
                    'tab' => $tab,
                    'category' => $category,
                    'q' => $query !== '' ? $query : null,
                ])),
                'checkout_url' => route('equipment.checkout'),
            ],
            'notifications_count' => max(2, $cartCount + count($favoriteIds) + 1),
        ];
    }

    /**
     * Add a listing to the cart (or increase quantity / rental days).
     */
    public function addToCart(
        User $user,
        EquipmentListing $listing,
        int $quantity = 1,
        int $rentalDays = 1
    ): EquipmentCartItem {
        if (! $listing->isAvailable()) {
            throw new BusinessLogicException('This equipment is out of stock.');
        }

        $quantity = max(1, $quantity);
        $rentalDays = $listing->listing_type === 'rent' ? max(1, $rentalDays) : 1;

        if ($quantity > $listing->stock) {
            throw new BusinessLogicException('Not enough stock for this listing.');
        }

        $existing = EquipmentCartItem::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            $nextQty = $existing->quantity + $quantity;
            if ($nextQty > $listing->stock) {
                throw new BusinessLogicException('Not enough stock for this listing.');
            }

            $existing->forceFill([
                'quantity' => $nextQty,
                'rental_days' => $listing->listing_type === 'rent'
                    ? max($existing->rental_days, $rentalDays)
                    : 1,
            ])->save();

            return $existing->refresh()->load('listing');
        }

        return EquipmentCartItem::query()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'quantity' => $quantity,
            'rental_days' => $rentalDays,
        ])->load('listing');
    }

    /**
     * Update cart line quantity and rental days.
     */
    public function updateCartItem(
        User $user,
        EquipmentCartItem $item,
        int $quantity,
        ?int $rentalDays = null
    ): EquipmentCartItem {
        $this->assertOwnedCartItem($user, $item);

        $listing = $item->listing;
        if (! $listing || ! $listing->is_active) {
            throw new BusinessLogicException('This listing is no longer available.');
        }

        if ($quantity < 1) {
            $item->delete();

            return $item;
        }

        if ($quantity > $listing->stock) {
            throw new BusinessLogicException('Not enough stock for this listing.');
        }

        $days = $listing->listing_type === 'rent'
            ? max(1, $rentalDays ?? $item->rental_days)
            : 1;

        $item->forceFill([
            'quantity' => $quantity,
            'rental_days' => $days,
        ])->save();

        return $item->refresh()->load('listing');
    }

    /**
     * Remove a cart line.
     */
    public function removeCartItem(User $user, EquipmentCartItem $item): void
    {
        $this->assertOwnedCartItem($user, $item);
        $item->delete();
    }

    /**
     * Pay for every cart line from the wallet and clear the cart.
     *
     * @return Collection<int, EquipmentOrder>
     */
    public function checkoutCart(User $user): Collection
    {
        return DB::transaction(function () use ($user): Collection {
            $items = EquipmentCartItem::query()
                ->where('user_id', $user->id)
                ->with('listing')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new BusinessLogicException('Your cart is empty.');
            }

            $orders = collect();

            foreach ($items as $item) {
                $listing = $item->listing;
                if (! $listing) {
                    throw new BusinessLogicException('A cart item is no longer available.');
                }

                $orders->push($this->fulfillOrder(
                    $user,
                    $listing,
                    (int) $item->quantity,
                    (int) $item->rental_days
                ));
            }

            EquipmentCartItem::query()->where('user_id', $user->id)->delete();

            return $orders;
        });
    }

    /**
     * Toggle a listing as a user favorite.
     */
    public function toggleFavorite(User $user, EquipmentListing $listing): bool
    {
        if (! $listing->is_active) {
            throw new BusinessLogicException('This listing is no longer available.');
        }

        $existing = EquipmentFavorite::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        EquipmentFavorite::query()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        return true;
    }

    /**
     * Purchase or rent equipment immediately using the digital wallet.
     */
    public function placeOrder(
        User $user,
        EquipmentListing $listing,
        int $quantity = 1,
        int $rentalDays = 1
    ): EquipmentOrder {
        return DB::transaction(function () use ($user, $listing, $quantity, $rentalDays): EquipmentOrder {
            return $this->fulfillOrder($user, $listing, $quantity, $rentalDays);
        });
    }

    /**
     * Create a paid order for one listing line (caller manages outer transaction when needed).
     */
    protected function fulfillOrder(
        User $user,
        EquipmentListing $listing,
        int $quantity = 1,
        int $rentalDays = 1
    ): EquipmentOrder {
        $quantity = max(1, $quantity);
        $rentalDays = $listing->listing_type === 'rent' ? max(1, $rentalDays) : 1;

        /** @var EquipmentListing $locked */
        $locked = EquipmentListing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isAvailable()) {
            throw new BusinessLogicException("{$locked->name} is out of stock.");
        }

        if ($quantity > $locked->stock) {
            throw new BusinessLogicException("Not enough stock for {$locked->name}.");
        }

        $unitUsd = (int) $locked->price_usd;
        $amountNgn = $this->usdToNgn($unitUsd * $quantity * $rentalDays);

        $this->walletService->ensureWallet($user);

        $order = EquipmentOrder::query()->create([
            'user_id' => $user->id,
            'listing_id' => $locked->id,
            'reference' => $this->nextReference($user),
            'order_type' => $locked->listing_type,
            'amount_ngn' => $amountNgn,
            'status' => 'paid',
            'meta' => [
                'price_usd' => $unitUsd,
                'listing_name' => $locked->name,
                'quantity' => $quantity,
                'rental_days' => $rentalDays,
            ],
        ]);

        $detail = $order->reference.' · '.$locked->name;
        if ($quantity > 1) {
            $detail .= " ×{$quantity}";
        }
        if ($locked->listing_type === 'rent') {
            $detail .= " · {$rentalDays} day".($rentalDays === 1 ? '' : 's');
        }

        $this->walletService->payForEquipment(
            $user,
            $amountNgn,
            $order,
            $detail
        );

        $locked->forceFill([
            'stock' => max(0, $locked->stock - $quantity),
        ])->save();

        return $order->load('listing');
    }

    /**
     * @return Collection<int, EquipmentCartItem>
     */
    protected function cartItemsFor(User $user): Collection
    {
        return EquipmentCartItem::query()
            ->where('user_id', $user->id)
            ->with('listing')
            ->latest('id')
            ->get();
    }

    /**
     * @param  list<int>  $favoriteIds
     * @return array<string, mixed>
     */
    protected function presentListing(EquipmentListing $item, array $favoriteIds): array
    {
        $favorited = in_array($item->id, $favoriteIds, true);
        $cta = match ($item->listing_type) {
            'rent' => 'Rent',
            'parts' => 'Buy parts',
            default => 'Buy',
        };
        $priceLabel = $this->formatUsd((int) $item->price_usd);
        if ($item->listing_type === 'rent') {
            $priceLabel .= '/day';
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'price' => $priceLabel,
            'price_ngn' => '₦'.number_format($this->usdToNgn((int) $item->price_usd)),
            'location' => $item->location,
            'rating' => number_format((float) $item->rating, 1),
            'image' => $item->imageUrl(),
            'category' => $item->category,
            'listing_type' => $item->listing_type,
            'is_rent' => $item->listing_type === 'rent',
            'available' => $item->isAvailable(),
            'stock' => $item->stock,
            'favorited' => $favorited,
            'cta' => $cta,
            'favorite_url' => route('equipment.favorite', $item),
            'cart_url' => route('equipment.cart.add', $item),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentCartItem(EquipmentCartItem $item): array
    {
        $listing = $item->listing;
        $isRent = $listing?->listing_type === 'rent';
        $lineTotal = $item->lineTotalNgn(self::USD_TO_NGN);

        return [
            'id' => $item->id,
            'name' => $listing?->name ?? 'Equipment',
            'image' => $listing?->imageUrl() ?? asset('images/equipment/placeholder.jpg'),
            'type' => match ($listing?->listing_type) {
                'rent' => 'Rental',
                'parts' => 'Parts',
                default => 'Purchase',
            },
            'is_rent' => $isRent,
            'unit_price' => $listing
                ? $this->formatUsd((int) $listing->price_usd).($isRent ? '/day' : '')
                : '$0',
            'quantity' => $item->quantity,
            'rental_days' => $item->rental_days,
            'stock' => $listing?->stock ?? 0,
            'line_total' => '₦'.number_format($lineTotal),
            'line_total_ngn' => $lineTotal,
            'update_url' => route('equipment.cart.update', $item),
            'remove_url' => route('equipment.cart.remove', $item),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentOrder(EquipmentOrder $order): array
    {
        $qty = (int) ($order->meta['quantity'] ?? 1);
        $days = (int) ($order->meta['rental_days'] ?? 1);
        $detail = $order->order_type === 'rent'
            ? "{$qty} unit · {$days} day".($days === 1 ? '' : 's')
            : "Qty {$qty}";

        return [
            'reference' => $order->reference,
            'title' => $order->listing?->name ?? ($order->meta['listing_name'] ?? 'Equipment'),
            'type' => match ($order->order_type) {
                'rent' => 'Rental',
                'parts' => 'Parts',
                default => 'Purchase',
            },
            'detail' => $detail,
            'amount' => '₦'.number_format($order->amount_ngn),
            'status' => ucfirst($order->status),
            'when' => $order->created_at?->format('M j, Y') ?? '',
        ];
    }

    protected function assertOwnedCartItem(User $user, EquipmentCartItem $item): void
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if ((int) $item->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own cart.', 'CART_FORBIDDEN', 403);
        }
    }

    protected function ensureCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        foreach ($this->seedListings() as $row) {
            $existing = EquipmentListing::query()
                ->where('name', $row['name'])
                ->where('listing_type', $row['listing_type'])
                ->first();

            if ($existing) {
                // Refresh product photo/metadata without resetting live stock.
                $existing->forceFill([
                    'image_path' => $row['image_path'],
                    'category' => $row['category'],
                    'location' => $row['location'],
                    'description' => $row['description'],
                    'rating' => $row['rating'],
                ])->save();

                continue;
            }

            EquipmentListing::query()->create($row);
        }
    }

    /**
     * Full catalog: every category has For Sale, For Rent, and Spare Parts stock.
     *
     * @return list<array<string, mixed>>
     */
    protected function seedListings(): array
    {
        return [
            // For Sale — one+ listing per category
            [
                'name' => 'John Deere 5075E',
                'category' => 'Tractors',
                'listing_type' => 'sale',
                'price_usd' => 35000,
                'location' => 'Lagos, NG',
                'rating' => 4.8,
                'image_path' => 'images/equipment/john-deere-5075e.jpg',
                'stock' => 3,
                'description' => 'Utility tractor for medium farms',
            ],
            [
                'name' => 'New Holland TX66',
                'category' => 'Harvesters',
                'listing_type' => 'sale',
                'price_usd' => 38000,
                'location' => 'Kano, NG',
                'rating' => 4.7,
                'image_path' => 'images/equipment/new-holland-tx66.jpg',
                'stock' => 2,
                'description' => 'Combine harvester',
            ],
            [
                'name' => 'Case IH Axial-Flow AFX8010',
                'category' => 'Harvesters',
                'listing_type' => 'sale',
                'price_usd' => 155000,
                'location' => 'Lagos, NG',
                'rating' => 4.9,
                'image_path' => 'images/equipment/case-ih-axial.jpg',
                'stock' => 1,
                'description' => 'High-capacity axial-flow combine',
            ],
            [
                'name' => 'Irrigation Pump Set',
                'category' => 'Irrigation',
                'listing_type' => 'sale',
                'price_usd' => 1200,
                'location' => 'Loko, NG',
                'rating' => 4.7,
                'image_path' => 'images/equipment/irrigation-pump.jpg',
                'stock' => 12,
                'description' => 'Diesel irrigation pump set',
            ],
            [
                'name' => 'Offset Disc Plough',
                'category' => 'Implements',
                'listing_type' => 'sale',
                'price_usd' => 2800,
                'location' => 'Benue, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/offset-disc-plough.jpg',
                'stock' => 7,
                'description' => '3-disc plough for land preparation',
            ],
            [
                'name' => 'Knapsack Sprayer 20L',
                'category' => 'Sprayers',
                'listing_type' => 'sale',
                'price_usd' => 95,
                'location' => 'Kaduna, NG',
                'rating' => 4.4,
                'image_path' => 'images/equipment/knapsack-sprayer.jpg',
                'stock' => 30,
                'description' => 'Manual knapsack sprayer',
            ],
            [
                'name' => 'Mobile Grain Mill',
                'category' => 'Processing',
                'listing_type' => 'sale',
                'price_usd' => 4200,
                'location' => 'Ogun, NG',
                'rating' => 4.6,
                'image_path' => 'images/equipment/mobile-grain-mill.jpg',
                'stock' => 4,
                'description' => 'Diesel grain mill for cooperatives',
            ],
            [
                'name' => 'Mechanic Tool Chest',
                'category' => 'Parts & Tools',
                'listing_type' => 'sale',
                'price_usd' => 450,
                'location' => 'Lagos, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/mechanic-tool-chest.jpg',
                'stock' => 18,
                'description' => 'Farm workshop tool set',
            ],
            [
                'name' => 'Solar Cold-Room Kit',
                'category' => 'Others',
                'listing_type' => 'sale',
                'price_usd' => 8900,
                'location' => 'Abuja, NG',
                'rating' => 4.6,
                'image_path' => 'images/equipment/solar-cold-room.jpg',
                'stock' => 3,
                'description' => 'Off-grid produce cooling kit',
            ],

            // For Rent — one listing per category
            [
                'name' => 'Massey Ferguson 375',
                'category' => 'Tractors',
                'listing_type' => 'rent',
                'price_usd' => 85,
                'location' => 'Ibadan, NG',
                'rating' => 4.6,
                'image_path' => 'images/equipment/massey-ferguson-375.jpg',
                'stock' => 5,
                'description' => 'Daily tractor rental',
            ],
            [
                'name' => 'Combine Hire TX Unit',
                'category' => 'Harvesters',
                'listing_type' => 'rent',
                'price_usd' => 220,
                'location' => 'Kano, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/new-holland-tx66.jpg',
                'stock' => 3,
                'description' => 'Daily combine hire with operator option',
            ],
            [
                'name' => 'Drip Irrigation Kit Hire',
                'category' => 'Irrigation',
                'listing_type' => 'rent',
                'price_usd' => 40,
                'location' => 'Loko, NG',
                'rating' => 4.4,
                'image_path' => 'images/equipment/drip-irrigation-kit.jpg',
                'stock' => 10,
                'description' => 'Seasonal drip kit rental',
            ],
            [
                'name' => 'Disc Harrow Set',
                'category' => 'Implements',
                'listing_type' => 'rent',
                'price_usd' => 55,
                'location' => 'Benue, NG',
                'rating' => 4.4,
                'image_path' => 'images/equipment/disc-harrow.jpg',
                'stock' => 6,
                'description' => 'Implement rental for land prep',
            ],
            [
                'name' => 'Boom Sprayer 600L',
                'category' => 'Sprayers',
                'listing_type' => 'rent',
                'price_usd' => 45,
                'location' => 'Kaduna, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/boom-sprayer.jpg',
                'stock' => 8,
                'description' => 'Daily sprayer rental',
            ],
            [
                'name' => 'Rice Mill Line Hire',
                'category' => 'Processing',
                'listing_type' => 'rent',
                'price_usd' => 150,
                'location' => 'Ebonyi, NG',
                'rating' => 4.3,
                'image_path' => 'images/equipment/rice-mill-line.jpg',
                'stock' => 2,
                'description' => 'Daily processing line rental',
            ],
            [
                'name' => 'Welding Plant Hire',
                'category' => 'Parts & Tools',
                'listing_type' => 'rent',
                'price_usd' => 35,
                'location' => 'Lagos, NG',
                'rating' => 4.2,
                'image_path' => 'images/equipment/welding-plant.jpg',
                'stock' => 9,
                'description' => 'Mobile welding plant rental',
            ],
            [
                'name' => 'Weighbridge Day Hire',
                'category' => 'Others',
                'listing_type' => 'rent',
                'price_usd' => 70,
                'location' => 'Oyo, NG',
                'rating' => 4.3,
                'image_path' => 'images/equipment/weighbridge.jpg',
                'stock' => 4,
                'description' => 'Portable weighbridge rental',
            ],

            // Spare Parts — one listing per category
            [
                'name' => 'Tractor Filter Kit',
                'category' => 'Tractors',
                'listing_type' => 'parts',
                'price_usd' => 95,
                'location' => 'Lagos, NG',
                'rating' => 4.6,
                'image_path' => 'images/equipment/tractor-filter-kit.jpg',
                'stock' => 40,
                'description' => 'OEM-compatible filter kit',
            ],
            [
                'name' => 'Harvester Belt Pack',
                'category' => 'Harvesters',
                'listing_type' => 'parts',
                'price_usd' => 180,
                'location' => 'Kano, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/harvester-belt-pack.jpg',
                'stock' => 25,
                'description' => 'Drive belts for common combines',
            ],
            [
                'name' => 'Pump Impeller Set',
                'category' => 'Irrigation',
                'listing_type' => 'parts',
                'price_usd' => 65,
                'location' => 'Loko, NG',
                'rating' => 4.4,
                'image_path' => 'images/equipment/pump-impeller-set.jpg',
                'stock' => 50,
                'description' => 'Replacement impellers for farm pumps',
            ],
            [
                'name' => 'Plough Share Blades',
                'category' => 'Implements',
                'listing_type' => 'parts',
                'price_usd' => 48,
                'location' => 'Benue, NG',
                'rating' => 4.3,
                'image_path' => 'images/equipment/plough-share-blades.jpg',
                'stock' => 60,
                'description' => 'Hardened plough shares',
            ],
            [
                'name' => 'Sprayer Nozzle Pack',
                'category' => 'Sprayers',
                'listing_type' => 'parts',
                'price_usd' => 28,
                'location' => 'Kaduna, NG',
                'rating' => 4.4,
                'image_path' => 'images/equipment/sprayer-nozzle-pack.jpg',
                'stock' => 80,
                'description' => 'Mixed nozzle set for boom sprayers',
            ],
            [
                'name' => 'Grain Mill Spare Stones',
                'category' => 'Processing',
                'listing_type' => 'parts',
                'price_usd' => 220,
                'location' => 'Ogun, NG',
                'rating' => 4.3,
                'image_path' => 'images/equipment/grain-mill-stones.jpg',
                'stock' => 15,
                'description' => 'Replacement milling stones',
            ],
            [
                'name' => 'Hydraulic Seal Kit',
                'category' => 'Parts & Tools',
                'listing_type' => 'parts',
                'price_usd' => 75,
                'location' => 'Lagos, NG',
                'rating' => 4.5,
                'image_path' => 'images/equipment/hydraulic-seal-kit.jpg',
                'stock' => 35,
                'description' => 'Universal hydraulic seal assortment',
            ],
            [
                'name' => 'Cold-Room Fan Motor',
                'category' => 'Others',
                'listing_type' => 'parts',
                'price_usd' => 140,
                'location' => 'Abuja, NG',
                'rating' => 4.2,
                'image_path' => 'images/equipment/cold-room-fan-motor.jpg',
                'stock' => 12,
                'description' => 'Spare evaporator fan motor',
            ],
        ];
    }

    protected function nextReference(User $user): string
    {
        $count = EquipmentOrder::query()->where('user_id', $user->id)->count() + 1;

        return 'EQ-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function usdToNgn(int $usd): int
    {
        return (int) max(1, round($usd * self::USD_TO_NGN));
    }

    protected function formatUsd(int $amount): string
    {
        return '$'.number_format($amount);
    }
}
