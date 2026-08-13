@props([
    'name',
    'location',
    'crop',
    'stage',
    'progress',
    'image',
])

<article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line">
    <div class="aspect-[16/10] overflow-hidden">
        <img src="{{ $image }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy">
    </div>
    <div class="p-4">
        <h3 class="text-base font-bold text-cyra-ink">{{ $name }}</h3>
        <p class="mt-1 text-sm text-cyra-muted">{{ $location }}</p>

        <div class="mt-4 flex items-center justify-between gap-3 text-sm">
            <span class="font-semibold text-cyra-ink">{{ $crop }}</span>
            <span class="rounded-full bg-cyra-mint px-2.5 py-1 text-xs font-semibold text-cyra-forest">{{ $stage }}</span>
        </div>

        <div class="mt-4">
            <div class="mb-1.5 flex items-center justify-between text-xs font-semibold">
                <span class="text-cyra-muted">Progress</span>
                <span class="text-cyra-forest">{{ $progress }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-cyra-line">
                <div class="h-full rounded-full bg-cyra-forest" style="width: {{ max(0, min(100, (int) $progress)) }}%"></div>
            </div>
        </div>
    </div>
</article>
