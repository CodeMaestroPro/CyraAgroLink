@props([
    'steps' => [],
])

@php
    $count = max(1, count($steps));
@endphp

<ol
    class="relative grid gap-2 sm:gap-3"
    style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr));"
    aria-label="Supply chain progress"
>
    @foreach ($steps as $index => $step)
        @php
            $complete = (bool) ($step['complete'] ?? false);
            $isLast = $index === count($steps) - 1;
        @endphp
        <li class="relative flex flex-col items-center text-center">
            @unless ($isLast)
                <span
                    @class([
                        'absolute left-[calc(50%+1.1rem)] right-[calc(-50%+1.1rem)] top-5 hidden h-0.5 sm:block',
                        'bg-cyra-forest' => $complete,
                        'bg-cyra-line' => ! $complete,
                    ])
                    aria-hidden="true"
                ></span>
            @endunless

            <span
                @class([
                    'relative z-10 inline-flex h-10 w-10 items-center justify-center rounded-full shadow-sm',
                    'bg-cyra-forest text-white' => $complete,
                    'bg-white text-cyra-muted ring-2 ring-cyra-line' => ! $complete,
                ])
            >
                @switch($step['icon'] ?? 'harvested')
                    @case('picked_up')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3V7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10h4l3 3v4h-7v-7z"/>
                            <circle cx="7" cy="18.5" r="1.2" fill="currentColor" stroke="none"/>
                            <circle cx="17.5" cy="18.5" r="1.2" fill="currentColor" stroke="none"/>
                        </svg>
                        @break
                    @case('in_transit')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12h12M12 8l4 4-4 4M20 7v10"/>
                        </svg>
                        @break
                    @case('warehouse')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 20V9.5L12 4l9 5.5V20"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20v-6h6v6"/>
                        </svg>
                        @break
                    @case('delivered')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10.5 12 4l8 6.5V20H4v-9.5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20v-6h6v6"/>
                        </svg>
                        @break
                    @default
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c0 4-3 7-7 7 4 0 7 3 7 7 0-4 3-7 7-7-4 0-7-3-7-7z"/>
                        </svg>
                @endswitch
            </span>

            <p class="mt-3 text-[11px] font-bold leading-tight text-cyra-ink sm:text-sm">
                {{ $step['label'] }}
            </p>
            <p class="mt-1 text-[10px] leading-tight text-cyra-muted sm:text-xs">
                {{ $step['location'] }}
            </p>
            <p class="mt-0.5 text-[10px] leading-tight text-cyra-muted sm:text-xs">
                {{ $step['date'] }}
            </p>
        </li>
    @endforeach
</ol>
