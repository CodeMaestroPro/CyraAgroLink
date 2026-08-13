@props([
    'commodity',
    'href' => null,
    'showBuy' => true,
])

@php
    $link = $href ?? route('exchange.show', $commodity);
@endphp

<article class="overflow-hidden rounded-2xl bg-white ring-1 ring-cyra-line/80 transition duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-cyra-soft">
    <a href="{{ $link }}" class="group block">
        <div class="relative overflow-hidden">
            <img
                src="{{ $commodity->imageUrl() }}"
                alt="{{ $commodity->name }}"
                class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                loading="lazy"
            >
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-cyra-forest shadow-sm ring-1 ring-cyra-line/70">
                <x-marketing.icon name="leaf" class="h-3.5 w-3.5" />
                {{ __('home.marketplace.live_badge') }}
            </span>
        </div>
        <div class="p-3.5 pb-2 sm:p-4 sm:pb-2">
            <h3 class="font-display text-sm font-bold text-cyra-ink sm:text-base">{{ $commodity->name }}</h3>
            @if ($commodity->scientific_name)
                <p class="mt-0.5 truncate text-xs italic text-cyra-muted">{{ $commodity->scientific_name }}</p>
            @endif
            <p class="mt-2 text-sm font-extrabold text-cyra-forest">{{ $commodity->formattedPrice() }}</p>
            <p class="mt-1 inline-flex items-center gap-1 truncate text-xs text-cyra-muted">
                <x-marketing.icon name="location" class="h-3.5 w-3.5 shrink-0 text-cyra-forest" />
                {{ $commodity->locationLabel() ?: __('home.marketplace.nigeria') }}
            </p>
        </div>
    </a>

    @if ($showBuy)
        <div class="border-t border-cyra-line/70 px-3.5 py-3 sm:px-4">
            <form method="POST" action="{{ route('marketplace.buy', $commodity) }}" class="flex items-center gap-2">
                @csrf
                <input
                    type="number"
                    name="quantity_tons"
                    min="1"
                    value="1"
                    aria-label="Tons to buy"
                    class="w-16 rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
                >
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white transition hover:bg-cyra-green"
                >
                    Buy
                </button>
            </form>
        </div>
    @endif
</article>
