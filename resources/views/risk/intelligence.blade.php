<x-dashboard-layout
    title="AI Risk Intelligence Center"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Intelligence'],
        ['label' => 'Risk Center'],
    ]"
>
    <x-page-header
        title="AI Risk Intelligence Center"
        description="Live agricultural risk monitoring across market, weather, disease, supply chain, and credit signals."
    >
        <x-slot:actions>
            <form method="POST" action="{{ $actions['refresh_url'] }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40">
                    Refresh score
                </button>
            </form>
            <a
                href="{{ $actions['export_url'] }}"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Download report
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/50 px-4 py-3 text-sm text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div id="overview">
        <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
            Risk Overview
        </h2>
        <p class="mt-1 text-sm text-cyra-muted">
            AI-powered agricultural risk monitoring
            · Updated {{ $score['calculated_at'] }}
            · {{ $farmsCount }} farm{{ $farmsCount === 1 ? '' : 's' }} linked
        </p>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3" aria-label="Risk overview and alerts">
        <article id="score" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6 xl:col-span-2">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,16rem)_1fr] lg:items-center">
                <div class="relative mx-auto w-full max-w-[16rem]">
                    <div class="h-40 sm:h-44">
                        <canvas
                            id="riskScoreGauge"
                            data-labels='@json($gauge['labels'])'
                            data-values='@json($gauge['values'])'
                            data-colors='@json($gauge['colors'])'
                            aria-label="Overall risk score gauge"
                            role="img"
                        ></canvas>
                    </div>
                    <div class="pointer-events-none absolute inset-x-0 bottom-2 text-center">
                        <p class="text-[11px] font-medium text-cyra-muted">{{ $score['label'] }}</p>
                        <p class="text-4xl font-extrabold tabular-nums text-cyra-ink">{{ $score['value'] }}</p>
                        <p class="mt-0.5 text-sm font-bold {{ $score['status_tone'] }}">{{ $score['status'] }}</p>
                    </div>
                </div>

                <ul id="categories" class="space-y-2.5">
                    @foreach ($categories as $category)
                        @php
                            $dotClass = match ($category['tone']) {
                                'high' => 'bg-rose-500',
                                'medium' => 'bg-amber-500',
                                default => 'bg-cyra-forest',
                            };
                            $levelClass = match ($category['tone']) {
                                'high' => 'text-rose-600',
                                'medium' => 'text-amber-500',
                                default => 'text-cyra-forest',
                            };
                        @endphp
                        <li class="flex items-center gap-2.5 text-sm">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $category['label'] }}</span>
                            <span class="text-xs tabular-nums text-cyra-muted">{{ $category['score'] }}</span>
                            <span class="font-bold {{ $levelClass }}">{{ $category['level'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div id="report" class="mt-6 rounded-xl bg-cyra-surface/60 p-4 ring-1 ring-cyra-line">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-cyra-ink">Risk Report</h3>
                        <p class="mt-1 text-sm text-cyra-muted">
                            Score {{ $report['score'] }} · {{ $report['status'] }} · {{ $report['open_alerts'] }} open alert{{ $report['open_alerts'] === 1 ? '' : 's' }}
                            · {{ $report['mitigations'] }} active mitigation{{ $report['mitigations'] === 1 ? '' : 's' }}
                        </p>
                        <p class="mt-1 text-xs text-cyra-muted">Generated {{ $report['generated_at'] }}</p>
                    </div>
                    <a
                        href="{{ $actions['export_url'] }}"
                        class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        View Risk Report
                    </a>
                </div>
            </div>
        </article>

        <article id="alerts" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Risk Alerts</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($alerts as $alert)
                    @php
                        $toneClass = match ($alert['severity_tone']) {
                            'high' => 'bg-rose-50/80 ring-rose-100',
                            'medium' => 'bg-amber-50/70 ring-amber-100',
                            default => 'bg-cyra-mint/40 ring-cyra-line',
                        };
                        $iconClass = match ($alert['severity_tone']) {
                            'high' => 'bg-rose-100 text-rose-600',
                            'medium' => 'bg-amber-100 text-amber-600',
                            default => 'bg-cyra-mint text-cyra-forest',
                        };
                    @endphp
                    <li class="rounded-xl px-3 py-3 ring-1 {{ $toneClass }}">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $iconClass }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.3L2.8 18a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold leading-snug text-cyra-ink">{{ $alert['title'] }}</p>
                                @if ($alert['detail'])
                                    <p class="mt-1 text-xs text-cyra-muted">{{ $alert['detail'] }}</p>
                                @endif
                                <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-cyra-muted">
                                    {{ $alert['category'] }} · {{ $alert['severity'] }} · {{ $alert['status'] }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @if ($alert['can_acknowledge'])
                                        <form method="POST" action="{{ $alert['acknowledge_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-cyra-line bg-white px-2.5 py-1 text-[11px] font-bold text-cyra-ink hover:bg-cyra-surface">
                                                Acknowledge
                                            </button>
                                        </form>
                                    @endif
                                    @if ($alert['can_dismiss'])
                                        <form method="POST" action="{{ $alert['dismiss_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-700 hover:bg-rose-50">
                                                Dismiss
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="rounded-xl bg-cyra-surface/50 px-3 py-4 text-sm text-cyra-muted ring-1 ring-cyra-line">
                        No open risk alerts. Refresh the score after farm or market changes.
                    </li>
                @endforelse
            </ul>
        </article>
    </section>

    <section id="mitigations" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Mitigations">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Active Mitigations</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($mitigations as $item)
                    <li class="flex flex-wrap items-start justify-between gap-3 rounded-xl bg-cyra-surface/50 px-3 py-3 ring-1 ring-cyra-line">
                        <div>
                            <p class="text-sm font-semibold text-cyra-ink">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-xs text-cyra-muted">{{ $item['action_type'] }} · {{ $item['status'] }}@if($item['due']) · Due {{ $item['due'] }}@endif</p>
                            @if ($item['link'])
                                <a href="{{ $item['link'] }}" class="mt-1 inline-block text-xs font-bold text-cyra-forest hover:underline">Open related module</a>
                            @endif
                        </div>
                        <form method="POST" action="{{ $item['complete_url'] }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">
                                Mark done
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No planned mitigations yet. Create one from an alert or the form.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Plan Mitigation</h2>
            <p class="mt-1 text-sm text-cyra-muted">Convert a risk into a concrete next step (insurance, logistics, market hedge, etc.).</p>
            <form method="POST" action="{{ $actions['mitigation_url'] }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Title</span>
                    <input
                        type="text"
                        name="title"
                        required
                        maxlength="160"
                        placeholder="e.g. Buy weather index cover for Ogun farm"
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Action type</span>
                    <select
                        name="action_type"
                        required
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                        <option value="insure">Insure</option>
                        <option value="logistics_review">Logistics review</option>
                        <option value="market_hedge">Market hedge</option>
                        <option value="crop_scouting">Crop / enterprise scouting</option>
                        <option value="wallet_topup">Wallet top-up</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                @if (count($alerts))
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Linked alert (optional)</span>
                        <select
                            name="alert_id"
                            class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                            <option value="">None</option>
                            @foreach ($alerts as $alert)
                                <option value="{{ $alert['id'] }}">{{ $alert['title'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                >
                    Save mitigation
                </button>
            </form>
        </article>
    </section>
</x-dashboard-layout>
