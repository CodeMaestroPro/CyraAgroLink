@props([
    'conversations' => [],
])

@php
    $grouped = collect($conversations)->groupBy(fn (array $item) => $item['group'] ?? 'Today');
@endphp

<aside class="flex w-full flex-col border-b border-cyra-line bg-cyra-surface/50 lg:w-72 lg:border-b-0 lg:border-r xl:w-80">
    <div class="p-4 sm:p-5">
        <form method="POST" action="{{ route('ai.assistant.store') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-cyra-line bg-white px-4 py-2.5 text-sm font-semibold text-cyra-ink transition hover:border-cyra-forest/30 hover:bg-cyra-mint/40"
            >
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14M5 12h14"/>
                    </svg>
                </span>
                New Chat
            </button>
        </form>
        <p class="mt-2 px-1 text-[11px] leading-snug text-cyra-muted">
            Chats stay for this visit only. Refreshing the page clears them.
        </p>
    </div>

    <div class="flex-1 space-y-5 overflow-y-auto px-3 pb-5 sm:px-4">
        @forelse ($grouped as $group => $items)
            <div>
                <p class="px-2 text-xs font-semibold uppercase tracking-wide text-cyra-muted">{{ $group }}</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($items as $conversation)
                        <li>
                            <form method="POST" action="{{ route('ai.assistant.open', $conversation['id']) }}">
                                @csrf
                                <button
                                    type="submit"
                                    @class([
                                        'flex w-full items-start gap-2 rounded-xl px-3 py-2.5 text-left transition',
                                        'bg-cyra-mint/80 ring-1 ring-cyra-soft/70' => $conversation['active'] ?? false,
                                        'hover:bg-white' => ! ($conversation['active'] ?? false),
                                    ])
                                >
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-cyra-ink">
                                            {{ $conversation['title'] }}
                                        </span>
                                        <span class="mt-0.5 block truncate text-xs text-cyra-muted">
                                            {{ $conversation['subtitle'] }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 pt-0.5 text-[11px] font-medium text-cyra-muted">
                                        {{ $conversation['time'] }}
                                    </span>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="px-2 text-xs text-cyra-muted">No chats yet. Send a message to begin.</p>
        @endforelse
    </div>
</aside>
