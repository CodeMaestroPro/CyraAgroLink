@props([
    'farm',
    'states' => [],
])

@php
    $lat = old('latitude', $farm->latitude ?? 7.3775);
    $lng = old('longitude', $farm->longitude ?? 3.9470);
    $coordsDisplay = old(
        'coordinates_display',
        $farm->formattedCoordinates() ?? sprintf('%.4f° N, %.4f° E', abs((float) $lat), abs((float) $lng))
    );
@endphp

<div
    class="grid gap-8 lg:grid-cols-2 lg:gap-10"
    x-data="farmLocationMap({
        lat: {{ (float) $lat }},
        lng: {{ (float) $lng }}
    })"
>
    <div>
        <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">Farm Location</h2>
        <p class="mt-1 text-sm text-cyra-muted">Select your farm location on the map</p>

        <div class="relative mt-5 overflow-hidden rounded-xl ring-1 ring-cyra-line">
            <div id="farm-map" class="h-72 w-full bg-cyra-mint sm:h-80" role="application" aria-label="Farm location map"></div>

            <button
                type="button"
                class="absolute right-3 top-3 z-[500] inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-cyra-muted shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-ink"
                @click="resetMarker()"
                aria-label="Reset map marker"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="space-y-4 lg:pt-14">
        <div>
            <label for="state" class="mb-1.5 block text-sm font-semibold text-cyra-ink">State</label>
            <div class="relative">
                <select
                    id="state"
                    name="state"
                    required
                    class="block w-full appearance-none rounded-lg border border-cyra-line bg-white px-3.5 py-3 pr-10 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                    <option value="" disabled @selected(old('state', $farm->state) === null)>Select state</option>
                    @foreach ($states as $state)
                        <option value="{{ $state }}" @selected(old('state', $farm->state) === $state)>
                            {{ $state === 'FCT' ? 'FCT' : $state.' State' }}
                        </option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-cyra-muted" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
            <x-input-error :messages="$errors->get('state')" class="mt-2" />
        </div>

        <div>
            <label for="local_government" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Local Government</label>
            <input
                id="local_government"
                type="text"
                name="local_government"
                value="{{ old('local_government', $farm->local_government) }}"
                required
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('local_government')" class="mt-2" />
        </div>

        <div>
            <label for="address" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Address</label>
            <input
                id="address"
                type="text"
                name="address"
                value="{{ old('address', $farm->address) }}"
                required
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div>
            <label for="coordinates_display" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Coordinates</label>
            <input
                id="coordinates_display"
                type="text"
                x-model="coordinatesDisplay"
                readonly
                class="block w-full rounded-lg border border-cyra-line bg-cyra-surface px-3.5 py-3 text-sm text-cyra-ink shadow-sm"
            >
            <input type="hidden" name="latitude" x-model="lat">
            <input type="hidden" name="longitude" x-model="lng">
            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
            <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
        </div>
    </div>
</div>
