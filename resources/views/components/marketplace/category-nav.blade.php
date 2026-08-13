@props([
    'categories' => [],
    'active' => null,
    'query' => '',
    'state' => null,
])

<aside class="rounded-2xl bg-cyra-surface/80 p-4 ring-1 ring-cyra-line">
    <h2 class="text-sm font-extrabold text-cyra-ink">Category</h2>

    <nav class="mt-3 space-y-1" aria-label="Marketplace categories">
        <a
            href="{{ route('marketplace.index', array_filter(['q' => $query ?: null, 'state' => $state, 'view' => 'commodities'])) }}"
            @class([
                'flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                'bg-white text-cyra-forest shadow-sm ring-1 ring-cyra-line' => blank($active),
                'text-cyra-ink hover:bg-white/80' => filled($active),
            ])
        >
            <span>All categories</span>
        </a>

        @foreach ($categories as $category)
            <a
                href="{{ route('marketplace.index', array_filter(['category' => $category->slug, 'q' => $query ?: null, 'state' => $state, 'view' => 'commodities'])) }}"
                @class([
                    'flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                    'bg-white text-cyra-forest shadow-sm ring-1 ring-cyra-line' => $active === $category->slug,
                    'text-cyra-ink hover:bg-white/80' => $active !== $category->slug,
                ])
            >
                <span>{{ $category->name }}</span>
                <svg class="h-4 w-4 text-cyra-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.17 10 7.23 6.29a.75.75 0 111.04-1.08l4.25 4.21a.75.75 0 010 1.08l-4.25 4.21a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                </svg>
            </a>
        @endforeach
    </nav>
</aside>
