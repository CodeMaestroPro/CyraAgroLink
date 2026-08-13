@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-cyra-line focus:border-cyra-forest focus:ring-cyra-forest rounded-md shadow-sm']) }}>
