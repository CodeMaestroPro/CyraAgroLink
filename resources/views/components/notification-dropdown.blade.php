@props([
    'count' => 0,
])

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        class="relative rounded-xl bg-white p-2.5 text-cyra-muted shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-forest"
        @click="open = !open"
        aria-label="Notifications"
        :aria-expanded="open.toString()"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
        </svg>
        @if ($count > 0)
            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl bg-white shadow-soft ring-1 ring-cyra-line"
    >
        <div class="border-b border-cyra-line px-4 py-3">
            <p class="text-sm font-extrabold text-cyra-ink">Notifications</p>
        </div>
        <div class="max-h-72 overflow-y-auto p-2">
            <a href="{{ route('messaging.index') }}" class="block rounded-lg px-3 py-2.5 text-sm hover:bg-cyra-mint">
                <span class="font-semibold text-cyra-ink">Market & message updates</span>
                <span class="mt-0.5 block text-xs text-cyra-muted">Open your inbox for the latest alerts.</span>
            </a>
        </div>
        <div class="border-t border-cyra-line px-4 py-2.5">
            <a href="{{ route('messaging.index') }}" class="text-xs font-bold text-cyra-forest hover:text-cyra-green">View all</a>
        </div>
    </div>
</div>
