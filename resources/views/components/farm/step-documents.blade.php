@props(['farm'])

@php
    $documents = is_array($farm->documents) ? $farm->documents : [];
    $slots = [
        'land_title' => 'Land title / survey plan',
        'farm_certificate' => 'Farm certificate',
        'identity_document' => 'Identity document',
    ];
@endphp

<div>
    <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">Documents</h2>
    <p class="mt-1 text-sm text-cyra-muted">Upload supporting documents (optional — PDF or image, max 5MB each)</p>

    <div class="mt-6 grid gap-4">
        @foreach ($slots as $key => $label)
            @php $existing = $documents[$key] ?? null; @endphp
            <div class="rounded-2xl border border-cyra-line bg-white p-4 sm:p-5">
                <label for="{{ $key }}" class="block text-sm font-semibold text-cyra-ink">{{ $label }}</label>
                @if ($existing)
                    <p class="mt-1 text-xs font-medium text-cyra-forest">
                        Uploaded: {{ $existing['original_name'] ?? 'file' }}
                        @if (! empty($existing['path']))
                            — <a href="{{ asset('storage/'.$existing['path']) }}" target="_blank" rel="noopener" class="underline">View</a>
                        @endif
                    </p>
                @endif
                <input
                    id="{{ $key }}"
                    name="{{ $key }}"
                    type="file"
                    accept=".pdf,image/jpeg,image/png,image/webp"
                    class="mt-3 block w-full text-sm text-cyra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-cyra-mint file:px-3 file:py-2 file:text-sm file:font-semibold file:text-cyra-forest"
                >
                <x-input-error :messages="$errors->get($key)" class="mt-2" />
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-cyra-muted">You can continue without uploads and attach documents later if needed.</p>
</div>
