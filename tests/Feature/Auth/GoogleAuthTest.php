<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_link_to_google_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee('Continue with Google', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee('Continue with Google', false);
    }

    public function test_google_redirect_requires_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_google_redirect_sends_user_to_provider_when_configured(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')->once()->with(['openid', 'profile', 'email'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_creates_and_logs_in_new_user(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-123',
            'name' => 'Ada Farmer',
            'email' => 'ada.google@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/a/test',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada.google@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('Ada Farmer', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('https://lh3.googleusercontent.com/a/test', $user->avatar_path);
    }

    public function test_google_callback_links_existing_email_account(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        $existing = User::factory()->create([
            'email' => 'linked@example.com',
            'name' => 'Existing User',
            'google_id' => null,
            'email_verified_at' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-456',
            'name' => 'Existing User',
            'email' => 'linked@example.com',
            'avatar' => null,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame('google-456', $existing->fresh()->google_id);
        $this->assertNotNull($existing->fresh()->email_verified_at);
    }

    public function test_inactive_google_user_cannot_sign_in(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        User::factory()->create([
            'email' => 'inactive@example.com',
            'google_id' => 'google-inactive',
            'status' => UserStatus::Inactive,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-inactive',
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'avatar' => null,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    /**
     * @param  array{id: string, name: string, email: string, avatar: ?string}  $attributes
     */
    protected function fakeGoogleUser(array $attributes): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($attributes['id']);
        $socialUser->shouldReceive('getName')->andReturn($attributes['name']);
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getEmail')->andReturn($attributes['email']);
        $socialUser->shouldReceive('getAvatar')->andReturn($attributes['avatar']);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
