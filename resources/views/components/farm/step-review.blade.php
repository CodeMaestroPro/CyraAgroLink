@props([
    'farm',
    'readOnly' => false,
])

@php
    $documents = is_array($farm->documents) ? $farm->documents : [];
    $docLabels = [
        'land_title' => 'Land title',
        'farm_certificate' => 'Farm certificate',
        'identity_document' => 'Identity document',
    ];
@endphp

<div>
    <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
        {{ $readOnly ? 'Registration submitted' : 'Review' }}
    </h2>
    <p class="mt-1 text-sm text-cyra-muted">
        @if ($readOnly)
            Status: <span class="font-semibold text-cyra-forest">{{ str_replace('_', ' ', $farm->status->value) }}</span>
        @else
            Confirm your farm registration details before submitting
        @endif
    </p>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Farm Name</dt>
            <dd class="mt-1 text-sm font-bold text-cyra-ink">{{ $farm->name ?: '—' }}</dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Location</dt>
            <dd class="mt-1 text-sm font-bold text-cyra-ink">
                {{ $farm->address ?: '—' }}
                @if ($farm->local_government || $farm->state)
                    <span class="block font-medium text-cyra-muted">
                        {{ collect([$farm->local_government, $farm->state ? $farm->state.' State' : null])->filter()->implode(', ') }}
                    </span>
                @endif
            </dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Coordinates</dt>
            <dd class="mt-1 text-sm font-bold text-cyra-ink">{{ $farm->formattedCoordinates() ?: '—' }}</dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Size</dt>
            <dd class="mt-1 text-sm font-bold text-cyra-ink">
                {{ $farm->size_hectares ? $farm->size_hectares.' ha' : '—' }}
            </dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Soil Type</dt>
            <dd class="mt-1 text-sm font-bold text-cyra-ink">{{ $farm->soil_type ?: '—' }}</dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Description</dt>
            <dd class="mt-1 text-sm font-medium text-cyra-ink">{{ $farm->description ?: '—' }}</dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Enterprises</dt>
            <dd class="mt-2 flex flex-wrap gap-2">
                @forelse (($farm->crops ?? []) as $crop)
                    <span class="rounded-full bg-cyra-mint px-3 py-1 text-xs font-semibold text-cyra-forest">{{ $crop }}</span>
                @empty
                    <span class="text-sm text-cyra-muted">No enterprises selected</span>
                @endforelse
            </dd>
        </div>
        <div class="rounded-xl bg-cyra-surface p-4 ring-1 ring-cyra-line sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-cyra-muted">Documents</dt>
            <dd class="mt-2 space-y-1.5">
                @forelse ($docLabels as $key => $label)
                    @if (! empty($documents[$key]['path']))
                        <p class="text-sm text-cyra-ink">
                            <span class="font-semibold">{{ $label }}:</span>
                            <a href="{{ asset('storage/'.$documents[$key]['path']) }}" target="_blank" rel="noopener" class="text-cyra-forest underline">
                                {{ $documents[$key]['original_name'] ?? 'View file' }}
                            </a>
                        </p>
                    @endif
                @empty
                @endforelse
                @if (collect($documents)->filter(fn ($d) => ! empty($d['path'] ?? null))->isEmpty())
                    <span class="text-sm text-cyra-muted">No documents uploaded</span>
                @endif
            </dd>
        </div>
    </dl>
</div>
