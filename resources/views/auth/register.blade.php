<x-auth-layout
    title="Create Account"
    heading="Join CyraAgroLink"
    subheading="Create your account to access Africa's agricultural digital ecosystem"
>
    <div class="mx-auto w-full max-w-md">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold tracking-tight text-cyra-ink">Create Account</h2>
            <p class="mt-2 text-sm text-cyra-muted">Enter your details to get started</p>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <a
            href="{{ route('auth.google.redirect') }}"
            class="mb-6 inline-flex w-full items-center justify-center gap-3 rounded-lg border border-cyra-line bg-white px-4 py-3 text-sm font-semibold text-cyra-ink shadow-sm transition hover:bg-cyra-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest/30"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.6 3.8-5.5 3.8A6.4 6.4 0 1 1 12 5.6a6.2 6.2 0 0 1 4.4 1.7l3-2.9A10.3 10.3 0 0 0 12 1.5 10.5 10.5 0 1 0 12 22.5c6.1 0 10.1-4.3 10.1-10.3 0-.7-.1-1.2-.2-1.7H12z"/>
                <path fill="#34A853" d="M3.2 7.3 6.4 9.7A6.4 6.4 0 0 0 12 18c1.8 0 3.3-.6 4.4-1.6l3.2 2.5C17.9 20.9 15.2 22 12 22a10.5 10.5 0 0 1-8.8-14.7z"/>
                <path fill="#4A90E2" d="M12 5.6c1.7 0 3.2.6 4.4 1.7l3-2.9A10.3 10.3 0 0 0 12 1.5a10.5 10.5 0 0 0-8.8 5.8l3.2 2.4A6.2 6.2 0 0 1 12 5.6z"/>
                <path fill="#FBBC05" d="M3.2 16.7A10.5 10.5 0 0 0 12 22.5c3.2 0 5.9-1.1 7.6-2.9l-3.2-2.5C15.3 18.2 13.8 18.7 12 18.7a6.4 6.4 0 0 1-5.6-3.5l-3.2 1.5z"/>
            </svg>
            Continue with Google
        </a>

        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-cyra-line"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white px-3 text-cyra-muted">or register with email</span>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Confirm Password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
            >
                Create Account
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-cyra-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-cyra-forest transition hover:text-cyra-green">
                Login
            </a>
        </p>
    </div>
</x-auth-layout>
