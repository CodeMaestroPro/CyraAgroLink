<footer {{ $attributes->merge(['class' => 'mt-12 border-t border-cyra-line/80 bg-white']) }}>
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
        <p class="text-xs text-cyra-muted">&copy; {{ date('Y') }} {{ config('cyra.brand') }}. All rights reserved.</p>
        <nav class="flex flex-wrap items-center gap-4 text-xs font-medium text-cyra-muted" aria-label="Footer">
            <a href="{{ route('home') }}" class="transition hover:text-cyra-forest">Home</a>
            <a href="{{ route('home') }}#marketplace" class="transition hover:text-cyra-forest">Marketplace</a>
            <a href="{{ route('home') }}#invest" class="transition hover:text-cyra-forest">Invest</a>
            <a href="{{ route('home') }}#logistics" class="transition hover:text-cyra-forest">Logistics</a>
            <a href="{{ route('home') }}#resources" class="transition hover:text-cyra-forest">Resources</a>
            <a href="{{ route('home') }}#about" class="transition hover:text-cyra-forest">About</a>
            @auth
                <a href="{{ route('dashboard') }}" class="transition hover:text-cyra-forest">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="transition hover:text-cyra-forest">Sign in</a>
            @endauth
        </nav>
    </div>
</footer>
