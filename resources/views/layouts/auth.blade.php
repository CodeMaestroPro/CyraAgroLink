<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', ($title ?? 'Login').' — '.config('cyra.brand'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:500,600,700,800|plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-cyra-ink bg-cyra-panel">
    <div class="flex min-h-screen flex-col">
        <div class="flex flex-1 items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-black/5">
                <div class="grid lg:grid-cols-2">
                    {{-- Brand panel --}}
                    <aside class="relative flex flex-col bg-cyra-forest px-8 py-10 text-white sm:px-10 lg:min-h-[640px]">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10">
                                <svg class="h-5 w-5 text-cyra-soft" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                                    <path d="M11.2 13.5v7.2c0 .4.4.8.8.8s.8-.4.8-.8v-7.2c-.5.3-1.1.3-1.6 0z" fill="currentColor" opacity="0.85"/>
                                </svg>
                            </span>
                            <span class="font-display text-lg font-extrabold tracking-tight">{{ config('cyra.brand') }}</span>
                        </a>

                        <div class="mt-8">
                            <h1 class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                                {{ $heading ?? 'Welcome Back!' }}
                            </h1>
                            <p class="mt-3 max-w-sm text-sm leading-relaxed text-white/80 sm:text-base">
                                {{ $subheading ?? 'Login to continue to your '.config('cyra.brand').' account' }}
                            </p>
                        </div>

                        <div class="relative my-10 flex flex-1 items-center justify-center lg:my-0">
                            <x-auth.plant-illustration class="h-56 w-56 sm:h-64 sm:w-64" />
                        </div>

                        <p class="mt-auto text-center text-xs leading-relaxed text-white/70 sm:text-sm">
                            Empowering Africa's agriculture through technology and innovation.
                        </p>
                    </aside>

                    {{-- Form panel --}}
                    <section class="flex flex-col justify-center bg-white px-6 py-10 sm:px-10 lg:px-12">
                        {{ $slot }}
                    </section>
                </div>
            </div>
        </div>

        <x-app-footer class="bg-transparent" />
    </div>
</body>
</html>
