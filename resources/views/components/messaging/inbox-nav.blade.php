@props([
    'active' => 'notifications',
    'unreadNotifications' => 0,
])

@php
    $items = [
        ['id' => 'notifications', 'label' => 'Notifications', 'href' => '#notifications', 'badge' => $unreadNotifications > 0],
        ['id' => 'messages', 'label' => 'Messages', 'href' => '#messages', 'badge' => false],
        ['id' => 'announcements', 'label' => 'Announcements', 'href' => '#announcements', 'badge' => false],
        ['id' => 'sms', 'label' => 'SMS', 'href' => '#sms', 'badge' => false],
        ['id' => 'email', 'label' => 'Email', 'href' => '#email', 'badge' => false],
        ['id' => 'tasks', 'label' => 'Tasks', 'href' => '#tasks', 'badge' => false],
        ['id' => 'activity', 'label' => 'Activity Log', 'href' => '#activity', 'badge' => false],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'badge' => false],
    ];
@endphp

<aside class="flex w-full flex-col bg-cyra-forest lg:w-48 xl:w-52">
    <div class="px-4 py-4">
        <p class="text-sm font-extrabold text-white">Inbox</p>
        <p class="mt-0.5 text-[11px] font-medium text-white/70">Alerts & chats</p>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-5 lg:overflow-y-auto" aria-label="Notifications and messaging sections">
        <div class="flex gap-1 lg:block lg:space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([
                        'relative inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:w-full',
                        'bg-white/20 text-white shadow-sm' => $item['id'] === $active,
                        'text-white/80 hover:bg-white/10 hover:text-white' => $item['id'] !== $active,
                    ])
                >
                    <span>{{ $item['label'] }}</span>
                    @if ($item['badge'])
                        <span class="inline-flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-cyra-forest" aria-label="Unread"></span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
</aside>
