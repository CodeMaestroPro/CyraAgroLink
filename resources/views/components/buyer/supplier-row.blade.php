@props([
    'name',
    'badge',
    'rating',
    'image',
])

<li class="flex items-center gap-3 py-3">
    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-cyra-panel ring-1 ring-cyra-line/80">
        <img
            src="{{ $image }}"
            alt="{{ $name }}"
            class="h-full w-full object-cover"
            loading="lazy"
        >
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-bold text-cyra-ink">{{ $name }}</p>
        <p class="mt-0.5 truncate text-xs text-cyra-muted">{{ $badge }}</p>
    </div>

    <p class="inline-flex shrink-0 items-center gap-1 text-sm font-bold text-cyra-ink">
        <svg class="h-4 w-4 text-cyra-sun" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        {{ $rating }}
    </p>
</li>
