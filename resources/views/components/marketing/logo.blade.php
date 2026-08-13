@props([
    'class' => 'h-10 w-10',
    'showTagline' => true,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img
        src="{{ asset('images/logo.svg') }}"
        alt=""
        class="{{ $class }}"
        width="40"
        height="40"
        aria-hidden="true"
    >

    <span class="leading-tight">
        <span class="block font-display text-lg font-extrabold tracking-tight text-cyra-forest sm:text-xl">
            {{ config('cyra.brand') }}
        </span>
        @if ($showTagline)
            <span class="block text-[11px] font-medium tracking-wide text-cyra-muted sm:text-xs">
                Connect. Grow. Prosper.
            </span>
        @endif
    </span>
</span>
