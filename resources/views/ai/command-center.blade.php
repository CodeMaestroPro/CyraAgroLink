<x-dashboard-layout
    title="CyraAI Command Center"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Intelligence'],
        ['label' => 'CyraAI'],
    ]"
>
    <x-page-header
        title="CyraAI Command Center"
        description="Ask CyraAI for farm advice, market forecasts, weather guidance, and crop recommendations."
    />

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-5">
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6 xl:col-span-3" aria-labelledby="cyraai-assistant-heading">
            <h2 id="cyraai-assistant-heading" class="font-display text-xl font-bold tracking-tight text-cyra-ink sm:text-2xl">
                CyraAI Assistant
            </h2>

            <div class="mt-8 flex flex-col items-center text-center">
                <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-cyra-mint to-cyra-soft ring-4 ring-cyra-forest/10">
                    <svg class="h-10 w-10 text-cyra-forest" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                        <circle cx="32" cy="28" r="14" fill="currentColor" opacity="0.15"/>
                        <path d="M22 26c0-5.5 4.5-10 10-10s10 4.5 10 10v4c0 2.2-1.8 4-4 4h-12c-2.2 0-4-1.8-4-4v-4z" fill="currentColor"/>
                        <circle cx="27" cy="27" r="2" fill="#fff"/>
                        <circle cx="37" cy="27" r="2" fill="#fff"/>
                        <path d="M26 34c2 2 4.5 3 6 3s4-1 6-3" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                        <path d="M18 30h-4M50 30h-4M32 14v-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="20" y="42" width="24" height="8" rx="4" fill="currentColor" opacity="0.85"/>
                    </svg>
                </span>

                <p class="mt-5 font-display text-lg font-semibold text-cyra-ink sm:text-xl">
                    Hello {{ $greetingName }}! How can I help you today?
                </p>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($prompts as $prompt)
                    <a
                        href="{{ $prompt['href'] }}"
                        class="inline-flex items-center gap-3 rounded-2xl bg-cyra-surface/80 px-4 py-3.5 text-left text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line transition hover:bg-cyra-mint hover:ring-cyra-forest/30"
                    >
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-cyra-forest shadow-sm ring-1 ring-cyra-line">
                            @if ($prompt['icon'] === 'chart')
                                <svg class="h-4.5 w-4.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15l3-3 2 2 5-5"/></svg>
                            @elseif ($prompt['icon'] === 'network')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="6" cy="12" r="2.5" stroke-width="1.8"/><circle cx="18" cy="6" r="2.5" stroke-width="1.8"/><circle cx="18" cy="18" r="2.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8.2 11.2l7.2-4M8.2 12.8l7.2 4"/></svg>
                            @elseif ($prompt['icon'] === 'weather')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14a4 4 0 017.7-1.5A3.2 3.2 0 0117 18H8.3A3.2 3.2 0 018 14zM10 19v2M14 19v2"/></svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/></svg>
                            @endif
                        </span>
                        <span>{{ $prompt['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <form class="mt-8" action="{{ route('ai.assistant') }}" method="GET">
                <div class="flex items-center gap-2.5">
                    <label class="relative min-w-0 flex-1">
                        <span class="sr-only">Ask CyraAI anything</span>
                        <input
                            type="text"
                            name="q"
                            placeholder="Ask CyraAI anything..."
                            class="w-full rounded-2xl border-0 bg-cyra-surface py-3.5 pl-4 pr-12 text-sm text-cyra-ink shadow-sm ring-1 ring-cyra-line placeholder:text-cyra-muted focus:outline-none focus:ring-2 focus:ring-cyra-forest/25"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-cyra-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4a4 4 0 014 4v4a4 4 0 01-8 0V8a4 4 0 014-4zM6 12a6 6 0 0012 0M12 18v2"/>
                            </svg>
                        </span>
                    </label>
                    <button
                        type="submit"
                        class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyra-forest text-white shadow-sm transition hover:bg-cyra-green"
                        aria-label="Send message"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h12M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </div>
            </form>
        </section>

        <aside class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6 xl:col-span-2" aria-labelledby="ai-insights-heading">
            <x-section-heading title="AI Insights" tone="mint" class="mb-4" />
            <h2 id="ai-insights-heading" class="sr-only">AI Insights</h2>

            <ul class="space-y-3">
                @foreach ($insights as $insight)
                    <li class="flex gap-3 rounded-2xl bg-cyra-mint/70 px-4 py-3.5 ring-1 ring-cyra-soft/60">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-cyra-forest shadow-sm ring-1 ring-cyra-line">
                            @if ($insight['icon'] === 'grain')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c-.4 2.2-1.8 3.8-3.8 4.6 1.6.4 2.9 1.4 3.8 2.8.9-1.4 2.2-2.4 3.8-2.8C13.8 6.8 12.4 5.2 12 3zm0 8.2c-.5 2.4-2.1 4.1-4.4 4.9 1.8.5 3.3 1.7 4.4 3.4 1.1-1.7 2.6-2.9 4.4-3.4-2.3-.8-3.9-2.5-4.4-4.9z"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14a4 4 0 017.7-1.5A3.2 3.2 0 0117 18H8.3A3.2 3.2 0 018 14zM10 19v2M14 19v2"/></svg>
                            @endif
                        </span>
                        <p class="text-sm font-semibold leading-relaxed text-cyra-ink">
                            {{ $insight['message'] }}
                        </p>
                    </li>
                @endforeach
            </ul>

            <a
                href="{{ route('ai.assistant') }}"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl border-2 border-cyra-forest bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:bg-cyra-mint"
            >
                Open full chat
            </a>
        </aside>
    </div>
</x-dashboard-layout>
