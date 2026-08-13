@php
    $user = auth()->user();
    $roleLabel = $user?->role?->label() ?? 'Farmer';
    $initial = strtoupper(substr($user?->name ?? 'U', 0, 1));
    $avatarUrl = $user?->avatarUrl();
@endphp

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    <button
        type="button"
        class="flex items-center gap-2 rounded-xl bg-white py-1.5 pl-1.5 pr-2 shadow-sm ring-1 ring-cyra-line sm:pr-3"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-label="User menu"
    >
        <span class="inline-flex h-9 w-9 overflow-hidden rounded-full bg-cyra-mint text-sm font-bold text-cyra-forest">
            @if ($avatarUrl)
                <img
                    src="{{ $avatarUrl }}"
                    alt="{{ $user?->name }}"
                    class="h-full w-full object-cover"
                >
            @else
                <span class="inline-flex h-full w-full items-center justify-center">{{ $initial }}</span>
            @endif
        </span>
        <span class="hidden text-left sm:block">
            <span class="block text-sm font-bold text-cyra-ink">{{ $user?->name }}</span>
            <span class="block text-xs text-cyra-muted">{{ $roleLabel }}</span>
        </span>
        <svg class="hidden h-4 w-4 text-cyra-muted sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
    </button>

    <div
        x-cloak
        x-show="open"
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl bg-white py-1 shadow-soft ring-1 ring-cyra-line"
    >
        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm font-medium text-cyra-ink hover:bg-cyra-mint">Profile</a>
        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm font-medium text-cyra-ink hover:bg-cyra-mint">Settings</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="block w-full px-4 py-2 text-left text-sm font-medium text-cyra-ink hover:bg-cyra-mint">
                Log Out
            </button>
        </form>
    </div>
</div>
