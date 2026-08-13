@props([
    'items' => [],
    'active' => null,
])

@php
    /** @var list<array{id: string, label: string, href?: string|null}> $items */
@endphp

@if (count($items) > 0)
    <div {{ $attributes->merge(['class' => 'mb-5 sm:mb-6 border-b border-cyra-line']) }} role="tablist" aria-label="Section navigation">
        <div class="-mb-px flex gap-0.5 overflow-x-auto overscroll-x-contain pb-px [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-1">
            @foreach ($items as $item)
                @php
                    $isActive = ($active ?? null) === ($item['id'] ?? null);
                    $href = $item['href'] ?? ('#'.$item['id']);
                @endphp
                <a
                    href="{{ $href }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    @class([
                        'inline-flex shrink-0 items-center whitespace-nowrap border-b-2 px-2.5 py-2.5 text-[13px] font-semibold transition sm:px-3 sm:text-sm',
                        'border-cyra-forest text-cyra-forest' => $isActive,
                        'border-transparent text-cyra-muted hover:border-cyra-line hover:text-cyra-ink' => ! $isActive,
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
