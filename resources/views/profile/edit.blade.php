@php
    $initials = collect(explode(' ', trim($summary['name'])))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<x-dashboard-layout
    title="Profile"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Settings'],
        ['label' => 'Profile'],
    ]"
>
    <x-page-header
        title="Profile"
        description="Manage your account information, password, and data."
    />

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/60 px-4 py-3 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') === 'profile-updated' ? 'Profile updated successfully.' : (session('status') === 'password-updated' ? 'Password updated successfully.' : session('status')) }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-2xl bg-white ring-1 ring-cyra-line">
        <div class="border-b border-cyra-line px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-cyra-forest font-display text-lg font-extrabold text-white">
                        @if (! empty($summary['avatar_url']))
                            <img src="{{ $summary['avatar_url'] }}" alt="{{ $summary['name'] }}" class="h-full w-full object-cover">
                        @else
                            <span class="inline-flex h-full w-full items-center justify-center">{{ $initials !== '' ? $initials : 'CA' }}</span>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate font-display text-xl font-extrabold text-cyra-ink">{{ $summary['name'] }}</h2>
                        <p class="mt-0.5 truncate text-sm text-cyra-muted">{{ $summary['email'] }} · {{ $summary['role'] }}</p>
                        <p class="mt-1 text-xs text-cyra-muted">Member since {{ $summary['member_since'] }} · {{ $summary['status'] }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($stats as $stat)
                        <div class="rounded-full bg-cyra-surface px-3 py-1.5 text-sm ring-1 ring-cyra-line">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-cyra-muted">{{ $stat['label'] }}</span>
                            <span class="ml-1 font-extrabold text-cyra-forest">{{ $stat['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
            <x-section-tabs :active="$tab" :items="$tabs" />
        </div>

        @if ($tab === 'account')
            <section class="border-b border-cyra-line p-4 sm:p-6" aria-labelledby="profile-picture-heading">
                <div class="max-w-2xl">
                    <h3 id="profile-picture-heading" class="font-display text-lg font-extrabold text-cyra-ink">Profile picture</h3>
                    <p class="mt-1 text-sm text-cyra-muted">Upload a clear photo so partners can recognize you. JPG, PNG, or WEBP up to 2&nbsp;MB.</p>

                    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <span class="inline-flex h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-cyra-mint text-2xl font-extrabold text-cyra-forest ring-1 ring-cyra-line">
                            @if (! empty($summary['avatar_url']))
                                <img src="{{ $summary['avatar_url'] }}" alt="{{ $summary['name'] }}" class="h-full w-full object-cover">
                            @else
                                <span class="inline-flex h-full w-full items-center justify-center">{{ $initials !== '' ? $initials : 'CA' }}</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1 space-y-3">
                            <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                @csrf
                                <div class="min-w-0 flex-1">
                                    <label for="avatar" class="block text-xs font-bold text-cyra-muted">Choose photo</label>
                                    <input
                                        id="avatar"
                                        name="avatar"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        required
                                        class="mt-1.5 block w-full text-sm text-cyra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-cyra-mint file:px-3 file:py-2 file:text-sm file:font-semibold file:text-cyra-forest"
                                    >
                                    @error('avatar')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                                    {{ ! empty($summary['avatar_url']) ? 'Change photo' : 'Upload photo' }}
                                </button>
                            </form>

                            @if (! empty($summary['avatar_url']))
                                <form method="post" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('Remove your profile picture?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-rose-700 hover:underline">
                                        Remove photo
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="p-4 sm:p-6" aria-labelledby="profile-information-heading">
                <div class="max-w-2xl">
                    <h3 id="profile-information-heading" class="font-display text-lg font-extrabold text-cyra-ink">Profile Information</h3>
                    <p class="mt-1 text-sm text-cyra-muted">Update your name, phone number, and email address.</p>

                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
                        @csrf
                        @method('patch')

                        <div>
                            <label for="name" class="block text-xs font-bold text-cyra-muted">Name</label>
                            <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name', $user->name) }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @error('name')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-cyra-muted">Phone</label>
                            <input id="phone" name="phone" type="text" autocomplete="tel" value="{{ old('phone', $user->phone) }}" placeholder="+2348012345678" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @error('phone')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-cyra-muted">Email</label>
                            <input id="email" name="email" type="email" required autocomplete="username" value="{{ old('email', $user->email) }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @error('email')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <p class="mt-2 text-sm text-cyra-ink">
                                    Your email address is unverified.
                                    <button form="send-verification" class="font-semibold text-cyra-forest underline hover:text-cyra-green">
                                        Click here to re-send the verification email.
                                    </button>
                                </p>
                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-2 text-sm font-medium text-cyra-forest">A new verification link has been sent to your email address.</p>
                                @endif
                            @else
                                <p class="mt-2 text-xs font-semibold text-cyra-forest">Email verified</p>
                            @endif
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                            Save changes
                        </button>
                    </form>
                </div>
            </section>
        @endif

        @if ($tab === 'security')
            <section class="p-4 sm:p-6" aria-labelledby="update-password-heading">
                <div class="max-w-2xl">
                    <h3 id="update-password-heading" class="font-display text-lg font-extrabold text-cyra-ink">Update Password</h3>
                    <p class="mt-1 text-sm text-cyra-muted">Use a long, random password to keep your account secure.</p>

                    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                        @csrf
                        @method('put')

                        <div>
                            <label for="update_password_current_password" class="block text-xs font-bold text-cyra-muted">Current password</label>
                            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @error('current_password', 'updatePassword')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="update_password_password" class="block text-xs font-bold text-cyra-muted">New password</label>
                            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @error('password', 'updatePassword')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="update_password_password_confirmation" class="block text-xs font-bold text-cyra-muted">Confirm password</label>
                            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                            Update password
                        </button>
                    </form>
                </div>
            </section>
        @endif

        @if ($tab === 'danger')
            <section class="p-4 sm:p-6" aria-labelledby="delete-account-heading">
                <div class="max-w-2xl rounded-2xl bg-rose-50/60 p-5 ring-1 ring-rose-200">
                    <h3 id="delete-account-heading" class="font-display text-lg font-extrabold text-rose-800">Delete Account</h3>
                    <p class="mt-1 text-sm text-rose-700/90">
                        Once your account is deleted, your profile and related records are soft-deleted and you will be signed out. Enter your password to confirm.
                    </p>

                    <form method="post" action="{{ route('profile.destroy') }}" class="mt-6 space-y-4">
                        @csrf
                        @method('delete')

                        <div>
                            <label for="password" class="block text-xs font-bold text-rose-800">Password</label>
                            <input id="password" name="password" type="password" class="mt-1.5 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-200" placeholder="Confirm with your password">
                            @error('password', 'userDeletion')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-rose-800">
                            Delete Account
                        </button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</x-dashboard-layout>
