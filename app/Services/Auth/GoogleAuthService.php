<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Find or create platform users from Google OAuth profiles.
 */
class GoogleAuthService
{
    /**
     * Resolve a local user for a Google identity and prepare them for login.
     */
    public function findOrCreateFromGoogle(SocialiteUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $email = strtolower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || $email === '') {
            throw new BusinessLogicException(
                'Google did not return a usable account email. Try another Google account.'
            );
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            $this->assertActive($user);
            $this->linkGoogleAccount($user, $googleUser);

            return $user->refresh();
        }

        $user = User::query()->create([
            'name' => $this->resolveName($googleUser),
            'email' => $email,
            'google_id' => $googleId,
            'password' => Hash::make(Str::password(32)),
            'email_verified_at' => now(),
            'avatar_path' => $googleUser->getAvatar() ?: null,
        ]);

        $user->forceFill([
            'role' => UserRole::Farmer,
            'status' => UserStatus::Active,
        ])->save();

        event(new Registered($user));

        return $user->refresh();
    }

    protected function linkGoogleAccount(User $user, SocialiteUser $googleUser): void
    {
        $updates = [];

        if (blank($user->google_id)) {
            $updates['google_id'] = (string) $googleUser->getId();
        }

        if ($user->email_verified_at === null) {
            $updates['email_verified_at'] = now();
        }

        if (blank($user->avatar_path) && filled($googleUser->getAvatar())) {
            $updates['avatar_path'] = $googleUser->getAvatar();
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }

    protected function resolveName(SocialiteUser $googleUser): string
    {
        $name = trim((string) $googleUser->getName());

        if ($name !== '') {
            return $name;
        }

        $nickname = trim((string) $googleUser->getNickname());

        if ($nickname !== '') {
            return $nickname;
        }

        $email = (string) $googleUser->getEmail();
        $local = strstr($email, '@', true);

        return is_string($local) && $local !== '' ? $local : 'Google User';
    }

    protected function assertActive(User $user): void
    {
        if (! $user->isActive()) {
            throw new BusinessLogicException('This account is inactive. Contact support for help.');
        }
    }
}
