<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Buyer Dashboard.
 */
class BuyerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_buyer_dashboard(): void
    {
        $user = User::factory()->buyer()->create([
            'name' => 'Tony Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('buyer.dashboard'));

        $response->assertOk();
        $response->assertSee('Buyer Dashboard');
        $response->assertDontSee('15. BUYER DASHBOARD');
        $response->assertDontSee('15. Buyer Dashboard');
        $response->assertSee('Welcome back, Tony', false);
        $response->assertSeeText("Here's your procurement overview");
        $response->assertSee('Total Orders', false);
        $response->assertSee('24', false);
        $response->assertSee('Total Spent', false);
        $response->assertSee('₦18,560,000', false);
        $response->assertSee('Active Suppliers', false);
        $response->assertSee('18', false);
        $response->assertSee('Pending Deliveries', false);
        $response->assertSee('Recent Orders', false);
        $response->assertSee('Maize (White)', false);
        $response->assertSee('Green Valley Farms', false);
        $response->assertSee('In Transit', false);
        $response->assertSee('Rice (Parboiled)', false);
        $response->assertSee('Delivered', false);
        $response->assertSee('Cashew Nuts', false);
        $response->assertSee('Processing', false);
        $response->assertSee('Cocoa Beans', false);
        $response->assertSee('Pending', false);
        $response->assertSee('Spend Analytics', false);
        $response->assertSee('Favorite Suppliers', false);
        $response->assertSee('Golden Harvest Ltd', false);
        $response->assertSee("Nature's Pride");
        $response->assertSee('View All Orders', false);
        $response->assertSee('View All Suppliers', false);
    }

    public function test_buyer_role_sees_buyer_dashboard_on_main_dashboard_route(): void
    {
        $user = User::factory()->buyer()->create([
            'name' => 'Tony Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Welcome back, Tony', false);
        $response->assertSee('Total Orders', false);
        $response->assertDontSee('Farm Overview');
    }

    public function test_guest_cannot_view_buyer_dashboard(): void
    {
        $this->get(route('buyer.dashboard'))->assertRedirect(route('login'));
    }
}
