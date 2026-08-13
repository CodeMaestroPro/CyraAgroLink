@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-cyra-forest']) }}>
        {{ $status }}
    </div>
@endif
