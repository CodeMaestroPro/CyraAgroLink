<x-dashboard-layout
    title="Farmer Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Overview'],
    ]"
>
    <x-page-header
        title="Welcome back, {{ $greetingName }}"
        description="Here's what's happening on your farms today."
    />

    <x-section-heading title="Key Metrics" description="Live performance across your farms." />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key metrics">
        @foreach ($stats as $stat)
            <x-dashboard.stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :meta="$stat['meta'] ?? null"
                :meta-tone="$stat['meta_tone'] ?? 'amber'"
                :meta-href="$stat['meta_href'] ?? null"
            />
        @endforeach
    </section>

    <section class="mt-8" aria-labelledby="farm-overview-heading">
        <x-section-heading title="Farm Overview" description="Status and progress for each registered farm." />

        <div class="mt-1 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($farms as $farm)
                <x-dashboard.farm-card
                    :name="$farm['name']"
                    :location="$farm['location']"
                    :crop="$farm['crop']"
                    :stage="$farm['stage']"
                    :progress="$farm['progress']"
                    :image="$farm['image']"
                />
            @endforeach

            <x-dashboard.add-farm-card />
        </div>
    </section>

    <section class="mt-8 grid grid-cols-1 gap-4 xl:grid-cols-3" aria-label="Insights">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line xl:col-span-1">
            <h2 class="text-base font-extrabold text-cyra-ink">Recent Activities</h2>
            <ul class="mt-2 divide-y divide-cyra-line">
                @foreach ($activities as $activity)
                    <x-dashboard.activity-item
                        :title="$activity['title']"
                        :detail="$activity['detail']"
                        :time="$activity['time']"
                        :icon="$activity['icon']"
                    />
                @endforeach
            </ul>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line xl:col-span-1">
            <h2 class="text-base font-extrabold text-cyra-ink">Earnings Overview</h2>
            <p class="mt-3 text-2xl font-extrabold text-cyra-ink">{{ $earnings['total'] }}</p>
            <p class="mt-1 text-sm font-semibold text-cyra-leaf">{{ $earnings['trend_label'] }}</p>
            <div class="mt-4 h-52">
                <canvas
                    id="earningsChart"
                    data-labels='@json($earnings['labels'])'
                    data-values='@json($earnings['values'])'
                    aria-label="Earnings chart for the last six months"
                    role="img"
                ></canvas>
            </div>
        </article>

        <article class="relative overflow-hidden rounded-2xl bg-cyra-mint p-5 shadow-sm ring-1 ring-cyra-line/60 xl:col-span-1">
            <div
                class="pointer-events-none absolute inset-0 bg-cover bg-center opacity-25"
                style="background-image: url('{{ asset('images/dashboard/ai-recommendation.jpg') }}')"
                aria-hidden="true"
            ></div>
            <div class="relative">
                <h2 class="text-base font-extrabold text-cyra-forest">AI Recommendation</h2>
                <p class="mt-4 text-sm leading-relaxed text-cyra-ink/90">
                    {{ $aiRecommendation['message'] }}
                </p>
                <a
                    href="{{ $aiRecommendation['action_href'] }}"
                    class="mt-6 inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-cyra-forest shadow-sm transition hover:bg-white/90"
                >
                    {{ $aiRecommendation['action_label'] }}
                </a>
            </div>
        </article>
    </section>
</x-dashboard-layout>
