<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileAvatarUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Profile\ProfileDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Account profile settings hub.
 */
class ProfileController extends Controller
{
    public function __construct(
        protected ProfileDashboardService $profileDashboardService
    ) {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $data = $this->profileDashboardService->getOverviewData(
            $request->user(),
            $request->string('tab')->toString() ?: null
        );

        return view('profile.edit', [
            'user' => $data['user'],
            'tab' => $data['tab'],
            'tabs' => $data['tabs'],
            'summary' => $data['summary'],
            'stats' => $data['stats'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit', ['tab' => 'account'])
            ->with('status', 'Profile updated successfully.');
    }

    /**
     * Upload or replace the profile picture.
     */
    public function updateAvatar(ProfileAvatarUpdateRequest $request): RedirectResponse
    {
        $this->profileDashboardService->updateAvatar(
            $request->user(),
            $request->file('avatar')
        );

        return Redirect::route('profile.edit', ['tab' => 'account'])
            ->with('status', 'Profile picture updated successfully.');
    }

    /**
     * Remove the profile picture.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $this->profileDashboardService->removeAvatar($request->user());

        return Redirect::route('profile.edit', ['tab' => 'account'])
            ->with('status', 'Profile picture removed.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
