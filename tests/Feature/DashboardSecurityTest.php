<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security hardening coverage for dashboard access control and headers.
 */
class DashboardSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_farmer_can_access_government_and_financial_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('government.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('financial.dashboard'))->assertOk();
    }

    public function test_farmer_cannot_access_buyer_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('buyer.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_privileged_dashboards(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('government.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('financial.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_user_is_logged_out_from_dashboard(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_security_headers_are_present_on_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue(
            $response->headers->has('Content-Security-Policy-Report-Only')
            || $response->headers->has('Content-Security-Policy')
        );
    }

    public function test_mass_assignment_cannot_escalate_role_via_user_create(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => UserStatus::Suspended->value,
        ]);

        $user->refresh();

        $this->assertSame('farmer', $user->role->value);
        $this->assertSame('active', $user->status->value);
    }

    public function test_buyer_cannot_force_featured_listing_flag(): void
    {
        $farmer = User::factory()->create();

        $this->actingAs($farmer)->get(route('marketplace.index'));

        $this->actingAs($farmer)->post(route('marketplace.store'), [
            'name' => 'Secured Maize',
            'price_per_ton' => 250000,
            'city' => 'Ibadan',
            'state' => 'Oyo',
            'is_featured' => true,
        ])->assertRedirect(route('marketplace.index', ['view' => 'listings']));

        $this->assertDatabaseHas('marketplace_commodities', [
            'name' => 'Secured Maize',
            'user_id' => $farmer->id,
            'is_featured' => 0,
        ]);
    }
}
