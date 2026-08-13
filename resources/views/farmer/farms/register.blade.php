<x-dashboard-layout
    title="Farm Registration"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Farm Registration'],
    ]"
>
    <x-page-header
        title="Farm Registration"
        description="Register your farm in five steps — location, details, enterprises (crops, poultry, fish, livestock), documents, and review."
    >
        <x-slot:actions>
            <a
                href="{{ route('farms.register', ['new' => 1]) }}"
                class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:bg-cyra-soft"
            >
                Register another farm
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($farms->isNotEmpty())
        <section class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5" aria-label="Your farms">
            <h2 class="text-sm font-extrabold text-cyra-ink">Your farms</h2>
            <ul class="mt-3 divide-y divide-cyra-line/80">
                @foreach ($farms as $item)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-cyra-ink">{{ $item->name ?: 'Untitled draft' }}</p>
                            <p class="text-xs text-cyra-muted">
                                {{ $item->state ?: 'No state' }} · {{ str_replace('_', ' ', $item->status->value) }}
                                · Step {{ $item->registration_step }}/5
                            </p>
                        </div>
                        <a
                            href="{{ route('farms.register', ['farm' => $item->id, 'step' => $item->status->value === 'draft' ? $item->registration_step : 5]) }}"
                            class="shrink-0 text-sm font-bold text-cyra-forest hover:underline"
                        >
                            {{ $item->status->value === 'draft' ? 'Continue' : 'View' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <x-farm.wizard-steps :steps="$steps" :current="$step" />

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-8">
        @if ($readOnly)
            <div class="space-y-8">
                <x-farm.step-review :farm="$farm" :read-only="true" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a
                        href="{{ route('farms.register', ['new' => 1]) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        Register another farm
                    </a>
                </div>
            </div>
        @elseif ($step === 1)
            <form method="POST" action="{{ route('farms.register.location', $farm) }}" class="space-y-8">
                @csrf
                <x-farm.step-location :farm="$farm" :states="$states" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border-2 border-cyra-forest bg-white px-5 py-2.5 text-sm font-semibold text-cyra-forest transition hover:bg-cyra-mint"
                    >
                        Back
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
                    >
                        Next
                    </button>
                </div>
            </form>
        @elseif ($step === 2)
            <form method="POST" action="{{ route('farms.register.details', $farm) }}" class="space-y-8">
                @csrf
                <x-farm.step-details :farm="$farm" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a href="{{ route('farms.register', ['farm' => $farm->id, 'step' => 1]) }}" class="inline-flex items-center justify-center rounded-lg border border-cyra-line bg-white px-5 py-2.5 text-sm font-semibold text-cyra-muted transition hover:bg-cyra-surface">Back</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">Next</button>
                </div>
            </form>
        @elseif ($step === 3)
            <form method="POST" action="{{ route('farms.register.crops', $farm) }}" class="space-y-8">
                @csrf
                <x-farm.step-crops :farm="$farm" :crop-options="$cropOptions" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a href="{{ route('farms.register', ['farm' => $farm->id, 'step' => 2]) }}" class="inline-flex items-center justify-center rounded-lg border border-cyra-line bg-white px-5 py-2.5 text-sm font-semibold text-cyra-muted transition hover:bg-cyra-surface">Back</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">Next</button>
                </div>
            </form>
        @elseif ($step === 4)
            <form method="POST" action="{{ route('farms.register.documents', $farm) }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                <x-farm.step-documents :farm="$farm" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a href="{{ route('farms.register', ['farm' => $farm->id, 'step' => 3]) }}" class="inline-flex items-center justify-center rounded-lg border border-cyra-line bg-white px-5 py-2.5 text-sm font-semibold text-cyra-muted transition hover:bg-cyra-surface">Back</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">Next</button>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('farms.register.submit', $farm) }}" class="space-y-8">
                @csrf
                <x-farm.step-review :farm="$farm" />
                <div class="flex items-center justify-end gap-3 border-t border-cyra-line pt-6">
                    <a href="{{ route('farms.register', ['farm' => $farm->id, 'step' => 4]) }}" class="inline-flex items-center justify-center rounded-lg border border-cyra-line bg-white px-5 py-2.5 text-sm font-semibold text-cyra-muted transition hover:bg-cyra-surface">Back</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">Submit Registration</button>
                </div>
            </form>
        @endif
    </div>
</x-dashboard-layout>
