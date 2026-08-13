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
 * Feature tests for AI Digital Twin Farm.
 */
class DigitalTwinFarmTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_digital_twin_farm(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('digital.twin'));

        $response->assertOk();
        $response->assertSee('AI Digital Twin Farm');
        $response->assertDontSee('26. AI DIGITAL TWIN FARM');
        $response->assertDontSee('26. AI Digital Twin Farm');
        $response->assertSee('Farm Overview', false);
        $response->assertSee('Green Valley Farm', false);
        $response->assertSee('Healthy', false);
        $response->assertSee('Crop Health', false);
        $response->assertSee('Soil Moisture', false);
        $response->assertSee('Growth Stage', false);
        $response->assertSee('Flowering', false);
        $response->assertSee('Estimated Yield', false);
        $response->assertSee('Soil Health', false);
        $response->assertSee('Good', false);
        $response->assertSee('Water Level', false);
        $response->assertSee('Pest Risk', false);
        $response->assertSee('Low', false);
        $response->assertSee('Weather', false);
        $response->assertSee('Run twin scan', false);
        $response->assertSee('Simulate irrigation', false);
        $response->assertSee('Plot A', false);
        $response->assertSee('digitalTwinFarmMap', false);
    }

    public function test_guest_cannot_view_digital_twin_farm(): void
    {
        $this->get(route('digital.twin'))->assertRedirect(route('login'));
    }

    public function test_user_can_run_twin_scan_and_irrigate(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'River Twin Farm',
            'state' => 'Benue',
            'latitude' => '7.7300000',
            'longitude' => '8.5400000',
            'size_hectares' => '3.00',
            'soil_type' => 'Sandy loam',
            'crops' => ['Maize', 'Rice'],
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
            'health_status' => CropHealthStatus::Fair,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('digital.twin', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('River Twin Farm', false)
            ->assertSee('Vegetative', false);

        $this->actingAs($user)
            ->post(route('digital.twin.scan', $farm))
            ->assertRedirect(route('digital.twin', ['farm' => $farm->id]));

        $this->actingAs($user)
            ->get(route('digital.twin', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('Twin scan complete', false)
            ->assertSee('Last scan:', false);

        $before = session('digital_twin_overlay_'.$farm->id)['moisture'] ?? null;

        $this->actingAs($user)
            ->post(route('digital.twin.irrigate', $farm))
            ->assertRedirect(route('digital.twin', ['farm' => $farm->id]));

        $after = session('digital_twin_overlay_'.$farm->id)['moisture'] ?? null;
        $this->assertNotNull($after);
        if ($before !== null) {
            $this->assertGreaterThanOrEqual((int) $before, (int) $after);
        }

        $this->actingAs($user)
            ->get(route('digital.twin', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('Irrigation applied', false);
    }

    public function test_user_cannot_scan_another_users_farm(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $farm = Farm::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Twin',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        $this->actingAs($intruder)
            ->post(route('digital.twin.scan', $farm))
            ->assertForbidden();
    }
}
