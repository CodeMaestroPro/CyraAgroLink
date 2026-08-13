<x-dashboard-layout
    title="Crop Management"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Crop Management'],
    ]"
>
    <x-page-header
        title="{{ $crop->displayTitle() }}"
        description="Track growth, care, health, and harvest for crop cycles and related farm enterprises."
    >
        <x-slot:actions>
            <a
                href="#add-crop"
                class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:bg-cyra-soft"
            >
                Add crop
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/40 px-4 py-3 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-forest/20" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($crops->count() > 1)
        <section class="mb-5 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line" aria-label="Your crop cycles">
            <h2 class="text-sm font-extrabold text-cyra-ink">Your crop cycles</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($crops as $item)
                    <a
                        href="{{ route('crops.manage', ['crop' => $item->id, 'tab' => $activeTab]) }}"
                        @class([
                            'rounded-full px-3 py-1.5 text-xs font-bold ring-1 transition',
                            'bg-cyra-forest text-white ring-cyra-forest' => $item->id === $crop->id,
                            'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-surface' => $item->id !== $crop->id,
                        ])
                    >
                        {{ $item->name }} · {{ $item->status }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <nav class="-mt-2 flex gap-5 overflow-x-auto border-b border-cyra-line" aria-label="Crop sections">
        @foreach ($tabs as $tab)
            <a
                href="{{ route('crops.manage', ['crop' => $crop->id, 'tab' => $tab['key']]) }}"
                @class([
                    'whitespace-nowrap border-b-2 pb-3 text-sm font-semibold transition',
                    'border-cyra-forest text-cyra-forest' => $activeTab === $tab['key'],
                    'border-transparent text-cyra-muted hover:text-cyra-ink' => $activeTab !== $tab['key'],
                ])
                @if ($activeTab === $tab['key']) aria-current="page" @endif
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-6 space-y-6">
        @if ($activeTab === 'overview')
            <x-crop.overview
                :crop="$crop"
                :stages="$stages"
                :timeline-percent="$timeline_percent"
            />

            @if ($crop->status === 'active' && $crop->growth_stage->value !== 'maturity')
                <form method="POST" action="{{ route('crops.advance-stage', $crop) }}" class="flex justify-end">
                    @csrf
                    <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyra-green">
                        Advance growth stage
                    </button>
                </form>
            @endif

            <section class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                <h3 class="text-base font-extrabold text-cyra-ink">Recent activity</h3>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @forelse ($activities as $activity)
                        <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-cyra-ink">{{ $activity->title }}</p>
                                <p class="text-xs text-cyra-muted">{{ $activity->type->label() }}@if($activity->quantity) · {{ $activity->quantity }}@endif</p>
                                @if ($activity->notes)
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $activity->notes }}</p>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-cyra-muted">{{ $activity->occurred_at?->format('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-cyra-muted">No activities logged yet.</li>
                    @endforelse
                </ul>
            </section>
        @elseif (in_array($activeTab, ['activities', 'irrigation', 'fertilizer'], true))
            @php
                $formType = match ($activeTab) {
                    'irrigation' => 'irrigation',
                    'fertilizer' => 'fertilizer',
                    default => 'activity',
                };
                $defaultTitle = match ($activeTab) {
                    'irrigation' => 'Irrigation applied',
                    'fertilizer' => 'Fertilizer applied',
                    default => 'Field activity',
                };
            @endphp

            <div class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">Log {{ ucfirst($activeTab === 'activities' ? 'activity' : $activeTab) }}</h3>
                    @if ($crop->status !== 'active')
                        <p class="mt-3 text-sm text-cyra-muted">This crop cycle is closed.</p>
                    @else
                        <form method="POST" action="{{ route('crops.activities.store', $crop) }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="{{ $formType }}">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="title">Title</label>
                                <input id="title" name="title" value="{{ old('title', $defaultTitle) }}" required class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                                <x-input-error :messages="$errors->get('title')" class="mt-1" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="quantity">Quantity / amount</label>
                                <input id="quantity" name="quantity" value="{{ old('quantity') }}" placeholder="e.g. 25 mm or 2 bags NPK" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">{{ old('notes') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green">Save</button>
                        </form>
                    @endif
                </article>

                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">History</h3>
                    <ul class="mt-4 divide-y divide-cyra-line/80">
                        @forelse ($activities as $activity)
                            <li class="py-3">
                                <p class="font-semibold text-cyra-ink">{{ $activity->title }}</p>
                                <p class="text-xs text-cyra-muted">{{ $activity->occurred_at?->format('d M Y, H:i') }}@if($activity->quantity) · {{ $activity->quantity }}@endif</p>
                                @if ($activity->notes)
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $activity->notes }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-sm text-cyra-muted">No records yet for this section.</li>
                        @endforelse
                    </ul>
                </article>
            </div>
        @elseif ($activeTab === 'health')
            <div class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">Update health status</h3>
                    @if ($crop->status !== 'active')
                        <p class="mt-3 text-sm text-cyra-muted">This crop cycle is closed.</p>
                    @else
                        <form method="POST" action="{{ route('crops.activities.store', $crop) }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="health">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="health_status">Status</label>
                                <select id="health_status" name="health_status" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                                    @foreach ($health_statuses as $status)
                                        <option value="{{ $status->value }}" @selected(old('health_status', $crop->health_status->value) === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="health_notes">Notes</label>
                                <textarea id="health_notes" name="health_notes" rows="3" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">{{ old('health_notes', $crop->health_notes) }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green">Update health</button>
                        </form>
                    @endif
                </article>
                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">Health log</h3>
                    <ul class="mt-4 divide-y divide-cyra-line/80">
                        @forelse ($activities as $activity)
                            <li class="py-3">
                                <p class="font-semibold text-cyra-ink">{{ $activity->title }}</p>
                                <p class="text-xs text-cyra-muted">{{ $activity->occurred_at?->format('d M Y') }}</p>
                                @if ($activity->notes)
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $activity->notes }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-sm text-cyra-muted">No health updates yet.</li>
                        @endforelse
                    </ul>
                </article>
            </div>
        @elseif ($activeTab === 'harvest')
            <div class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">Record harvest</h3>
                    <p class="mt-1 text-sm text-cyra-muted">
                        Expected: {{ $crop->expected_harvest_at?->format('d M Y') ?: 'not set' }}
                    </p>
                    @if ($crop->status !== 'active')
                        <p class="mt-3 rounded-lg bg-cyra-mint/40 px-3 py-2 text-sm font-semibold text-cyra-forest">Harvest already recorded for this cycle.</p>
                    @else
                        <form method="POST" action="{{ route('crops.activities.store', $crop) }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="harvest">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="quantity">Yield quantity</label>
                                <input id="quantity" name="quantity" value="{{ old('quantity') }}" placeholder="e.g. 4.5 tons" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">{{ old('notes') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green">Complete harvest</button>
                        </form>
                    @endif
                </article>
                <article class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
                    <h3 class="text-base font-extrabold text-cyra-ink">Harvest history</h3>
                    <ul class="mt-4 divide-y divide-cyra-line/80">
                        @forelse ($activities as $activity)
                            <li class="py-3">
                                <p class="font-semibold text-cyra-ink">{{ $activity->title }}</p>
                                <p class="text-xs text-cyra-muted">{{ $activity->occurred_at?->format('d M Y') }}@if($activity->quantity) · {{ $activity->quantity }}@endif</p>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-cyra-muted">No harvest records yet.</li>
                        @endforelse
                    </ul>
                </article>
            </div>
        @endif

        <section id="add-crop" class="rounded-xl bg-white p-5 ring-1 ring-cyra-line">
            <h3 class="text-base font-extrabold text-cyra-ink">Add enterprise cycle</h3>
            @if ($farms->isEmpty())
                <p class="mt-2 text-sm text-cyra-muted">
                    Register a farm first, then add crop cycles here.
                    <a href="{{ route('farms.register') }}" class="font-semibold text-cyra-forest underline">Go to Farm Registration</a>
                </p>
            @else
                <form method="POST" action="{{ route('crops.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="farm_id">Farm</label>
                        <select id="farm_id" name="farm_id" required class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                            @foreach ($farms as $farm)
                                <option value="{{ $farm->id }}" @selected((int) old('farm_id', $crop->farm_id) === $farm->id)>{{ $farm->name ?: 'Farm #'.$farm->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="name">Crop</label>
                        <select id="name" name="name" required class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                            @foreach ($crop_options as $option)
                                <option value="{{ $option }}" @selected(old('name') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="variety">Variety</label>
                        <input id="variety" name="variety" value="{{ old('variety') }}" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="growth_stage">Starting stage</label>
                        <select id="growth_stage" name="growth_stage" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                            @foreach ($growth_stages as $stage)
                                <option value="{{ $stage->value }}" @selected(old('growth_stage', 'seedling') === $stage->value)>{{ $stage->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="planted_at">Planted date</label>
                        <input id="planted_at" type="date" name="planted_at" value="{{ old('planted_at', now()->toDateString()) }}" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-cyra-ink" for="expected_harvest_at">Expected harvest</label>
                        <input id="expected_harvest_at" type="date" name="expected_harvest_at" value="{{ old('expected_harvest_at', now()->addDays(90)->toDateString()) }}" class="w-full rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green">Create crop cycle</button>
                    </div>
                </form>
            @endif
        </section>
    </div>
</x-dashboard-layout>
