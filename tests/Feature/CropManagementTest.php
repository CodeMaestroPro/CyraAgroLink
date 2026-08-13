<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Crop Management.
 */
class CropManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_crop_management_overview(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('crops.manage'));

        $response->assertOk();
        $response->assertSee('Crop Management');
        $response->assertSee('Maize - Green Valley Farm');
        $response->assertSee('Vegetative');
        $response->assertSee('Top Dressing');
        $response->assertSee('Apply NPK 20-10-10 fertilizer in 5 days');
        $response->assertSee('Add crop', false);
        $response->assertSee('Irrigation', false);

        $this->assertDatabaseHas('crops', [
            'user_id' => $user->id,
            'name' => 'Maize',
            'growth_stage' => CropGrowthStage::Vegetative->value,
            'health_status' => CropHealthStatus::Good->value,
        ]);
    }

    public function test_user_can_log_irrigation_and_update_health(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('crops.manage'));
        $crop = Crop::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->post(route('crops.activities.store', $crop), [
            'type' => 'irrigation',
            'title' => 'Evening irrigation',
            'quantity' => '30 mm',
            'notes' => 'Full field coverage',
        ])->assertRedirect(route('crops.manage', ['crop' => $crop->id, 'tab' => 'irrigation']));

        $this->assertDatabaseHas('crop_activities', [
            'crop_id' => $crop->id,
            'type' => 'irrigation',
            'title' => 'Evening irrigation',
        ]);

        $this->actingAs($user)->post(route('crops.activities.store', $crop), [
            'type' => 'health',
            'health_status' => CropHealthStatus::Fair->value,
            'health_notes' => 'Mild leaf stress observed',
        ])->assertRedirect(route('crops.manage', ['crop' => $crop->id, 'tab' => 'health']));

        $this->assertSame(CropHealthStatus::Fair, $crop->fresh()->health_status);
    }

    public function test_user_can_create_crop_advance_stage_and_harvest(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'River Bend Farm',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
            'state' => 'Benue',
        ]);

        $this->actingAs($user)->post(route('crops.store'), [
            'farm_id' => $farm->id,
            'name' => 'Yam',
            'variety' => 'White Yam',
            'growth_stage' => CropGrowthStage::Seedling->value,
            'planted_at' => now()->toDateString(),
            'expected_harvest_at' => now()->addDays(120)->toDateString(),
        ])->assertRedirect();

        $crop = Crop::query()->where('user_id', $user->id)->where('name', 'Yam')->firstOrFail();
        $this->assertSame(CropGrowthStage::Seedling, $crop->growth_stage);

        $this->actingAs($user)
            ->post(route('crops.advance-stage', $crop))
            ->assertRedirect(route('crops.manage', ['crop' => $crop->id, 'tab' => 'overview']));

        $this->assertSame(CropGrowthStage::Vegetative, $crop->fresh()->growth_stage);

        $this->actingAs($user)->post(route('crops.activities.store', $crop), [
            'type' => 'harvest',
            'quantity' => '3.2 tons',
            'notes' => 'Good quality tubers',
        ])->assertRedirect(route('crops.manage', ['crop' => $crop->id, 'tab' => 'harvest']));

        $this->assertSame('harvested', $crop->fresh()->status);
        $this->assertSame(100, $crop->fresh()->progress_percent);
    }

    public function test_guest_cannot_view_crop_management(): void
    {
        $this->get(route('crops.manage'))->assertRedirect(route('login'));
    }
}
