@props([
    'costs' => [],
    'totalCost' => 0,
])

<article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
    <h2 class="text-base font-extrabold text-cyra-ink">Cost Breakdown</h2>

    <dl class="mt-4 space-y-3 text-sm">
        @foreach ($costs as $cost)
            <div class="flex items-center justify-between gap-3">
                <dt class="text-cyra-muted">{{ $cost['label'] }}</dt>
                <dd class="font-semibold text-cyra-ink">₦{{ number_format($cost['amount']) }}</dd>
            </div>
        @endforeach

        <div class="border-t border-cyra-line pt-3">
            <div class="flex items-center justify-between gap-3">
                <dt class="font-extrabold text-cyra-ink">Total Cost</dt>
                <dd class="font-extrabold text-cyra-ink">₦{{ number_format($totalCost) }}</dd>
            </div>
        </div>
    </dl>
</article>
