@props(['farm'])

<div>
    <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">Farm Details</h2>
    <p class="mt-1 text-sm text-cyra-muted">Tell us more about your farm</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Farm Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $farm->name) }}"
                required
                placeholder="e.g. Green Valley Farm"
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="size_hectares" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Size (hectares)</label>
            <input
                id="size_hectares"
                type="number"
                step="0.01"
                min="0.01"
                name="size_hectares"
                value="{{ old('size_hectares', $farm->size_hectares) }}"
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('size_hectares')" class="mt-2" />
        </div>

        <div>
            <label for="soil_type" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Soil Type</label>
            <input
                id="soil_type"
                type="text"
                name="soil_type"
                value="{{ old('soil_type', $farm->soil_type) }}"
                placeholder="e.g. Loamy"
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('soil_type')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Description</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >{{ old('description', $farm->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
    </div>
</div>
