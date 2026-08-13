@props([
    'name',
    'location',
    'occupancy',
    'inventory' => [],
    'detailsUrl' => '#warehouse-details',
    'usedTons' => null,
    'capacityTons' => null,
])

<article class="rounded-2xl bg-cyra-surface/60 p-5 ring-1 ring-cyra-line/80 sm:p-6">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="text-lg font-extrabold tracking-tight text-cyra-ink">
                {{ $name }}
            </h3>
            <p class="mt-1 text-sm text-cyra-muted">{{ $location }}</p>
        </div>
        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-cyra-mint px-2.5 py-1 text-xs font-semibold text-cyra-forest">
            <span class="h-1.5 w-1.5 rounded-full bg-cyra-forest" aria-hidden="true"></span>
            Active
        </span>
    </div>

    <div class="mt-6">
        <div class="flex items-center justify-between gap-3 text-sm">
            <span class="font-medium text-cyra-muted">Occupancy</span>
            <span class="font-bold tabular-nums text-cyra-ink">
                {{ $occupancy }}%
                @if ($usedTons !== null && $capacityTons !== null)
                    <span class="font-medium text-cyra-muted">({{ number_format($usedTons) }}/{{ number_format($capacityTons) }} tons)</span>
                @endif
            </span>
        </div>
        <div
            class="mt-2 h-2.5 overflow-hidden rounded-full bg-cyra-mint"
            role="progressbar"
            aria-valuenow="{{ $occupancy }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="Warehouse occupancy"
        >
            <div
                class="h-full rounded-full bg-gradient-to-r from-cyra-forest to-cyra-green transition-all duration-500"
                style="width: {{ max(0, min(100, (int) $occupancy)) }}%"
            ></div>
        </div>
    </div>

    <div class="mt-7">
        <h4 class="text-sm font-extrabold text-cyra-ink sm:text-base">Inventory Summary</h4>
        <ul class="mt-1 divide-y divide-cyra-line/80">
            @forelse ($inventory as $item)
                <x-warehouse.inventory-item
                    :name="$item['name']"
                    :quantity="$item['quantity']"
                    :icon="$item['icon']"
                />
            @empty
                <li class="py-4 text-sm text-cyra-muted">No stock recorded yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        <a
            href="{{ $detailsUrl }}#stock-in"
            class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
        >
            Stock In
        </a>
        <a
            href="{{ $detailsUrl }}"
            class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line transition hover:bg-cyra-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
        >
            View Details
        </a>
    </div>
</article>
