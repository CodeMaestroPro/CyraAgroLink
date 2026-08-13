<x-dashboard-layout
    title="Mobile App Screens - Preview"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Mobile App Preview'],
    ]"
>
    <x-page-header
        title="Mobile App Preview"
        description="Preview of the CyraAgroLink mobile experience across key screens."
    />

    <div class="overflow-x-auto pb-4">
        <div class="flex min-w-max items-start justify-center gap-5 lg:gap-6">
            {{-- 1. Dashboard --}}
            <x-mobile.phone-frame active="home">
                <div class="flex h-full flex-col px-3.5 pb-2">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-[15px] font-extrabold leading-snug text-cyra-forest">
                            Good morning, {{ $greetingName }} 👋
                        </h2>
                        <span class="inline-flex h-8 w-8 shrink-0 overflow-hidden rounded-full bg-cyra-mint ring-1 ring-cyra-line">
                            <img src="{{ asset('images/avatars/adewale.jpg') }}" alt="" class="h-full w-full object-cover">
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-cyra-line">
                            <p class="text-[10px] font-medium text-cyra-muted">Farms</p>
                            <p class="mt-0.5 text-lg font-extrabold tabular-nums text-cyra-ink">{{ $dashboard['farms'] }}</p>
                            <p class="text-[10px] font-semibold text-cyra-forest">{{ $dashboard['farms_change'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-cyra-line">
                            <p class="text-[10px] font-medium text-cyra-muted">Crops</p>
                            <p class="mt-0.5 text-lg font-extrabold tabular-nums text-cyra-ink">{{ $dashboard['crops'] }}</p>
                            <p class="text-[10px] font-semibold text-cyra-forest">{{ $dashboard['crops_change'] }}</p>
                        </div>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between rounded-xl bg-white px-3 py-2.5 shadow-sm ring-1 ring-cyra-line">
                        <div>
                            <p class="text-[10px] font-medium text-cyra-muted">Wallet Balance</p>
                            <p class="text-sm font-extrabold tabular-nums text-cyra-ink">{{ $dashboard['wallet_balance'] }}</p>
                        </div>
                        <svg class="h-4 w-4 text-cyra-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                    </div>

                    <p class="mt-3 text-[11px] font-bold text-cyra-ink">Quick Actions</p>
                    <div class="mt-2 grid grid-cols-4 gap-1.5 text-center">
                        @foreach ([['Market', 'M4 7h16l-1.2 11.2A2 2 0 0116.81 20H7.19a2 2 0 01-1.99-1.8L4 7z'], ['Invest', 'M4 19V5m0 14h16M8 15l3-3 2 2 5-5'], ['AI Assistant', 'M12 3v3m0 12v3M3 12h3m12 0h3M6.3 6.3l2.1 2.1m7.2 7.2l2.1 2.1M17.7 6.3l-2.1 2.1M6.3 17.7l2.1-2.1'], ['Logistics', 'M3 7h11v8H3V7zm11 3h3l3 3v2h-6v-5zM6 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z']] as [$label, $path])
                            <div>
                                <span class="mx-auto inline-flex h-10 w-10 items-center justify-center rounded-full bg-cyra-forest text-white">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/></svg>
                                </span>
                                <p class="mt-1 text-[8px] font-semibold leading-tight text-cyra-ink">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-3 text-[11px] font-bold text-cyra-ink">Recent Activity</p>
                    <div class="mt-2 flex items-center gap-2.5 rounded-xl bg-white px-2.5 py-2 shadow-sm ring-1 ring-cyra-line">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m0 0l-5-5m5 5l5-5"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[11px] font-semibold text-cyra-ink">{{ $dashboard['activity']['title'] }}</p>
                            <p class="truncate text-[9px] text-cyra-muted">{{ $dashboard['activity']['meta'] }}</p>
                        </div>
                        <span class="text-[11px] font-bold text-cyra-forest">{{ $dashboard['activity']['amount'] }}</span>
                    </div>
                </div>
            </x-mobile.phone-frame>

            {{-- 2. Marketplace --}}
            <x-mobile.phone-frame active="market">
                <div class="flex h-full flex-col px-3.5 pb-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[15px] font-extrabold text-cyra-ink">Marketplace</h2>
                        <svg class="h-4 w-4 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    </div>

                    <div class="mt-2.5 flex items-center gap-2 rounded-xl bg-white px-2.5 py-2 ring-1 ring-cyra-line">
                        <svg class="h-3.5 w-3.5 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                        <span class="text-[11px] text-cyra-muted">Search commodities...</span>
                    </div>

                    <div class="mt-2.5 flex gap-1.5 overflow-hidden">
                        @foreach ($marketplace['filters'] as $index => $filter)
                            <span @class([
                                'shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold',
                                'bg-cyra-forest text-white' => $index === 0,
                                'bg-white text-cyra-ink ring-1 ring-cyra-line' => $index !== 0,
                            ])>{{ $filter }}</span>
                        @endforeach
                    </div>

                    <div class="mt-2.5 grid grid-cols-2 gap-2 overflow-hidden">
                        @foreach (array_slice($marketplace['products'], 0, 4) as $product)
                            <article class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-cyra-line">
                                <div class="aspect-square overflow-hidden bg-cyra-panel">
                                    <img src="{{ asset($product['image']) }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
                                </div>
                                <div class="p-1.5">
                                    <p class="truncate text-[10px] font-bold text-cyra-ink">{{ $product['name'] }}</p>
                                    <p class="truncate text-[9px] font-semibold text-cyra-forest">{{ $product['price'] }}</p>
                                    <p class="truncate text-[8px] text-cyra-muted">{{ $product['farm'] }}</p>
                                    <p class="mt-0.5 text-[9px] font-semibold text-cyra-sun">★ {{ $product['rating'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </x-mobile.phone-frame>

            {{-- 3. Investments --}}
            <x-mobile.phone-frame active="invest">
                <div class="flex h-full flex-col px-3.5 pb-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[15px] font-extrabold text-cyra-ink">Investments</h2>
                        <svg class="h-4 w-4 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h10M4 18h16"/></svg>
                    </div>

                    <div class="mt-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-cyra-line">
                        <p class="text-[11px] font-bold text-cyra-ink">My Portfolio</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[9px] text-cyra-muted">Total Invested</p>
                                <p class="text-[12px] font-extrabold tabular-nums text-cyra-ink">{{ $investments['invested'] }}</p>
                                <p class="text-[9px] font-semibold text-cyra-forest">{{ $investments['invested_change'] }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] text-cyra-muted">Total Returns</p>
                                <p class="text-[12px] font-extrabold tabular-nums text-cyra-ink">{{ $investments['returns'] }}</p>
                                <p class="text-[9px] font-semibold text-cyra-forest">{{ $investments['returns_change'] }}</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-[11px] font-bold text-cyra-ink">Active Investments</p>
                    <div class="mt-2 space-y-2 overflow-hidden">
                        @foreach ($investments['active'] as $item)
                            <article class="flex items-center gap-2.5 rounded-xl bg-white p-2 shadow-sm ring-1 ring-cyra-line">
                                <img src="{{ asset($item['image']) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-bold text-cyra-ink">{{ $item['name'] }}</p>
                                    <div class="mt-0.5 flex gap-2 text-[9px] font-semibold text-cyra-forest">
                                        <span>ROI {{ $item['roi'] }}</span>
                                        <span>{{ $item['progress'] }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </x-mobile.phone-frame>

            {{-- 4. AI Assistant --}}
            <x-mobile.phone-frame active="home">
                <div class="flex h-full flex-col px-3.5 pb-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-cyra-ink" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/></svg>
                            <h2 class="text-[15px] font-extrabold text-cyra-ink">AI Assistant</h2>
                        </div>
                        <svg class="h-4 w-4 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/></svg>
                    </div>

                    <p class="mt-8 text-center text-sm font-extrabold text-cyra-ink">How can I help you today?</p>

                    <div class="mt-4 space-y-2">
                        @foreach ($aiSuggestions as $suggestion)
                            <button type="button" class="flex w-full items-center gap-2.5 rounded-xl border border-cyra-forest/30 bg-white px-3 py-2.5 text-left shadow-sm">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                                    @if ($suggestion['icon'] === 'leaf')
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/></svg>
                                    @elseif ($suggestion['icon'] === 'flask')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3h6M10 3v6l-5 9a2 2 0 001.7 3h10.6a2 2 0 001.7-3l-5-9V3"/></svg>
                                    @elseif ($suggestion['icon'] === 'calendar')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v3m8-3v3M4 9h16M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 15a4 4 0 004 4h10a4 4 0 100-8 5.5 5.5 0 00-10.7 1.5A4 4 0 003 15z"/></svg>
                                    @endif
                                </span>
                                <span class="text-[11px] font-semibold text-cyra-ink">{{ $suggestion['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-auto flex items-center gap-2 pt-3">
                        <div class="flex-1 rounded-full bg-white px-3 py-2 text-[11px] text-cyra-muted ring-1 ring-cyra-line">
                            Type a message...
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-cyra-forest text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14M13 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </div>
            </x-mobile.phone-frame>

            {{-- 5. Wallet --}}
            <x-mobile.phone-frame active="profile">
                <div class="flex h-full flex-col px-3.5 pb-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[15px] font-extrabold text-cyra-ink">Wallet</h2>
                        <svg class="h-4 w-4 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </div>

                    <div class="mt-3 rounded-2xl bg-gradient-to-br from-cyra-mint via-white to-cyra-soft/60 p-4 shadow-sm ring-1 ring-cyra-line">
                        <p class="text-[11px] font-medium text-cyra-muted">Balance</p>
                        <p class="mt-1 text-xl font-extrabold tabular-nums text-cyra-ink">{{ $wallet['balance'] }}</p>
                        <div class="mt-3 grid grid-cols-3 gap-1.5">
                            @foreach (['Deposit', 'Withdraw', 'Transfer'] as $action)
                                <span class="rounded-lg bg-cyra-forest px-1 py-2 text-center text-[9px] font-bold text-white">{{ $action }}</span>
                            @endforeach
                        </div>
                    </div>

                    <p class="mt-3 text-[11px] font-bold text-cyra-ink">Recent Transactions</p>
                    <div class="mt-2 space-y-2">
                        @foreach ($wallet['transactions'] as $tx)
                            <div class="flex items-center gap-2.5 rounded-xl bg-white px-2.5 py-2 shadow-sm ring-1 ring-cyra-line">
                                <span @class([
                                    'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                                    'bg-cyra-mint text-cyra-forest' => $tx['tone'] === 'credit',
                                    'bg-rose-50 text-rose-600' => $tx['tone'] === 'debit',
                                ])>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        @if ($tx['tone'] === 'credit')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m0 0l-5-5m5 5l5-5"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 19V5m0 0l-5 5m5-5l5 5"/>
                                        @endif
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-cyra-ink">{{ $tx['title'] }}</p>
                                    <p class="truncate text-[9px] text-cyra-muted">{{ $tx['meta'] }}</p>
                                </div>
                                <span @class([
                                    'text-[11px] font-bold tabular-nums',
                                    'text-cyra-forest' => $tx['tone'] === 'credit',
                                    'text-rose-600' => $tx['tone'] === 'debit',
                                ])>{{ $tx['amount'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-mobile.phone-frame>
        </div>
    </div>
</x-dashboard-layout>
