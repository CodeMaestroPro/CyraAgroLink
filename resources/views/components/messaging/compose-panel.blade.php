@props([
    'title',
    'description' => null,
    'icon' => 'megaphone',
])

<aside {{ $attributes->merge(['class' => 'min-w-0 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line lg:sticky lg:top-4 lg:self-start']) }}>
    <div class="relative overflow-hidden bg-gradient-to-br from-cyra-forest via-[#0E7340] to-[#0A5C2E] px-4 py-4 text-white sm:px-5">
        <div class="pointer-events-none absolute -right-6 -top-8 h-24 w-24 rounded-full bg-white/10 blur-xl" aria-hidden="true"></div>
        <div class="relative flex items-start gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                @if ($icon === 'sms')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8h10M7 12h6M6 4h12a2 2 0 0 1 2 2v9.5L16 13H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
                @elseif ($icon === 'email')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5 12 13l8-5.5M5 6h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z"/></svg>
                @elseif ($icon === 'task')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11.5 11 13.5 15.5 9M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.5c3.5-.8 6.5-.2 8.5 1.2-1.2 2.8-3.8 5-7.2 6.1M8.5 8.2C6 9.4 4.2 11.2 3.2 13.5c2.8.8 5.4.4 7.3-.7M9 19.5c1.2-2.2 2.1-4.2 2.6-6.1"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <h3 class="font-display text-base font-extrabold tracking-tight">{{ $title }}</h3>
                @if ($description)
                    <p class="mt-1 text-xs leading-relaxed text-white/80">{{ $description }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="p-4 sm:p-5">
        {{ $slot }}
    </div>
</aside>
