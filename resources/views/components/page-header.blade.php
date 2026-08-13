@props([
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'mb-7']) }}>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyra-forest via-[#0E7340] to-[#0A5C2E] px-5 py-6 shadow-soft sm:px-7 sm:py-7">
        <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 left-1/3 h-36 w-36 rounded-full bg-cyra-sun/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;" aria-hidden="true"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-3xl">
                <p class="cyra-section-kicker !text-cyra-soft">CyraAgroLink</p>
                <h1 class="mt-1.5 font-display text-2xl font-bold tracking-tight text-white sm:text-3xl lg:text-[2.05rem] lg:leading-tight">
                    {{ $title }}
                </h1>
                @if ($description)
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/85 sm:text-base">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 [&_a]:bg-white/95 [&_a]:text-cyra-forest [&_a]:ring-white/40 [&_button]:bg-white/95 [&_button]:text-cyra-forest [&_button]:ring-white/40">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</header>
