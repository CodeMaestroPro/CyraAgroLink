<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Exceptions\BusinessLogicException;
use App\Models\ConsumerCartItem;
use App\Models\ConsumerOrder;
use App\Models\ConsumerOrderItem;
use App\Models\ConsumerProduct;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Consumer retail storefront: catalog, cart, and checkout.
 */
class ConsumerMarketplaceService
{
    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStorefrontData(
        User $user,
        ?string $query = null,
        ?string $category = null,
        string $view = 'shop'
    ): array {
        $this->warmCatalog();

        $view = in_array($view, ['shop', 'cart', 'orders'], true) ? $view : 'shop';
        $search = trim((string) $query);
        $categorySlug = trim((string) $category) ?: null;

        $products = $this->filterProducts($search, $categorySlug);
        $cartItems = $this->cartItemsFor($user);
        $cartCount = (int) $cartItems->sum('quantity');
        $cartTotal = (int) $cartItems->sum(fn (ConsumerCartItem $item) => $item->lineTotal());
        $walletBalance = $this->walletService->getBalance($user);

        $orders = ConsumerOrder::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->latest('id')
            ->limit(30)
            ->get();

        return [
            'query' => $search,
            'category' => $categorySlug,
            'view' => $view,
            'categories' => $this->categories(),
            'products' => $products,
            'cart_items' => $cartItems,
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
            'wallet_balance' => $walletBalance,
            'wallet_can_pay_cart' => $walletBalance >= $cartTotal && $cartTotal > 0,
            'orders' => $orders,
            'orders_count' => $orders->count(),
            'notifications_count' => max(3, $cartCount + $orders->where('status', 'pending')->count()),
        ];
    }

