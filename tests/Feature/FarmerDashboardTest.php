<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Enums\UserRole;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\UserInboxNotification;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Farmer dashboard rendering and live data tests.
 */
class FarmerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_farmer_sees_live_dashboard_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
            'role' => UserRole::Farmer,
        ]);

        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'River Bend Farm',
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        Crop::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'growth_stage' => CropGrowthStage::Vegetative,
            'progress_percent' => 50,
            'health_status' => CropHealthStatus::Good,
            'status' => 'active',
            'ai_recommendation' => 'Apply balanced fertilizer within 5 days for Maize.',
            'next_activity' => 'Fertilizer application',
            'planted_at' => now()->subWeeks(3),
        ]);

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 350000,
            'currency' => 'NGN',
        ]);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'category' => 'earnings',
            'amount' => 350000,
            'balance_after' => 350000,
            'title' => 'Marketplace sale',
            'detail' => 'Maize sale proceeds',
        ]);

        MarketplaceCommodity::query()->create([
            'user_id' => $user->id,
            'name' => 'Yellow Maize',
            'price_per_ton' => 280000,
            'volume_tons' => 12,
            'is_featured' => false,
            'status' => 'active',
        ]);

        UserInboxNotification::query()->create([
            'user_id' => $user->id,
            'title' => 'Farm tip',
            'body' => 'Check irrigation schedules this week.',
            'tone' => 'info',
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Welcome back, Adewale', false)
            ->assertSee('Total Farms', false)
            ->assertSee('Farm Overview', false)
            ->assertSee('River Bend Farm', false)
            ->assertSee('Ibadan North, Oyo State', false)
            ->assertSee('Maize', false)
            ->assertSee('images/marketplace/maize.jpg', false)
            ->assertSee('Recent Activities', false)
            ->assertSee('Earnings Overview', false)
            ->assertSee('₦350,000', false)
            ->assertSee('Apply balanced fertilizer within 5 days for Maize.', false)
            ->assertDontSee('Green Valley Farm')
            ->assertDontSee('Government', false)
            ->assertDontSee('Food Security', false)
            ->assertDontSee('BI Center', false)
            ->assertSee('Investments', false)
            ->assertSee('Marketplace', false)
            ->assertSee('My Farms', false);
    }

    public function test_empty_farmer_dashboard_shows_guidance(): void
    {
        $user = User::factory()->create([
            'name' => 'New Farmer',
            'role' => UserRole::Farmer,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Welcome back, New', false)
            ->assertSee('Register a crop cycle', false)
            ->assertSee('Welcome to your farm hub', false)
            ->assertSee('Add New Farm', false);
    }

    public function test_farm_overview_uses_named_and_crop_images(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Farmer,
        ]);

        $greenValley = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Valley Farm',
            'state' => 'Oyo',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        Crop::query()->create([
            'farm_id' => $greenValley->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'growth_stage' => CropGrowthStage::Vegetative,
            'progress_percent' => 40,
            'health_status' => CropHealthStatus::Good,
            'status' => 'active',
        ]);

        Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Sunrise Poultry',
            'state' => 'Kaduna',
            'crops' => ['Broilers'],
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('images/farms/green-valley.jpg', false)
            ->assertSee('images/farms/sunrise.jpg', false)
            ->assertDontSee('commodity-placeholder.svg', false);
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
