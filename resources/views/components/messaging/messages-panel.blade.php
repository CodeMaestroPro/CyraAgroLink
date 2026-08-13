@props([
    'contacts' => [],
    'thread' => [],
    'search' => '',
    'sendUrl' => '',
])

<div class="flex w-full min-w-0 flex-col">
    <div class="flex flex-col gap-3 border-b border-cyra-line px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <h3 class="font-display text-lg font-extrabold text-cyra-ink">Messages</h3>
        <form method="GET" action="{{ route('messaging.index') }}" class="w-full sm:max-w-xs">
            <input type="hidden" name="tab" value="messages">
            @if (! empty($thread['contact_id'] ?? null))
                <input type="hidden" name="contact" value="{{ $thread['contact_id'] }}">
            @endif
            <label class="sr-only" for="message-search">Search messages</label>
            <input
                id="message-search"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search messages..."
                class="w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
        </form>
    </div>

    <div class="flex min-h-[28rem] w-full min-w-0 flex-col lg:flex-row">
        <aside class="w-full border-b border-cyra-line lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r xl:w-72" aria-label="Conversations">
            <ul class="flex gap-2 overflow-x-auto p-3 lg:max-h-[32rem] lg:flex-col lg:overflow-y-auto">
                @forelse ($contacts as $contact)
                    <li class="min-w-[12rem] shrink-0 lg:min-w-0 lg:w-full">
                        <a
                            href="{{ $contact['url'] }}"
                            @class([
                                'flex w-full items-center gap-3 rounded-xl px-3 py-3 transition',
                                'bg-cyra-forest text-white' => $contact['active'] ?? false,
                                'bg-cyra-surface hover:bg-cyra-mint/50' => ! ($contact['active'] ?? false),
                            ])
                        >
                            <span class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full bg-white">
                                <img src="{{ $contact['image'] }}" alt="{{ $contact['name'] }}" class="h-full w-full object-cover" loading="lazy">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span @class(['block truncate text-sm font-bold', 'text-white' => $contact['active'] ?? false, 'text-cyra-ink' => ! ($contact['active'] ?? false)])>
                                    {{ $contact['name'] }}
                                </span>
                                <span @class(['mt-0.5 block truncate text-xs', 'text-white/80' => $contact['active'] ?? false, 'text-cyra-muted' => ! ($contact['active'] ?? false)])>
                                    {{ $contact['preview'] }}
                                </span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-2 py-4 text-sm text-cyra-muted">No contacts match your search.</li>
                @endforelse
            </ul>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b border-cyra-line px-4 py-3">
                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-cyra-surface">
                    <img src="{{ $thread['image'] }}" alt="{{ $thread['contact_name'] }}" class="h-full w-full object-cover">
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-extrabold text-cyra-ink">{{ $thread['contact_name'] }}</p>
                    <p class="text-xs font-semibold {{ ($thread['online'] ?? false) ? 'text-cyra-forest' : 'text-cyra-muted' }}">
                        {{ ($thread['online'] ?? false) ? 'Online' : 'Offline' }}
                    </p>
                </div>
            </header>

            <div class="min-h-[14rem] flex-1 space-y-3 overflow-y-auto px-4 py-4">
                @forelse ($thread['messages'] ?? [] as $message)
                    @if (($message['role'] ?? '') === 'outgoing')
                        <div class="flex justify-end">
                            <div class="max-w-[85%] break-words rounded-2xl rounded-br-md bg-cyra-forest px-3.5 py-2.5 text-sm text-white">
                                <p>{{ $message['body'] }}</p>
                                <p class="mt-1 text-right text-[11px] text-white/75">{{ $message['time'] }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[85%] break-words rounded-2xl rounded-bl-md bg-cyra-surface px-3.5 py-2.5 text-sm text-cyra-ink ring-1 ring-cyra-line">
                                <p>{{ $message['body'] }}</p>
                                <p class="mt-1 text-[11px] text-cyra-muted">{{ $message['time'] }}</p>
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-cyra-muted">No messages yet. Say hello below.</p>
                @endforelse
            </div>

            <form class="border-t border-cyra-line p-3 sm:p-4" action="{{ $sendUrl }}" method="POST">
                @csrf
                <input type="hidden" name="contact" value="{{ $thread['contact_id'] ?? '' }}">
                <div class="flex gap-2">
                    <label class="sr-only" for="thread-message-input">Type a message</label>
                    <input
                        id="thread-message-input"
                        type="text"
                        name="message"
                        required
                        maxlength="2000"
                        placeholder="Type a message..."
                        class="min-w-0 flex-1 rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                    <button type="submit" class="shrink-0 rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
