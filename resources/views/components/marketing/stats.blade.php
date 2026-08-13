@props([
    'stats' => null,
])

@php
    $items = $stats ?? [
        ['value' => '0', 'label' => __('home.stats.farmers'), 'icon' => 'farmers'],
        ['value' => '0', 'label' => __('home.stats.investors'), 'icon' => 'investors'],
        ['value' => '0', 'label' => __('home.stats.buyers'), 'icon' => 'buyers'],
        ['value' => '0', 'label' => __('home.stats.listings'), 'icon' => 'listings'],
        ['value' => '0', 'label' => __('home.stats.farms'), 'icon' => 'farms'],
    ];

    $icons = [
        'farmers' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m10-5.13a4 4 0 11-8 0 4 4 0 018 0zM7 10a3 3 0 11-6 0 3 3 0 016 0z" />',
        'investors' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 8v4m2-2h-4" />',
        'buyers' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.1.4 1.1H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />',
        'listings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.6M20 20v-5h-.6M5.1 9A7 7 0 0119.5 8M18.9 15A7 7 0 014.5 16" />',
        'farms' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z" />',
    ];
@endphp

<section class="relative z-20 -mt-10 px-4 sm:-mt-12 sm:px-6 lg:-mt-14 lg:px-8 cyra-reveal" x-data="cyraReveal" aria-label="{{ __('home.stats.aria') }}">
    <div class="cyra-container">
        <div class="rounded-2xl bg-cyra-card px-2 py-4 shadow-stats ring-1 ring-cyra-line/80 sm:px-4 sm:py-5">
            <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x lg:divide-cyra-line/80">
                @foreach ($items as $stat)
                    @php
                        $iconKey = $stat['icon'] ?? 'farmers';
                    @endphp
                    <div class="flex items-center gap-3 px-3 py-3 sm:justify-center sm:px-4">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest sm:h-10 sm:w-10" aria-hidden="true">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                {!! $icons[$iconKey] ?? $icons['farmers'] !!}
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <dd class="font-display text-base font-extrabold tracking-tight text-cyra-ink sm:text-xl">
                                {{ $stat['value'] }}
                            </dd>
                            <dt class="truncate text-xs font-medium text-cyra-muted sm:text-sm">{{ $stat['label'] }}</dt>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>
