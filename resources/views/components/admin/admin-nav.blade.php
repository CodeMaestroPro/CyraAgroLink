@props([
    'active' => 'dashboard',
])

@php
    $items = [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => route('admin.dashboard', ['tab' => 'dashboard'])],
        ['id' => 'users', 'label' => 'User Management', 'href' => route('admin.dashboard', ['tab' => 'users'])],
        ['id' => 'roles', 'label' => 'Role Management', 'href' => route('admin.dashboard', ['tab' => 'roles'])],
        ['id' => 'verification', 'label' => 'Verification', 'href' => route('admin.dashboard', ['tab' => 'verification'])],
        ['id' => 'marketplace', 'label' => 'Marketplace', 'href' => route('marketplace.index')],
        ['id' => 'investments', 'label' => 'Investments', 'href' => route('investments.index')],
        ['id' => 'logistics', 'label' => 'Logistics', 'href' => route('logistics.index')],
        ['id' => 'warehouse', 'label' => 'Warehouse', 'href' => route('warehouse.index')],
        ['id' => 'finance', 'label' => 'Finance', 'href' => route('financial.dashboard')],
        ['id' => 'reports', 'label' => 'Reports', 'href' => route('reporting.analytics')],
        ['id' => 'audit', 'label' => 'Audit Logs', 'href' => route('admin.dashboard', ['tab' => 'audit'])],
        ['id' => 'security', 'label' => 'Security Center', 'href' => route('admin.dashboard', ['tab' => 'security'])],
        ['id' => 'settings', 'label' => 'Account Settings', 'href' => route('profile.edit')],
    ];
@endphp

<aside class="flex w-full flex-col bg-gradient-to-b from-cyra-forest to-[#0A5C2E] lg:w-56 xl:w-60">
    <div class="flex items-center gap-2.5 px-4 py-4">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-cyra-soft">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5L12 4l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold text-white">Admin</p>
            <p class="truncate text-[11px] font-medium text-white/70">Platform control</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-5 lg:overflow-y-auto" aria-label="Enterprise admin sections">
        <div class="flex gap-1 lg:block lg:space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([
                        'inline-flex shrink-0 items-center rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:w-full',
                        'bg-white/20 text-white shadow-sm' => $item['id'] === $active,
                        'text-white/80 hover:bg-white/10 hover:text-white' => $item['id'] !== $active,
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</aside>
