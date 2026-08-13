@props([
    'action' => null,
    'placeholder' => 'Search CyraAgroLink...',
    'preserve' => [],
])

@php
    $action = $action ?: route('marketplace.index');
@endphp

<form
    method="GET"
    action="{{ $action }}"
    role="search"
    {{ $attributes->merge(['class' => 'relative min-w-0 flex-1 md:max-w-md']) }}
>
    @foreach ($preserve as $name => $value)
        @if (filled($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-cyra-muted" aria-hidden="true">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
        </svg>
    </span>
    <input
        type="search"
        name="q"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder }}"
        aria-label="{{ $placeholder }}"
        class="w-full rounded-xl border-0 bg-cyra-card py-2.5 pl-10 pr-[4.5rem] text-sm text-cyra-ink placeholder:text-cyra-muted shadow-sm ring-1 ring-cyra-line transition focus:outline-none focus:ring-2 focus:ring-cyra-forest/25"
    >
    <button
        type="submit"
        class="absolute inset-y-1.5 right-1.5 rounded-lg bg-cyra-forest px-3 text-xs font-bold text-white transition hover:bg-cyra-green"
    >
        {{ __('ui.search') }}
    </button>
</form>
