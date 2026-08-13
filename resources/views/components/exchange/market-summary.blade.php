@props([
    'commodity',
    'summary' => [],
])

<article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
    <h2 class="text-base font-extrabold text-cyra-ink">Market Summary</h2>

    <dl class="mt-4 space-y-3 text-sm">
        <div class="flex items-center justify-between gap-3">
            <dt class="text-cyra-muted">Day High</dt>
            <dd class="font-bold text-cyra-ink">₦{{ number_format((int) ($summary['day_high'] ?? 0)) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-cyra-muted">Day Low</dt>
            <dd class="font-bold text-cyra-ink">₦{{ number_format((int) ($summary['day_low'] ?? 0)) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-cyra-muted">Volume</dt>
            <dd class="font-bold text-cyra-ink">{{ number_format((int) ($summary['volume'] ?? 0)) }} Tons</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-cyra-muted">Open Interest</dt>
            <dd class="font-bold text-cyra-ink">{{ number_format((int) ($summary['open_interest'] ?? 0)) }} Tons</dd>
        </div>
    </dl>
</article>
