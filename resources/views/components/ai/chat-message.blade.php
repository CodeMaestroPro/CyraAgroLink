@props([
    'message',
])

@php
    $role = $message['role'] ?? 'assistant';
    $type = $message['type'] ?? 'text';
@endphp

@if ($role === 'user')
    <div class="flex justify-end">
        <div class="max-w-[85%] rounded-2xl rounded-br-md bg-cyra-forest px-4 py-3 text-sm leading-relaxed text-white shadow-sm sm:max-w-[70%]">
            {{ $message['body'] }}
        </div>
    </div>
@elseif ($type === 'diagnosis')
    <div class="flex gap-3" x-data="{ showGuide: false }">
        <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
            </svg>
        </span>

        <div class="min-w-0 flex-1 rounded-2xl rounded-tl-md bg-cyra-surface/80 p-4 ring-1 ring-cyra-line/80 sm:p-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_9rem] sm:items-start lg:grid-cols-[1fr_11rem]">
                <div>
                    <p class="text-sm leading-relaxed text-cyra-ink">
                        {{ $message['intro'] }}
                        <span class="font-extrabold text-cyra-forest">{{ $message['diagnosis'] }}</span>
                    </p>

                    <p class="mt-4 text-sm font-extrabold text-cyra-ink">
                        {{ $message['recommendations_title'] }}
                    </p>
                    <ul class="mt-2 space-y-1.5 text-sm text-cyra-ink/90">
                        @foreach ($message['recommendations'] as $item)
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyra-forest" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mx-auto h-36 w-36 overflow-hidden rounded-2xl bg-cyra-panel ring-1 ring-cyra-line sm:mx-0 sm:h-full sm:min-h-[9rem] sm:w-full">
                    <img
                        src="{{ $message['image'] }}"
                        alt="Diagnosed maize leaf blight sample"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>

            <div class="mt-5 border-t border-cyra-line/80 pt-4">
                <p class="text-sm font-medium text-cyra-ink">{{ $message['cta_prompt'] }}</p>
                <div class="mt-3 flex flex-wrap gap-2.5">
                    <button
                        type="button"
                        @click="showGuide = true"
                        class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        Yes, show me
                    </button>
                    <button
                        type="button"
                        @click="showGuide = false"
                        class="inline-flex items-center justify-center rounded-xl border border-cyra-line bg-white px-4 py-2 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-surface"
                    >
                        Not now
                    </button>
                </div>

                <div
                    x-cloak
                    x-show="showGuide"
                    x-transition
                    class="mt-4 rounded-xl bg-white p-4 text-sm leading-relaxed text-cyra-ink ring-1 ring-cyra-line"
                >
                    {{ $message['treatment_guide'] }}
                </div>
            </div>
        </div>
    </div>
@else
    <div class="flex gap-3">
        <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
            </svg>
        </span>
        <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-cyra-surface/80 px-4 py-3 text-sm leading-relaxed text-cyra-ink ring-1 ring-cyra-line/80 sm:max-w-[75%]">
            {{ $message['body'] }}
        </div>
    </div>
@endif
