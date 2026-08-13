<a
    href="{{ $href ?? route('farms.register') }}"
    class="flex min-h-[280px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-cyra-leaf/50 bg-white/60 p-6 text-center transition hover:border-cyra-forest hover:bg-cyra-mint/40"
>
    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
        </svg>
    </span>
    <span class="mt-4 text-sm font-bold text-cyra-forest">Add New Farm</span>
</a>
