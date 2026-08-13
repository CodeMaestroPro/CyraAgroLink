<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\User;
use App\Models\UserInboxNotification;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Assembles account overview data for the profile settings hub.
 */
class ProfileDashboardService
{
    /**
     * @var list<string>
     */
    public const TABS = ['account', 'security', 'danger'];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(User $user, ?string $tab = null): array
    {
        $tab = $this->resolveTab($tab);
        $unread = UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $walletBalance = 0;
        try {
            $walletBalance = $this->walletService->getBalance($user);
        } catch (\Throwable) {
            $walletBalance = 0;
        }

        return [
            'tab' => $tab,
            'user' => $user,
            'summary' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?: 'Not set',
                'role' => $user->role?->label() ?? 'Member',
                'status' => ucfirst((string) ($user->status?->value ?? 'active')),
                'member_since' => $user->created_at?->format('d M Y') ?? '—',
                'email_verified' => $user->hasVerifiedEmail(),
                'avatar_url' => $user->avatarUrl(),
            ],
            'stats' => [
                ['label' => 'Farms', 'value' => (string) $user->farms()->count()],
                ['label' => 'Wallet', 'value' => '₦'.number_format($walletBalance)],
                ['label' => 'Unread alerts', 'value' => (string) $unread],
            ],
            'notifications_count' => $unread,
            'tabs' => [
                ['id' => 'account', 'label' => 'Account', 'href' => route('profile.edit', ['tab' => 'account'])],
                ['id' => 'security', 'label' => 'Security', 'href' => route('profile.edit', ['tab' => 'security'])],
                ['id' => 'danger', 'label' => 'Danger zone', 'href' => route('profile.edit', ['tab' => 'danger'])],
            ],
        ];
    }

    /**
     * Store a new profile picture and replace any previous upload.
     */
    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        $stored = $avatar->store('avatars', 'public');
        $path = 'storage/'.$stored;

        $this->deleteStoredAvatar($user->avatar_path);

        $user->forceFill(['avatar_path' => $path])->save();

        return $user->refresh();
    }

    /**
     * Remove the current profile picture.
     */
    public function removeAvatar(User $user): User
    {
        $this->deleteStoredAvatar($user->avatar_path);
        $user->forceFill(['avatar_path' => null])->save();

        return $user->refresh();
    }

    public function resolveTab(?string $tab): string
    {
        $tab = $tab ?: 'account';

        return in_array($tab, self::TABS, true) ? $tab : 'account';
    }

    protected function deleteStoredAvatar(?string $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'storage/avatars/')) {
            return;
        }

        Storage::disk('public')->delete(substr($path, strlen('storage/')));
    }
}
