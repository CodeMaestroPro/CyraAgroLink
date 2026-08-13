@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview'],
        ['id' => 'loan-applications', 'label' => 'Loan Applications', 'href' => '#loan-applications'],
        ['id' => 'loan-portfolio', 'label' => 'Loan Portfolio', 'href' => '#loan-portfolio'],
        ['id' => 'repayments', 'label' => 'Repayments', 'href' => '#repayments'],
        ['id' => 'risk-assessment', 'label' => 'Risk Assessment', 'href' => '#risk-assessment'],
        ['id' => 'farmers', 'label' => 'Farmers', 'href' => '#farmers'],
        ['id' => 'reports', 'label' => 'Reports', 'href' => route('reporting.analytics')],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit')],
    ];
@endphp

<aside class="flex w-full flex-col bg-gradient-to-b from-cyra-forest to-[#0A5C2E] lg:w-52 xl:w-56">
    <div class="flex items-center gap-2.5 px-4 py-4">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-cyra-soft">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M5 10V8a2 2 0 012-2h10a2 2 0 012 2v2M5 10v8a2 2 0 002 2h10a2 2 0 002-2v-8M9 14h.01M15 14h.01"/>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold text-white">Lending</p>
            <p class="truncate text-[11px] font-medium text-white/70">Institution desk</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-5 lg:overflow-y-auto" aria-label="Financial institution dashboard sections">
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
</aside>
