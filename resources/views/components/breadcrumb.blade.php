@props([
    'items' => [],
])

@php
    /** @var list<array{label: string, href?: string|null}> $items */
    $items = array_values($items);
@endphp

@if (count($items) > 0)
    <nav class="mb-4" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-cyra-muted">
            @foreach ($items as $index => $item)
                <li class="inline-flex items-center gap-1.5">
                    @if ($index > 0)
                        <svg class="h-3.5 w-3.5 shrink-0 text-cyra-line" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.17 10 7.23 6.29a.75.75 0 111.04-1.08l4.25 4.21a.75.75 0 010 1.08l-4.25 4.21a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    @endif

                    @if (! empty($item['href']) && $index < count($items) - 1)
                        <a href="{{ $item['href'] }}" class="font-medium transition hover:text-cyra-forest">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span @class([
                            'font-display font-semibold text-cyra-ink' => $index === count($items) - 1,
                            'font-medium' => $index !== count($items) - 1,
                        ])>
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
