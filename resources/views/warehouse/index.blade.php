<x-dashboard-layout
    title="Warehouse Management"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Logistics', 'href' => route('logistics.index')],
        ['label' => 'Warehouse Management'],
    ]"
>
    <x-page-header
        title="My Warehouses"
        description="Monitor capacity and stock across your storage facilities"
    />

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'list', 'label' => 'Warehouses', 'href' => route('warehouse.index', ['tab' => 'list'])],
            ['id' => 'details', 'label' => 'Details', 'href' => route('warehouse.index', array_filter(['tab' => 'details', 'warehouse' => $selected['id'] ?? null]))],
        ]"
    />

    @if ($tab === 'list')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="space-y-5" aria-label="Warehouse list">
                @forelse ($warehouses as $warehouse)
                    <x-warehouse.warehouse-card
                        :name="$warehouse['name']"
                        :location="$warehouse['location']"
                        :occupancy="$warehouse['occupancy']"
                        :inventory="$warehouse['inventory']"
                        :details-url="$warehouse['details_url']"
                        :used-tons="$warehouse['used_tons']"
                        :capacity-tons="$warehouse['capacity_tons']"
                    />
                @empty
                    <p class="rounded-2xl bg-white px-5 py-8 text-sm text-cyra-muted ring-1 ring-cyra-line">
                        No warehouses registered yet.
                    </p>
                @endforelse
            </section>

            <aside class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink">Register warehouse</h2>
                <p class="mt-1 text-sm text-cyra-muted">Add a new storage facility to your network.</p>

                <form method="POST" action="{{ route('warehouse.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="city" class="mb-1.5 block text-sm font-semibold text-cyra-ink">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}" required maxlength="80"
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <label for="state" class="mb-1.5 block text-sm font-semibold text-cyra-ink">State</label>
                        <input id="state" type="text" name="state" value="{{ old('state') }}" required maxlength="80"
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <x-input-error :messages="$errors->get('state')" class="mt-2" />
                    </div>
                    <div>
                        <label for="capacity_tons" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Capacity (tons)</label>
                        <input id="capacity_tons" type="number" name="capacity_tons" value="{{ old('capacity_tons', 500) }}" min="10" required
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <x-input-error :messages="$errors->get('capacity_tons')" class="mt-2" />
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">
                        Register warehouse
                    </button>
                </form>
            </aside>
        </div>
    @elseif ($selected)
        <div id="warehouse-details" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-5">
                <x-warehouse.warehouse-card
                    :name="$selected['name']"
                    :location="$selected['location']"
                    :occupancy="$selected['occupancy']"
                    :inventory="$selected['inventory']"
                    :details-url="$selected['details_url']"
                    :used-tons="$selected['used_tons']"
                    :capacity-tons="$selected['capacity_tons']"
                />

                <section class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                    <h2 class="text-base font-extrabold text-cyra-ink">Stock lines</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Release inventory when goods leave the facility.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($selected['inventory'] as $item)
                            <article class="flex flex-wrap items-end justify-between gap-3 rounded-xl bg-cyra-surface/70 px-4 py-3 ring-1 ring-cyra-line/70">
                                <div>
                                    <p class="text-sm font-bold text-cyra-ink">{{ $item['name'] }}</p>
                                    <p class="mt-0.5 text-xs text-cyra-muted">{{ $item['quantity'] }} on hand</p>
                                </div>
                                <form method="POST" action="{{ route('warehouse.stock.release', $item['id']) }}" class="flex items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-cyra-muted">Release tons</label>
                                        <input
                                            type="number"
                                            name="quantity_tons"
                                            min="1"
                                            max="{{ $item['quantity_raw'] }}"
                                            value="1"
                                            required
                                            class="w-24 rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                        >
                                    </div>
                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                        Stock out
                                    </button>
                                </form>
                            </article>
                        @empty
                            <p class="text-sm text-cyra-muted">No stock lines yet. Receive stock to begin.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                    <h2 class="text-base font-extrabold text-cyra-ink">Recent movements</h2>
                    <ul class="mt-4 divide-y divide-cyra-line/80">
                        @forelse ($movements as $movement)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-cyra-ink">
                                        {{ $movement['label'] }} · {{ $movement['commodity'] }}
                                    </p>
                                    <p class="text-xs text-cyra-muted">{{ $movement['note'] }} · {{ $movement['time'] }}</p>
                                </div>
                                <span @class([
                                    'font-bold tabular-nums',
                                    'text-cyra-forest' => $movement['type'] === 'in',
                                    'text-rose-600' => $movement['type'] === 'out',
                                ])>
                                    {{ $movement['type'] === 'in' ? '+' : '-' }}{{ $movement['quantity'] }}
                                </span>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-cyra-muted">No movements yet.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <aside class="space-y-5">
                <div
                    id="stock-in"
                    class="scroll-mt-24 rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6"
                    x-data="{
                        commodity: @js(old('commodity_name', $selected['inventory'][0]['name'] ?? ($commodity_options[0] ?? 'Maize'))),
                        init() {
                            const known = @js(array_values(array_unique(array_merge(
                                $commodity_options,
                                collect($selected['inventory'])->pluck('name')->all()
                            ))));
                            if (this.commodity !== '' && this.commodity !== '__custom__' && !known.includes(this.commodity)) {
                                this.$nextTick(() => {
                                    if (this.$refs.customInput) {
                                        this.$refs.customInput.value = this.commodity;
                                    }
                                });
                                this.commodity = '__custom__';
                            }
                        }
                    }"
                >
                    <h2 class="text-base font-extrabold text-cyra-ink">Stock In</h2>
                    <p class="mt-1 text-sm text-cyra-muted">
                        {{ number_format($selected['free_tons']) }} tons free of {{ number_format($selected['capacity_tons']) }}
                        · Current occupancy {{ $selected['occupancy'] }}%
                    </p>

                    @if ($selected['free_tons'] < 1)
                        <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
                            This warehouse is full. Release stock before stocking in.
                        </div>
                    @else
                        <form method="POST" action="{{ route('warehouse.stock.receive', $selected['id']) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label for="commodity_name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Commodity</label>
                                <select
                                    id="commodity_name"
                                    name="commodity_name"
                                    x-model="commodity"
                                    required
                                    class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                                    @foreach ($selected['inventory'] as $item)
                                        <option value="{{ $item['name'] }}">{{ $item['name'] }} (on hand: {{ $item['quantity'] }})</option>
                                    @endforeach
                                    @foreach ($commodity_options as $option)
                                        @if (! collect($selected['inventory'])->contains(fn ($item) => $item['name'] === $option))
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endif
                                    @endforeach
                                    <option value="__custom__">Other commodity…</option>
                                </select>
                                <x-input-error :messages="$errors->get('commodity_name')" class="mt-2" />
                            </div>

                            <div x-show="commodity === '__custom__'" x-cloak>
                                <label for="custom_commodity_name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Custom commodity</label>
                                <input
                                    id="custom_commodity_name"
                                    type="text"
                                    name="custom_commodity_name"
                                    x-ref="customInput"
                                    value="{{ old('custom_commodity_name', old('commodity_name')) }}"
                                    maxlength="120"
                                    :required="commodity === '__custom__'"
                                    placeholder="e.g. Groundnut"
                                    class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                            </div>

                            <div>
                                <label for="quantity_tons" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Quantity (tons)</label>
                                <input
                                    id="quantity_tons"
                                    type="number"
                                    name="quantity_tons"
                                    min="1"
                                    max="{{ $selected['free_tons'] }}"
                                    value="{{ old('quantity_tons', min(10, $selected['free_tons'])) }}"
                                    required
                                    class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                                <p class="mt-1 text-xs text-cyra-muted">Maximum {{ number_format($selected['free_tons']) }} tons.</p>
                                <x-input-error :messages="$errors->get('quantity_tons')" class="mt-2" />
                            </div>

                            <div>
                                <label for="source" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Source / supplier (optional)</label>
                                <input
                                    id="source"
                                    type="text"
                                    name="source"
                                    value="{{ old('source') }}"
                                    maxlength="120"
                                    placeholder="e.g. Green Valley Farm"
                                    class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                                <x-input-error :messages="$errors->get('source')" class="mt-2" />
                            </div>

                            <div>
                                <label for="note" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Note (optional)</label>
                                <input
                                    id="note"
                                    type="text"
                                    name="note"
                                    value="{{ old('note') }}"
                                    maxlength="255"
                                    placeholder="e.g. Truck arrival batch A12"
                                    class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                                <x-input-error :messages="$errors->get('note')" class="mt-2" />
                            </div>

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green"
                            >
                                Confirm stock in
                            </button>
                        </form>
                    @endif
                </div>

                @if (count($warehouses) > 1)
                    <div class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                        <h2 class="text-sm font-extrabold text-cyra-ink">Switch warehouse</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($warehouses as $warehouse)
                                <li>
                                    <a
                                        href="{{ route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouse['id']]) }}"
                                        @class([
                                            'block rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition',
                                            'bg-cyra-mint text-cyra-forest ring-cyra-forest/30' => $warehouse['id'] === $selected['id'],
                                            'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-surface' => $warehouse['id'] !== $selected['id'],
                                        ])
                                    >
                                        {{ $warehouse['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    @else
        <p class="rounded-2xl bg-white px-5 py-8 text-sm text-cyra-muted ring-1 ring-cyra-line">
            Select a warehouse to view details.
        </p>
    @endif
</x-dashboard-layout>