    /**
     * Add a product to the user's cart (or increase quantity).
     */
    public function addToCart(User $user, ConsumerProduct $product, int $quantity = 1): ConsumerCartItem
    {
        $this->assertProductAvailable($product);

        $quantity = max(1, $quantity);
        $existing = ConsumerCartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        $nextQty = ($existing?->quantity ?? 0) + $quantity;

        if ($nextQty > $product->stock_qty) {
            throw new BusinessLogicException('Not enough stock for this product.');
        }

        if ($existing) {
            $existing->forceFill(['quantity' => $nextQty])->save();

            return $existing->refresh();
        }

        return ConsumerCartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Update cart line quantity.
     */
    public function updateCartItem(User $user, ConsumerCartItem $item, int $quantity): ConsumerCartItem
    {
        $this->assertOwnedCartItem($user, $item);

        $product = $item->product;
        if (! $product) {
            throw new BusinessLogicException('Product is no longer available.');
        }

        if ($quantity < 1) {
            $item->delete();

            return $item;
        }

        if ($quantity > $product->stock_qty) {
            throw new BusinessLogicException('Not enough stock for this product.');
        }

        $item->forceFill(['quantity' => $quantity])->save();

        return $item->refresh();
    }

    /**
     * Remove a cart line.
     */
    public function removeCartItem(User $user, ConsumerCartItem $item): void
    {
        $this->assertOwnedCartItem($user, $item);
        $item->delete();
    }

    /**
     * Checkout the current cart into a pending order.
     *
     * @param  array{delivery_note?: string|null}  $data
     */
    public function checkout(User $user, array $data = []): ConsumerOrder
    {
        return DB::transaction(function () use ($user, $data): ConsumerOrder {
            $items = ConsumerCartItem::query()
                ->where('user_id', $user->id)
                ->with('product')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new BusinessLogicException('Your cart is empty.');
            }

            $total = 0;
            $lines = [];

            foreach ($items as $item) {
                /** @var ConsumerProduct|null $product */
                $product = ConsumerProduct::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product || ! $product->inStock()) {
                    throw new BusinessLogicException("{$item->product?->name} is unavailable.");
                }

                if ($item->quantity > $product->stock_qty) {
                    throw new BusinessLogicException("Not enough stock for {$product->name}.");
                }

                $lineTotal = $item->quantity * $product->price_per_unit;
                $total += $lineTotal;
                $lines[] = [
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $order = ConsumerOrder::query()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $total,
                'delivery_note' => filled($data['delivery_note'] ?? null)
                    ? trim((string) $data['delivery_note'])
                    : null,
            ]);

            foreach ($lines as $line) {
                /** @var ConsumerProduct $product */
                $product = $line['product'];

                ConsumerOrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'unit_price' => $product->price_per_unit,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $product->forceFill([
                    'stock_qty' => $product->stock_qty - $line['quantity'],
                ])->save();
            }

            ConsumerCartItem::query()->where('user_id', $user->id)->delete();

            return $order->load('items');
        });
    }

    /**
     * Cancel a pending consumer order and restock.
     */
    public function cancelOrder(User $user, ConsumerOrder $order): ConsumerOrder
    {
        $this->assertOwnedOrder($user, $order);

        if ($order->status !== 'pending') {
            throw new BusinessLogicException('Only pending orders can be cancelled.');
        }

        return DB::transaction(function () use ($order): ConsumerOrder {
            $locked = ConsumerOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $locked->items()->get();

            foreach ($items as $item) {
                if ($item->product_id) {
                    ConsumerProduct::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->increment('stock_qty', $item->quantity);
                }
            }

            $locked->forceFill(['status' => 'cancelled'])->save();

            return $locked->refresh();
        });
    }

    /**
     * Pay a pending order from the buyer's funded wallet.
     */
    public function confirmOrder(User $user, ConsumerOrder $order): ConsumerOrder
    {
        $this->assertOwnedOrder($user, $order);

        if ($order->status !== 'pending') {
            throw new BusinessLogicException('Only pending orders can be paid.');
        }

        return DB::transaction(function () use ($user, $order): ConsumerOrder {
            $locked = ConsumerOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw new BusinessLogicException('Only pending orders can be paid.');
            }

            $this->walletService->payForPurchase(
                $user,
                (int) $locked->total_amount,
                $locked,
                'Consumer order #'.$locked->id
            );

            $locked->forceFill(['status' => 'paid'])->save();

            return $locked->refresh();
        });
    }

    /**
     * @return Collection<int, ConsumerProduct>
     */
    protected function filterProducts(string $search, ?string $category): Collection
    {
        return ConsumerProduct::query()
            ->where('is_active', true)
            ->when(
                $category !== null,
                fn ($q) => $q->where('category', $category)
            )
            ->when(
                $search !== '',
                fn ($q) => $q->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                })
            )
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ConsumerCartItem>
     */
    protected function cartItemsFor(User $user): Collection
    {
        return ConsumerCartItem::query()
            ->where('user_id', $user->id)
            ->with('product')
            ->latest('id')
            ->get();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    protected function categories(): array
    {
        return [
            ['id' => 'grains', 'label' => 'Grains'],
            ['id' => 'fruits', 'label' => 'Fruits'],
            ['id' => 'vegetables', 'label' => 'Vegetables'],
            ['id' => 'oils', 'label' => 'Oils'],
            ['id' => 'organic', 'label' => 'Organic'],
            ['id' => 'others', 'label' => 'Others'],
        ];
    }

    protected function warmCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (ConsumerProduct::query()->exists()) {
            return;
        }

        foreach ($this->seedProducts() as $row) {
            ConsumerProduct::query()->create([
                'name' => $row['name'],
                'slug' => Str::slug($row['name']),
                'category' => $row['category'],
                'unit' => $row['unit'],
                'price_per_unit' => $row['price'],
                'stock_qty' => $row['stock'],
                'image_path' => $row['image'],
                'description' => $row['description'],
                'is_active' => true,
                'is_featured' => $row['featured'],
            ]);
        }
    }

    /**
     * @return list<array{name: string, category: string, unit: string, price: int, stock: int, image: string, description: string, featured: bool}>
     */
    protected function seedProducts(): array
    {
        return [
            [
                'name' => 'Rice (Ofada)',
                'category' => 'grains',
                'unit' => 'kg',
                'price' => 1200,
                'stock' => 500,
                'image' => 'images/consumer/rice-ofada.jpg',
                'description' => 'Local Ofada rice from Southwest farms.',
                'featured' => true,
            ],
            [
                'name' => 'Honey (Raw)',
                'category' => 'organic',
                'unit' => 'kg',
                'price' => 3500,
                'stock' => 120,
                'image' => 'images/consumer/honey-raw.jpg',
                'description' => 'Unprocessed raw honey, naturally filtered.',
                'featured' => true,
            ],
            [
                'name' => 'Palm Oil',
                'category' => 'oils',
                'unit' => 'L',
                'price' => 2000,
                'stock' => 300,
                'image' => 'images/consumer/palm-oil.jpg',
                'description' => 'Fresh red palm oil for everyday cooking.',
                'featured' => true,
            ],
            [
                'name' => 'Yam Flour',
                'category' => 'grains',
                'unit' => 'kg',
                'price' => 2500,
                'stock' => 200,
                'image' => 'images/consumer/yam-flour.jpg',
                'description' => 'Smooth yam flour for amala and swallows.',
                'featured' => true,
            ],
            [
                'name' => 'Fresh Tomatoes',
                'category' => 'vegetables',
                'unit' => 'kg',
                'price' => 800,
                'stock' => 400,
                'image' => 'images/consumer/tomatoes.jpg',
                'description' => 'Firm vine tomatoes from local growers.',
                'featured' => false,
            ],
            [
                'name' => 'Sweet Oranges',
                'category' => 'fruits',
                'unit' => 'kg',
                'price' => 1500,
                'stock' => 250,
                'image' => 'images/consumer/oranges.jpg',
                'description' => 'Juicy sweet oranges for juice and snacking.',
                'featured' => false,
            ],
            [
                'name' => 'Plantain',
                'category' => 'fruits',
                'unit' => 'kg',
                'price' => 900,
                'stock' => 350,
                'image' => 'images/consumer/plantain.jpg',
                'description' => 'Ripe plantain for frying and roasting.',
                'featured' => false,
            ],
            [
                'name' => 'Groundnut Oil',
                'category' => 'oils',
                'unit' => 'L',
                'price' => 2800,
                'stock' => 180,
                'image' => 'images/consumer/groundnut-oil.jpg',
                'description' => 'Cold-pressed groundnut cooking oil.',
                'featured' => false,
            ],
        ];
    }

    protected function assertProductAvailable(ConsumerProduct $product): void
    {
        if (! $product->inStock()) {
            throw new BusinessLogicException('This product is out of stock.');
        }
    }

    protected function assertOwnedCartItem(User $user, ConsumerCartItem $item): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $item->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own cart.', 'CART_FORBIDDEN', 403);
        }
    }

    protected function assertOwnedOrder(User $user, ConsumerOrder $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $order->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own orders.', 'ORDER_FORBIDDEN', 403);
        }
    }
}
