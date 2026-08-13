<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
            'phone' => '+2348011112222',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Adewale Okonkwo', false);
        $response->assertSee('Profile picture', false);
        $response->assertSee('Profile Information', false);
        $response->assertSee(route('profile.edit', ['tab' => 'security']), false);
        $response->assertSee(route('profile.edit', ['tab' => 'danger']), false);
    }

    public function test_user_can_upload_and_change_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $first = $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
        ]);

        $first
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'account']))
            ->assertSessionHas('status', 'Profile picture updated successfully.');

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(str_starts_with((string) $user->avatar_path, 'storage/avatars/'));
        Storage::disk('public')->assertExists(substr((string) $user->avatar_path, strlen('storage/')));

        $oldPath = (string) $user->avatar_path;

        $second = $this->actingAs($user)->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('new-avatar.png', 300, 300),
        ]);

        $second->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertNotSame($oldPath, $user->avatar_path);
        Storage::disk('public')->assertMissing(substr($oldPath, strlen('storage/')));
        Storage::disk('public')->assertExists(substr((string) $user->avatar_path, strlen('storage/')));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Change photo', false)
            ->assertSee('Remove photo', false);
    }

    public function test_user_can_remove_profile_picture(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $stored = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
        $user->forceFill(['avatar_path' => 'storage/'.$stored])->save();

        $response = $this->actingAs($user)->delete(route('profile.avatar.destroy'));

        $response
            ->assertRedirect(route('profile.edit', ['tab' => 'account']))
            ->assertSessionHas('status', 'Profile picture removed.');

        $user->refresh();
        $this->assertNull($user->avatar_path);
        Storage::disk('public')->assertMissing($stored);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+2348099998888',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'account']));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+2348099998888', $user->phone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'account']));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_security_tab_can_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Update Password', false);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'security']));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit', ['tab' => 'danger']))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect(route('profile.edit', ['tab' => 'danger']));

        $this->assertNotNull($user->fresh());
    }

    public function test_guest_cannot_view_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }
}
