<x-auth-layout title="Login">
    <div class="mx-auto w-full max-w-md">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold tracking-tight text-cyra-ink">Login</h2>
            <p class="mt-2 text-sm text-cyra-muted">Enter your credentials to access your account</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ showPassword: false }">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-cyra-ink">
                    Email or Phone Number
                </label>
                <input
                    id="email"
                    type="text"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter email or phone"
                    class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink placeholder:text-gray-400 shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-cyra-ink">
                    Password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter password"
                        class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 pr-11 text-sm text-cyra-ink placeholder:text-gray-400 shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition hover:text-cyra-muted"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-cloak x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.5 10.7A3 3 0 0012 15a3 3 0 002.1-.9M9.9 5.1A9.8 9.8 0 0112 5c4.5 0 8.3 2.9 9.5 7a10.4 10.4 0 01-4.1 4.9M6.1 6.1A10.4 10.4 0 002.5 12c1.2 4.1 5 7 9.5 7 1.3 0 2.5-.2 3.6-.7" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-3">
                <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-cyra-line text-cyra-forest shadow-sm focus:ring-cyra-forest"
                    >
                    <span class="text-sm font-medium text-cyra-ink">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-semibold text-cyra-forest transition hover:text-cyra-green"
                    >
                        Forgot Password?
                    </a>
                @endif
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
            >
                Login
            </button>
        </form>

        <div class="mt-8">
            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-cyra-line"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-3 text-cyra-muted">or continue with</span>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-center gap-3">
                <a
                    href="{{ route('auth.google.redirect') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-cyra-line bg-white transition hover:bg-cyra-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest/30"
                    aria-label="Continue with Google"
                    title="Continue with Google"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.6 3.8-5.5 3.8A6.4 6.4 0 1 1 12 5.6a6.2 6.2 0 0 1 4.4 1.7l3-2.9A10.3 10.3 0 0 0 12 1.5 10.5 10.5 0 1 0 12 22.5c6.1 0 10.1-4.3 10.1-10.3 0-.7-.1-1.2-.2-1.7H12z"/>
                        <path fill="#34A853" d="M3.2 7.3 6.4 9.7A6.4 6.4 0 0 0 12 18c1.8 0 3.3-.6 4.4-1.6l3.2 2.5C17.9 20.9 15.2 22 12 22a10.5 10.5 0 0 1-8.8-14.7z"/>
                        <path fill="#4A90E2" d="M12 5.6c1.7 0 3.2.6 4.4 1.7l3-2.9A10.3 10.3 0 0 0 12 1.5a10.5 10.5 0 0 0-8.8 5.8l3.2 2.4A6.2 6.2 0 0 1 12 5.6z"/>
                        <path fill="#FBBC05" d="M3.2 16.7A10.5 10.5 0 0 0 12 22.5c3.2 0 5.9-1.1 7.6-2.9l-3.2-2.5C15.3 18.2 13.8 18.7 12 18.7a6.4 6.4 0 0 1-5.6-3.5l-3.2 1.5z"/>
                    </svg>
                </a>

                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-cyra-line bg-white transition hover:bg-cyra-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest/30"
                    aria-label="Continue with Apple"
                    title="Apple sign-in coming soon"
                >
                    <svg class="h-5 w-5 text-cyra-ink" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16.4 12.7c0-2.1 1.7-3.1 1.8-3.2-1-1.4-2.5-1.6-3-1.6-1.3-.1-2.5.8-3.1.8-.7 0-1.7-.7-2.8-.7-1.4 0-2.7.8-3.4 2.1-1.5 2.5-.4 6.3 1 8.4.7 1 1.5 2.2 2.6 2.1 1 0 1.4-.7 2.7-.7s1.6.7 2.7.7c1.1 0 1.8-1 2.5-2 .8-1.2 1.1-2.3 1.1-2.4-.1 0-2.1-.8-2.1-3.5zM14.3 6.5c.6-.7 1-1.7.9-2.7-.9 0-1.9.6-2.5 1.3-.6.6-1.1 1.7-.9 2.6 1 .1 1.9-.5 2.5-1.2z"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-cyra-line bg-white transition hover:bg-cyra-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest/30"
                    aria-label="Continue with phone"
                    title="Phone sign-in coming soon"
                >
                    <svg class="h-5 w-5 text-cyra-ink" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.5h10A1.5 1.5 0 0 1 18.5 5v14a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 17.5h4" />
                    </svg>
                </button>
            </div>
        </div>

        <p class="mt-8 text-center text-sm text-cyra-muted">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-bold text-cyra-forest transition hover:text-cyra-green">
                Create Account
            </a>
        </p>
    </div>
</x-auth-layout>
