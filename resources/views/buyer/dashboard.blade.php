<x-dashboard-layout
    title="Buyer Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Overview'],
    ]"
>
    <x-page-header
        title="Welcome back, {{ $greetingName }}"
        description="Here's your procurement overview"
    />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Procurement metrics">
        @foreach ($stats as $stat)
            <x-dashboard.stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :meta="$stat['meta'] ?? null"
                :meta-tone="$stat['meta_tone'] ?? 'amber'"
            />
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-5" aria-label="Orders and analytics">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Recent Orders</h2>
            </div>

            <ul class="mt-2 divide-y divide-cyra-line/90">
                @foreach ($recentOrders as $order)
                    <x-buyer.order-row
                        :product="$order['product']"
                        :supplier="$order['supplier']"
                        :quantity="$order['quantity']"
                        :status="$order['status']"
                        :status-tone="$order['status_tone']"
                        :image="$order['image']"
                    />
                @endforeach
            </ul>

            <div class="mt-3">
                <a
                    href="#orders"
                    class="text-sm font-semibold text-cyra-forest transition hover:text-cyra-green"
                >
                    View All Orders
                </a>
            </div>
        </article>

        <div class="flex flex-col gap-5 xl:col-span-2">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Spend Analytics</h2>
                <div class="mt-4 h-48 sm:h-52">
                    <canvas
                        id="buyerSpendChart"
                        data-labels='@json($spend['labels'])'
                        data-values='@json($spend['values'])'
                        aria-label="Spend analytics from January to June"
                        role="img"
                    ></canvas>
                </div>
            </article>

            <article class="flex flex-1 flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Favorite Suppliers</h2>
                <ul class="mt-1 flex-1 divide-y divide-cyra-line/90">
                    @foreach ($favoriteSuppliers as $supplier)
                        <x-buyer.supplier-row
                            :name="$supplier['name']"
                            :badge="$supplier['badge']"
                            :rating="$supplier['rating']"
                            :image="$supplier['image']"
                        />
                    @endforeach
                </ul>
                <div class="mt-3">
                    <a
                        href="{{ route('marketplace.index') }}"
                        class="text-sm font-semibold text-cyra-forest transition hover:text-cyra-green"
                    >
                        View All Suppliers
                    </a>
                </div>
            </article>
        </div>
    </section>
</x-dashboard-layout>
