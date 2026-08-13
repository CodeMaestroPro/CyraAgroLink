@props(['supplier'])

@php
    $fullStars = (int) floor((float) $supplier->rating);
    $fullStars = max(0, min(5, $fullStars));
@endphp

<article class="overflow-hidden rounded-xl bg-white transition hover:-translate-y-0.5 hover:shadow-soft">
    <div class="overflow-hidden rounded-xl">
        <img
            src="{{ $supplier->imageUrl() }}"
            alt="{{ $supplier->name }}"
            class="h-28 w-full object-cover sm:h-32"
            loading="lazy"
        >
    </div>
    <div class="pt-3">
        <h3 class="text-sm font-bold text-cyra-ink sm:text-base">{{ $supplier->name }}</h3>
        <p class="mt-0.5 text-xs text-cyra-muted sm:text-sm">{{ $supplier->locationLabel() }}</p>

        <div class="mt-2 flex items-center gap-2">
            <div class="flex items-center gap-0.5 text-cyra-sun" aria-hidden="true">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="h-3.5 w-3.5 {{ $i <= $fullStars ? 'opacity-100' : 'opacity-25' }}" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <span class="text-xs text-cyra-muted">({{ $supplier->review_count }})</span>
            <span class="ml-auto inline-flex items-center gap-1 text-xs font-bold text-cyra-ink">
                {{ number_format((float) $supplier->rating, 1) }}
                <svg class="h-3.5 w-3.5 text-cyra-sun" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </span>
        </div>
    </div>
</article>
