<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for the farm registration wizard.
 */
class FarmRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_farm_registration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('farms.register'));

        $response->assertOk();
        $response->assertSee('Farm Registration');
        $response->assertSee('Farm Location');
        $response->assertSee('Register another farm', false);
        $this->assertDatabaseHas('farms', [
            'user_id' => $user->id,
            'status' => FarmStatus::Draft->value,
        ]);
    }

    public function test_user_can_save_farm_location_and_advance(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'status' => FarmStatus::Draft,
            'registration_step' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('farms.register.location', $farm), [
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Akinyele, Ibadan',
            'latitude' => 7.3775,
            'longitude' => 3.9470,
        ]);

        $response->assertRedirect(route('farms.register', ['farm' => $farm->id, 'step' => 2]));

        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'registration_step' => 2,
        ]);
    }

    public function test_user_can_complete_full_registration_with_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'status' => FarmStatus::Draft,
            'registration_step' => 1,
        ]);

        $this->actingAs($user)->post(route('farms.register.location', $farm), [
            'state' => 'Benue',
            'local_government' => 'Makurdi',
            'address' => 'North Bank',
            'latitude' => 7.7322,
            'longitude' => 8.5391,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('farms.register.details', $farm), [
            'name' => 'Benue River Farms',
            'size_hectares' => 12.5,
            'soil_type' => 'Loamy',
            'description' => 'Mixed crop farm along the river basin.',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('farms.register.crops', $farm), [
            'crops' => ['Maize', 'Yam'],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('farms.register.documents', $farm), [
            'land_title' => UploadedFile::fake()->create('title.pdf', 120, 'application/pdf'),
            'identity_document' => UploadedFile::fake()->image('id.jpg'),
        ])->assertRedirect(route('farms.register', ['farm' => $farm->id, 'step' => 5]));

        $farm->refresh();
        $this->assertNotEmpty($farm->documents['land_title']['path'] ?? null);

        $this->actingAs($user)
            ->post(route('farms.register.submit', $farm))
            ->assertRedirect(route('farms.register', ['farm' => $farm->id, 'step' => 5]));

        $this->assertSame(FarmStatus::PendingReview, $farm->fresh()->status);

        $this->actingAs($user)
            ->get(route('farms.register', ['farm' => $farm->id]))
            ->assertOk()
            ->assertSee('Registration submitted', false)
            ->assertSee('Benue River Farms', false);
    }

    public function test_user_cannot_edit_another_users_farm(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $owner->id,
            'status' => FarmStatus::Draft,
            'registration_step' => 1,
        ]);

        $this->actingAs($intruder)->post(route('farms.register.location', $farm), [
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Hijack attempt',
            'latitude' => 7.3775,
            'longitude' => 3.9470,
        ])->assertForbidden();
    }

    public function test_guest_cannot_access_farm_registration(): void
    {
        $this->get(route('farms.register'))->assertRedirect(route('login'));
    }
}
