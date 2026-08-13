@props([
    'crop',
    'stages' => [],
    'timelinePercent' => 0,
])

@php
    $daysToHarvest = $crop->daysToHarvest();
    $harvestMeta = $daysToHarvest === null
        ? null
        : ($daysToHarvest >= 0 ? $daysToHarvest.' days to go' : abs($daysToHarvest).' days overdue');
@endphp

<section class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-crop.metric-card
            label="Growth Stage"
            :value="$crop->growth_stage->label()"
            value-class="text-cyra-forest"
            :progress="$crop->progress_percent"
        />

        <x-crop.metric-card
            label="Next Activity"
            :value="$crop->next_activity ?: '—'"
            :meta="$crop->nextActivityLabel()"
        />

        <x-crop.metric-card
            label="Expected Harvest"
            :value="$crop->expected_harvest_at?->format('d M, Y') ?: '—'"
            :meta="$harvestMeta"
        />

        <x-crop.metric-card
            label="Crop Health"
            :value="$crop->health_status->label()"
            :value-class="$crop->health_status->isPositive() ? 'text-cyra-forest' : 'text-red-600'"
            :meta="$crop->health_notes"
        />
    </div>

    <div class="grid gap-5 lg:grid-cols-5">
        <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line lg:col-span-3">
            <h3 class="text-base font-extrabold text-cyra-ink">Growth Progress</h3>

            <div class="mt-8">
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($stages as $stage)
                        <div class="flex flex-col items-center text-center">
                            <x-crop.stage-icon :stage="$stage['key']" :active="$stage['reached']" />
                            <p @class([
                                'mt-2 text-xs font-semibold',
                                'text-cyra-forest' => $stage['current'] || $stage['reached'],
                                'text-cyra-muted' => ! $stage['reached'],
                            ])>
                                {{ $stage['label'] }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="relative mt-5 h-2.5 overflow-hidden rounded-full bg-cyra-line">
                    <div
                        class="absolute inset-y-0 left-0 rounded-full bg-cyra-forest transition-all"
                        style="width: {{ $timelinePercent }}%"
                    ></div>
                </div>
            </div>
        </article>

        <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line lg:col-span-2">
            <h3 class="text-base font-extrabold text-cyra-ink">AI Recommendation</h3>

            <div class="mt-4 rounded-xl bg-cyra-surface p-4">
                <p class="text-sm font-bold leading-relaxed text-cyra-ink">
                    {{ $crop->ai_recommendation ?: 'No recommendations available yet.' }}
                </p>

                <a
                    href="{{ route('crops.manage', ['crop' => $crop->id, 'tab' => 'fertilizer']) }}"
                    class="mt-5 inline-flex items-center justify-center rounded-lg border-2 border-cyra-forest bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:bg-cyra-mint"
                >
                    View Details
                </a>
            </div>
        </article>
    </div>
</section>
