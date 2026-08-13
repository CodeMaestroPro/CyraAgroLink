@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview'],
        ['id' => 'financial', 'label' => 'Financial Reports', 'href' => '#financial'],
        ['id' => 'operations', 'label' => 'Operations', 'href' => '#operations'],
        ['id' => 'investments', 'label' => 'Investments', 'href' => route('investments.index')],
        ['id' => 'marketplace', 'label' => 'Marketplace', 'href' => route('marketplace.index')],
        ['id' => 'logistics', 'label' => 'Logistics', 'href' => route('logistics.index')],
        ['id' => 'experts', 'label' => 'Experts', 'href' => '#experts'],
        ['id' => 'custom', 'label' => 'Custom Reports', 'href' => '#custom'],
        ['id' => 'export', 'label' => 'Data Export', 'href' => '#export'],
    ];
@endphp

<aside class="flex w-full flex-col bg-gradient-to-b from-cyra-forest to-[#0A5C2E] lg:w-56 xl:w-60">
    <div class="px-4 py-4">
        <p class="text-sm font-extrabold text-white">Analytics</p>
        <p class="mt-0.5 text-[11px] font-medium text-white/70">Business insights</p>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-4 lg:overflow-y-auto" aria-label="Reporting sections">
        <div class="flex gap-1 lg:block lg:space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([
                        'inline-flex shrink-0 items-center rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:w-full',
                        'bg-white/20 text-white shadow-sm' => $item['id'] === $active,
                        'text-white/80 hover:bg-white/10 hover:text-white' => $item['id'] !== $active,
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    <div class="border-t border-white/15 p-3 sm:p-4">
        <a
            href="#export"
            class="inline-flex w-full items-center justify-center rounded-xl border border-white/40 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
        >
            Export Report
        </a>
    </div>
</aside>
