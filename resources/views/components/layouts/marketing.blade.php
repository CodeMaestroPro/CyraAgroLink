<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ __('home.common.meta_description') ?: config('cyra.tagline') }}">

    <title>@yield('title', __('home.common.meta_title', ['brand' => config('cyra.brand')]))</title>

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('cyra-theme');
                var theme = stored === 'light' || stored === 'dark'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                document.documentElement.style.colorScheme = theme;
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:500,600,700,800|plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-cyra-ink bg-cyra-surface">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-cyra-forest focus:px-4 focus:py-2 focus:text-white">
        {{ __('home.common.skip_to_content') }}
    </a>

    <div class="flex min-h-screen flex-col">
        <x-marketing.navbar />

        <main id="main-content" class="flex-1">
            {{ $slot }}
        </main>

        <x-site-footer />
    </div>
</body>
</html>
