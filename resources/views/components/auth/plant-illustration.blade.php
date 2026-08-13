@props([
    'class' => 'h-64 w-64',
])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    {{-- Soft glow — homepage greens --}}
    <circle cx="128" cy="128" r="108" fill="#10853F" fill-opacity="0.18"/>
    <circle cx="128" cy="128" r="86" stroke="#B8E0C4" stroke-opacity="0.55" stroke-width="1.5" stroke-dasharray="4 6"/>
    <circle cx="128" cy="128" r="70" stroke="#7BC48E" stroke-opacity="0.4" stroke-width="1"/>

    {{-- Grid arcs --}}
    <path d="M58 128c0-38.7 31.3-70 70-70" stroke="#E8F5E9" stroke-opacity="0.5" stroke-width="1.2"/>
    <path d="M198 128c0 38.7-31.3 70-70 70" stroke="#E8F5E9" stroke-opacity="0.5" stroke-width="1.2"/>

    {{-- Soil mound --}}
    <ellipse cx="128" cy="168" rx="42" ry="14" fill="#5C4033"/>
    <ellipse cx="128" cy="164" rx="36" ry="10" fill="#6D4C41"/>
    <ellipse cx="118" cy="160" rx="5" ry="3" fill="#8D6E63" opacity="0.7"/>
    <ellipse cx="140" cy="162" rx="4" ry="2.5" fill="#8D6E63" opacity="0.55"/>

    {{-- Stem --}}
    <path d="M128 164c1-18 2-34 1-48" stroke="#1A9B4C" stroke-width="3.5" stroke-linecap="round"/>

    {{-- Leaves --}}
    <path d="M129 128c-18-4-30-16-32-30 18 2 32 14 32 30Z" fill="#2F8F4E"/>
    <path d="M129 128c-18-4-30-16-32-30 18 2 32 14 32 30Z" fill="#B8E0C4" fill-opacity="0.35"/>
    <path d="M127 120c18-6 28-18 30-32-16 4-28 16-30 32Z" fill="#10853F"/>
    <path d="M128 112c-8-14-8-28 2-38 2 14 4 26-2 38Z" fill="#7BC48E"/>

    {{-- Particles --}}
    <circle cx="78" cy="96" r="2.5" fill="#E8F5E9" opacity="0.9"/>
    <circle cx="186" cy="110" r="2" fill="#E8F5E9" opacity="0.8"/>
    <circle cx="172" cy="168" r="1.8" fill="#B8E0C4" opacity="0.7"/>
    <circle cx="92" cy="150" r="1.5" fill="#B8E0C4" opacity="0.7"/>
    <circle cx="160" cy="84" r="1.5" fill="#E8F5E9" opacity="0.9"/>
</svg>
