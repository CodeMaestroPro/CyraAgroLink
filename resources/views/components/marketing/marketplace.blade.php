@props(['commodities'])

@php
    $marketplaceHref = route('marketplace.index');
    $sellHref = route('marketplace.index').'#list-product';
@endphp

<section id="marketplace" class="cyra-section bg-cyra-surface cyra-reveal" x-data="cyraReveal">
    <div class="cyra-container">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="cyra-section-kicker inline-flex items-center gap-2">
                    <span class="cyra-icon-badge h-8 w-8">
                        <x-marketing.icon name="market" class="h-4 w-4" />
                    </span>
                    {{ __('home.marketplace.kicker') }}
                </p>
                <h2 class="cyra-section-heading mt-3">{{ __('home.marketplace.heading') }}</h2>
                <p class="cyra-section-copy">
                    {{ __('home.marketplace.copy') }}
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="{{ $marketplaceHref }}" class="cyra-btn-primary">
                    <x-marketing.icon name="cart" class="h-4 w-4" />
                    {{ auth()->check() ? __('home.marketplace.cta_open') : __('home.marketplace.cta_browse') }}
                </a>
                <a href="{{ $sellHref }}" class="cyra-btn-secondary">
                    <x-marketing.icon name="upload" class="h-4 w-4" />
                    {{ auth()->check() ? __('home.marketplace.cta_upload') : __('home.marketplace.cta_sign_in_to_sell') }}
                </a>
            </div>
        </div>

        @if ($commodities->isEmpty())
            <div class="cyra-panel mt-8 px-6 py-10 text-center">
                <span class="cyra-icon-badge-lg mx-auto">
                    <x-marketing.icon name="upload" class="h-6 w-6" />
                </span>
                <p class="mt-4 text-sm text-cyra-muted">
                    {{ __('home.marketplace.empty') }}
                </p>
                <a href="{{ $sellHref }}" class="cyra-btn-primary mt-5">
                    {{ __('home.marketplace.empty_cta') }}
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($commodities as $index => $commodity)
                    <div class="cyra-reveal" x-data="cyraReveal" style="transition-delay: {{ min($index * 40, 280) }}ms">
                        <x-marketplace.commodity-card :commodity="$commodity" :show-buy="false" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
