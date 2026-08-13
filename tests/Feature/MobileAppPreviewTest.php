<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Mobile App Screens Preview.
 */
class MobileAppPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_mobile_app_preview(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('mobile.preview'));

        $response->assertOk();
        $response->assertSee('Mobile App Screens - Preview');
        $response->assertDontSee('25. MOBILE APP SCREENS');
        $response->assertDontSee('25. Mobile App Screens');
        $response->assertSee('Good morning, Adewale', false);
        $response->assertSee('Wallet Balance', false);
        $response->assertSee('₦850,000.50', false);
        $response->assertSee('Quick Actions', false);
        $response->assertSee('Market', false);
        $response->assertSee('AI Assistant', false);
        $response->assertSee('Logistics', false);
        $response->assertSee('Payment received', false);
        $response->assertSee('Marketplace', false);
        $response->assertSee('Search commodities...', false);
        $response->assertSee('Maize (White)', false);
        $response->assertSee('Investments', false);
        $response->assertSee('My Portfolio', false);
        $response->assertSee('Total Invested', false);
        $response->assertSee('₦4,560,000', false);
        $response->assertSee('Green Valley Farm', false);
        $response->assertSee('How can I help you today?', false);
        $response->assertSee('Diagnose plant disease', false);
        $response->assertSee('Fertilizer recommendation', false);
        $response->assertSee('Best planting time', false);
        $response->assertSee('Weather advice', false);
        $response->assertSee('Type a message...', false);
        $response->assertSee('Deposit', false);
        $response->assertSee('Withdraw', false);
        $response->assertSee('Transfer', false);
        $response->assertSee('Payment Received', false);
        $response->assertSee('Transport Payment', false);
        $response->assertSee('Investment Return', false);
    }

    public function test_guest_cannot_view_mobile_app_preview(): void
    {
        $this->get(route('mobile.preview'))->assertRedirect(route('login'));
    }
}
