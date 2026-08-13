<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('cyra.brand', config('app.name')) }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:500,600,700,800|plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-cyra-ink antialiased">
        <div class="flex min-h-screen flex-col bg-cyra-panel">
            <div class="flex flex-1 flex-col items-center justify-center px-4 pt-6 sm:pt-0">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <x-marketing.logo :show-tagline="false" class="h-10 w-10" />
                    </a>
                </div>

                <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-soft ring-1 ring-cyra-line sm:max-w-md sm:rounded-2xl">
                    {{ $slot }}
                </div>
            </div>

            <x-app-footer class="bg-transparent" />
        </div>
    </body>
</html>
