<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademyCourse;
use App\Models\AcademyEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Agricultural Learning Academy.
 */
class LearningAcademyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_learning_academy(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('academy.learning'));

        $response->assertOk();
        $response->assertSee('Agricultural Learning Academy');
        $response->assertDontSee('39. AGRICULTURAL LEARNING ACADEMY');
        $response->assertDontSee('39. Agricultural Learning Academy');
        $response->assertSee('Featured Courses', false);
        $response->assertSee('Modern Maize Farming', false);
        $response->assertSee('Smart Irrigation Techniques', false);
        $response->assertSeeText('Pest & Disease Management');
        $response->assertSee('Beginner', false);
        $response->assertSee('Intermediate', false);
        $response->assertSee('4.8', false);
        $response->assertSee('4.7', false);
        $response->assertSee('1h 30m', false);
        $response->assertSee('View full journey', false);
        $response->assertSee('Continue Learning', false);
        $response->assertSee('Soil Fertility Management', false);
        $response->assertSee('50% Complete', false);
        $response->assertSee('Visit Now', false);
        $response->assertSee('Course Library', false);
        $response->assertSee('Certificates', false);
        $response->assertSee('Broiler Production Basics', false);
        $response->assertSee('Pond Fish Farming', false);

        $this->assertGreaterThanOrEqual(7, AcademyCourse::query()->count());
        $this->assertSame(1, AcademyEnrollment::query()->where('user_id', $user->id)->count());
    }

    public function test_guest_cannot_view_learning_academy(): void
    {
        $this->get(route('academy.learning'))->assertRedirect(route('login'));
    }

    public function test_user_can_enroll_advance_and_earn_certificate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('academy.learning'))->assertOk();

        $course = AcademyCourse::query()->where('slug', 'modern-maize-farming')->firstOrFail();

        $this->actingAs($user)
            ->post(route('academy.enroll'), ['course_id' => $course->id])
            ->assertRedirect(route('academy.learning').'#continue');

        $enrollment = AcademyEnrollment::query()
            ->where('user_id', $user->id)
            ->where('academy_course_id', $course->id)
            ->firstOrFail();

        $this->assertSame(0, $enrollment->progress_pct);

        // Four modules → 25% steps; start soil course is separate.
        $this->actingAs($user)
            ->post(route('academy.enrollments.advance', $enrollment))
            ->assertRedirect(route('academy.learning').'#continue');

        $this->assertSame(25, $enrollment->fresh()->progress_pct);
        $this->assertSame('in_progress', $enrollment->fresh()->status);

        foreach ([50, 75, 100] as $expected) {
            $this->actingAs($user)
                ->post(route('academy.enrollments.advance', $enrollment))
                ->assertRedirect();

            $this->assertSame($expected, $enrollment->fresh()->progress_pct);
        }

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->status);
        $this->assertNotNull($enrollment->certificate_code);

        $this->actingAs($user)
            ->get(route('academy.learning'))
            ->assertOk()
            ->assertSee($enrollment->certificate_code, false)
            ->assertSee('Modern Maize Farming', false);
    }

    public function test_level_filter_narrows_library(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('academy.learning'))->assertOk();

        $this->actingAs($user)
            ->get(route('academy.learning', ['level' => 'Beginner']))
            ->assertOk()
            ->assertSee('Modern Maize Farming', false)
            ->assertSee('Broiler Production Basics', false)
            ->assertDontSee('Smart Irrigation Techniques');
    }
}
