@props([
    'steps' => [],
    'current' => 1,
])

<nav class="mx-auto w-full max-w-3xl px-2" aria-label="Registration progress">
    <ol class="relative flex items-start justify-between">
        <li class="pointer-events-none absolute left-[10%] right-[10%] top-4 h-px bg-cyra-line" aria-hidden="true"></li>

        @foreach ($steps as $stepItem)
            @php
                $number = (int) $stepItem['number'];
                $isActive = $number === (int) $current;
                $isComplete = $number < (int) $current;
            @endphp

            <li class="relative z-10 flex w-16 flex-col items-center text-center sm:w-20">
                <span
                    @class([
                        'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold ring-4 ring-white',
                        'bg-cyra-forest text-white' => $isActive || $isComplete,
                        'bg-white text-cyra-muted ring-1 ring-cyra-line' => ! $isActive && ! $isComplete,
                    ])
                    @if ($isActive) aria-current="step" @endif
                >
                    {{ $number }}
                </span>
                <span
                    @class([
                        'mt-2 text-xs font-semibold sm:text-sm',
                        'text-cyra-forest' => $isActive || $isComplete,
                        'text-cyra-muted' => ! $isActive && ! $isComplete,
                    ])
                >
                    {{ $stepItem['label'] }}
                </span>
            </li>
        @endforeach
    </ol>
</nav>
