@php
    $locales = config('cyra.locales', []);
    $labels = config('cyra.locale_labels', []);
    $currentLocale = app()->getLocale();
    $currentLabel = $labels[$currentLocale] ?? strtoupper($currentLocale);
@endphp

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    <button
        type="button"
        class="inline-flex items-center gap-1 rounded-xl bg-cyra-card px-3 py-2 text-sm font-semibold text-cyra-muted shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-forest"
        @click="open = !open"
        aria-label="{{ __('ui.language') }}"
        :aria-expanded="open.toString()"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18" />
        </svg>
        {{ $currentLabel }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
    </button>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl bg-cyra-card py-1 shadow-soft ring-1 ring-cyra-line"
        role="menu"
        aria-label="{{ __('ui.language') }}"
    >
        @foreach ($locales as $code => $name)
            <form method="POST" action="{{ route('locale.update') }}" role="none">
                @csrf
                <input type="hidden" name="locale" value="{{ $code }}">
                <button
                    type="submit"
                    role="menuitem"
                    @class([
                        'flex w-full items-center justify-between px-4 py-2 text-left text-sm font-medium transition',
                        'bg-cyra-mint text-cyra-forest' => $code === $currentLocale,
                        'text-cyra-ink hover:bg-cyra-mint' => $code !== $currentLocale,
                    ])
                >
                    <span>{{ $name }}</span>
                    <span class="text-xs font-bold text-cyra-muted">{{ $labels[$code] ?? strtoupper($code) }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
