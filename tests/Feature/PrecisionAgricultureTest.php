<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CropActivityType;
use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Models\Crop;
use App\Models\CropActivity;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Precision Agriculture.
 */
class PrecisionAgricultureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_precision_agriculture(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('precision.agriculture'));

        $response->assertOk();
        $response->assertSee('Precision Agriculture');
        $response->assertDontSee('27. PRECISION AGRICULTURE');
        $response->assertDontSee('27. Precision Agriculture');
        $response->assertSee('Precision Overview', false);
        $response->assertSee('View All Fields', false);
        $response->assertSee('Green Valley Farm', false);
        $response->assertSee('Soil N (Nitrogen)', false);
        $response->assertSee('Soil P (Phosphorus)', false);
        $response->assertSee('Soil K (Potassium)', false);
        $response->assertSee('pH Level', false);
        $response->assertSee('Field Map (NDVI)', false);
        $response->assertSee('Irrigation Status', false);
        $response->assertSee('Next Irrigation:', false);
        $response->assertSee('Fertilizer Recommendation', false);
        $response->assertSee('Apply NPK 20-10-10', false);
        $response->assertSee('View Full Recommendation', false);
        $response->assertSee('Refresh NDVI scan', false);
        $response->assertSee('Schedule irrigation', false);
        $response->assertSee('Apply fertilizer plan', false);
    }

    public function test_guest_cannot_view_precision_agriculture(): void
    {
        $this->get(route('precision.agriculture'))->assertRedirect(route('login'));
    }

    public function test_user_can_scan_schedule_irrigation_and_apply_fertilizer(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Precision Ridge Farm',
            'state' => 'Kaduna',
            'latitude' => '10.5200000',
            'longitude' => '7.4400000',
            'size_hectares' => '2.50',
            'soil_type' => 'Loamy',
            'crops' => ['Maize'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        Crop::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'variety' => 'Hybrid',
            'growth_stage' => CropGrowthStage::Vegetative,
            'progress_percent' => 50,
            'health_status' => CropHealthStatus::Good,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('precision.agriculture', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('Precision Ridge Farm', false);

        $this->actingAs($user)
            ->post(route('precision.scan', $farm))
            ->assertRedirect(route('precision.agriculture', ['farm' => $farm->id]));

        $this->actingAs($user)
            ->get(route('precision.agriculture', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('NDVI scan refreshed', false)
            ->assertSee('Last scan', false);

        $this->actingAs($user)
            ->post(route('precision.irrigate', $farm))
            ->assertRedirect(route('precision.agriculture', ['farm' => $farm->id]));

        $this->actingAs($user)
            ->get(route('precision.agriculture', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('System Active', false)
            ->assertSee('Irrigation window scheduled', false);

        $this->actingAs($user)
            ->post(route('precision.fertilizer', $farm))
            ->assertRedirect(route('precision.agriculture', ['farm' => $farm->id]).'#fertilizer');

        $crop = Crop::query()->where('farm_id', $farm->id)->firstOrFail();

        $this->assertDatabaseHas('crop_activities', [
            'crop_id' => $crop->id,
            'user_id' => $user->id,
            'type' => CropActivityType::Fertilizer->value,
            'quantity' => 'NPK 20-10-10',
        ]);

        $this->assertTrue(
            CropActivity::query()
                ->where('crop_id', $crop->id)
                ->where('type', CropActivityType::Fertilizer)
                ->where('title', 'like', 'Precision plan applied:%')
                ->exists()
        );

        $crop->refresh();
        $this->assertSame('Monitor fertilizer uptake', $crop->next_activity);
        $this->assertStringContainsString('applied via Precision Agriculture', (string) $crop->ai_recommendation);

        $this->actingAs($user)
            ->get(route('precision.agriculture', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('Fertilizer plan applied', false)
            ->assertSee('Logged on', false)
            ->assertSee('Re-apply fertilizer plan', false)
            ->assertSee('NPK 20-10-10', false);

        $this->actingAs($user)
            ->get(route('crops.manage', ['crop' => $crop->id, 'tab' => 'fertilizer']))
            ->assertOk()
            ->assertSee('Precision plan applied', false);
    }

    public function test_user_cannot_scan_another_users_farm(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Precision',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        $this->actingAs($intruder)
            ->post(route('precision.scan', $farm))
            ->assertForbidden();
    }
}
