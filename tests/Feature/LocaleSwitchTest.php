<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for UI locale switching.
 */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_locale_to_french(): void
    {
        $user = User::factory()->investor()->create();

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('locale.update'), ['locale' => 'fr']);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('locale', 'fr');
        $response->assertSessionHas('status');

        $this->actingAs($user)
            ->withSession([SetLocale::SESSION_KEY => 'fr'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord', false)
            ->assertSee('Investissements', false)
            ->assertSee('FR', false);
    }

    public function test_user_can_switch_locale_to_hausa(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)
            ->from(route('investments.index'))
            ->post(route('locale.update'), ['locale' => 'ha'])
            ->assertRedirect(route('investments.index'))
            ->assertSessionHas('locale', 'ha');

        $this->actingAs($user)
            ->withSession([SetLocale::SESSION_KEY => 'ha'])
            ->get(route('investments.index'))
            ->assertOk()
            ->assertSee('Saka jari a ingantattun gonaki', false)
            ->assertSee('HA', false);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('locale.update'), ['locale' => 'xx'])
            ->assertSessionHasErrors('locale');
    }

    public function test_guest_can_switch_locale(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('locale', 'fr');
    }

    public function test_user_can_switch_to_yoruba_and_igbo(): void
    {
        $user = User::factory()->investor()->create();

        foreach (['yo' => 'YO', 'ig' => 'IG'] as $locale => $label) {
            $this->actingAs($user)
                ->from(route('dashboard'))
                ->post(route('locale.update'), ['locale' => $locale])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('locale', $locale);

            $this->actingAs($user)
                ->withSession([SetLocale::SESSION_KEY => $locale])
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee($label, false);
        }
    }
}
