@props([
    'steps' => [],
])

<ol class="relative space-y-0" aria-label="Shipment progress">
    @foreach ($steps as $index => $step)
        @php
            $isLast = $index === count($steps) - 1;
            $complete = (bool) ($step['complete'] ?? false);
        @endphp
        <li class="relative flex gap-3.5 pb-6 last:pb-0">
            @unless ($isLast)
                <span
                    @class([
                        'absolute left-[11px] top-6 h-[calc(100%-0.75rem)] w-0.5',
                        'bg-cyra-forest' => $complete,
                        'bg-cyra-line' => ! $complete,
                    ])
                    aria-hidden="true"
                ></span>
            @endunless

            <span
                @class([
                    'relative z-10 mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full',
                    'bg-cyra-forest text-white shadow-sm' => $complete,
                    'bg-white text-cyra-muted ring-2 ring-cyra-line' => ! $complete,
                ])
            >
                @if ($complete)
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    <span class="h-2 w-2 rounded-full bg-cyra-line"></span>
                @endif
            </span>

            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-cyra-ink">{{ $step['label'] }}</p>
                        <p class="mt-0.5 text-sm text-cyra-muted">{{ $step['detail'] }}</p>
                    </div>
                    <p class="shrink-0 text-xs font-medium text-cyra-muted">{{ $step['time'] }}</p>
                </div>
            </div>
        </li>
    @endforeach
</ol>
