@props([
    'active',
    'items' => [],
])

@php
    /** @var list<array{id: string, label: string, href: string}> $items */
@endphp

<nav {{ $attributes->merge(['class' => 'min-w-0']) }} aria-label="Messaging sections">
    <div class="cyra-tabs w-full justify-start gap-1 p-1.5 sm:w-auto">
        @foreach ($items as $item)
            @php $isActive = ($active ?? null) === ($item['id'] ?? null); @endphp
            <a
                href="{{ $item['href'] }}"
                @class([
                    'cyra-tab',
                    'cyra-tab-active' => $isActive,
                    'cyra-tab-idle' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
