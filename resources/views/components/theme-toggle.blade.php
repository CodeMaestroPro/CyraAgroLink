@props([
    'class' => '',
])

<button
    type="button"
    {{ $attributes->merge([
        'class' => 'relative inline-flex rounded-xl bg-cyra-card p-2.5 text-cyra-muted shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-forest '.$class,
        'aria-label' => __('ui.toggle_theme'),
    ]) }}
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = window.cyraTheme.toggleTheme() === 'dark';
        },
    }"
    x-on:cyra-theme-changed.window="dark = $event.detail.theme === 'dark'"
    @click="toggle()"
    :aria-pressed="dark.toString()"
    title="Toggle theme"
>
    <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0L16.95 7.05M7.05 16.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z" />
    </svg>
    <svg x-cloak x-show="dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 14.5A8.5 8.5 0 1111.5 3 7 7 0 0021 14.5z" />
    </svg>
</button>
