@props([
    'active' => 'home',
])

<nav class="mt-auto flex items-center justify-around border-t border-cyra-line/80 bg-white px-1 pb-3 pt-2" aria-label="Mobile navigation">
    @php
        $items = [
            'home' => [
                'label' => 'Home',
                'path' => 'M3 10.5L12 4l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z',
            ],
            'market' => [
                'label' => 'Market',
                'path' => 'M4 7h16l-1.2 11.2A2 2 0 0116.81 20H7.19a2 2 0 01-1.99-1.8L4 7zm4-3h8l1 3H7l1-3z',
            ],
            'invest' => [
                'label' => 'Invest',
                'path' => 'M4 19V5m0 14h16M8 15l3-3 2 2 5-5',
            ],
            'profile' => [
                'label' => 'Profile',
                'path' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4z',
            ],
        ];
    @endphp

    @foreach ($items as $key => $item)
        <button
            type="button"
            class="flex flex-col items-center gap-0.5 px-2 {{ $active === $key ? 'text-cyra-forest' : 'text-cyra-muted' }}"
            aria-label="{{ $item['label'] }}"
            @if ($active === $key) aria-current="page" @endif
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['path'] }}" />
            </svg>
            <span class="text-[9px] font-semibold">{{ $item['label'] }}</span>
        </button>
    @endforeach
</nav>
