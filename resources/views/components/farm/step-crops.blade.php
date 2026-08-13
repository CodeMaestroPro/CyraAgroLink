@props([
    'farm',
    'cropOptions' => [],
])

@php
    $selected = collect(old('crops', $farm->crops ?? []))->map(fn ($c) => (string) $c)->all();
@endphp

<div>
    <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">Enterprises</h2>
    <p class="mt-1 text-sm text-cyra-muted">
        Select what this farm produces — crops, poultry (broilers/layers), fish farming, livestock, and related activities.
    </p>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($cropOptions as $crop)
            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-cyra-line bg-white px-3 py-3 text-sm font-medium text-cyra-ink transition hover:border-cyra-forest has-[:checked]:border-cyra-forest has-[:checked]:bg-cyra-mint">
                <input
                    type="checkbox"
                    name="crops[]"
                    value="{{ $crop }}"
                    @checked(in_array($crop, $selected, true))
                    class="rounded border-cyra-line text-cyra-forest focus:ring-cyra-forest"
                >
                {{ $crop }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('crops')" class="mt-2" />
</div>
