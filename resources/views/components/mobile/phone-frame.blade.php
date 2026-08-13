@props([
    'active' => 'home',
])

<div class="relative mx-auto w-[17.5rem] shrink-0">
    <div class="rounded-[2rem] bg-cyra-ink p-[0.55rem] shadow-soft ring-1 ring-black/10">
        <div class="relative overflow-hidden rounded-[1.55rem] bg-white">
            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex justify-center pt-2">
                <div class="h-4 w-24 rounded-full bg-cyra-ink/90"></div>
            </div>

            <div class="flex h-[36.5rem] flex-col bg-cyra-surface/50 pt-7">
                <div class="min-h-0 flex-1 overflow-hidden">
                    {{ $slot }}
                </div>

                <x-mobile.bottom-nav :active="$active" />
            </div>
        </div>
    </div>
</div>
