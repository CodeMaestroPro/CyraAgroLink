<?php

declare(strict_types=1);

namespace App\Services\Academy;

use App\Exceptions\BusinessLogicException;
use App\Models\AcademyCourse;
use App\Models\AcademyEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Live agricultural learning academy: catalog, enrollments, and progress.
 */
class LearningAcademyService
{
    /**
     * @return array<string, mixed>
     */
    public function getAcademyData(User $user, ?string $level = null): array
    {
        $this->ensureCatalog();
        $this->ensureStarterEnrollment($user);

        $level = $level && in_array($level, ['Beginner', 'Intermediate', 'Advanced'], true)
            ? $level
            : null;

        $enrollments = AcademyEnrollment::query()
            ->where('user_id', $user->id)
            ->with('course')
            ->get()
            ->keyBy('academy_course_id');

        $coursesQuery = AcademyCourse::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($level) {
            $coursesQuery->where('level', $level);
        }

        $allCourses = $coursesQuery->get();

        $featured = $allCourses
            ->where('is_featured', true)
            ->take(3)
            ->values();

        if ($featured->isEmpty()) {
            $featured = $allCourses->take(3);
        }

        $continueEnrollment = AcademyEnrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['enrolled', 'in_progress'])
            ->where('progress_pct', '<', 100)
            ->with('course')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->first();

        $certificates = AcademyEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('course')
            ->latest('completed_at')
            ->limit(8)
            ->get()
            ->map(fn (AcademyEnrollment $enrollment) => [
                'title' => $enrollment->course?->title ?? 'Course',
                'code' => $enrollment->certificate_code,
                'completed' => $enrollment->completed_at?->format('d M, Y') ?? '',
            ])
            ->all();

        return [
            'courses' => $featured->map(fn (AcademyCourse $course) => $this->presentCourse($course, $enrollments->get($course->id)))->all(),
            'library' => $allCourses->map(fn (AcademyCourse $course) => $this->presentCourse($course, $enrollments->get($course->id)))->all(),
            'continue' => $continueEnrollment && $continueEnrollment->course
                ? [
                    'title' => $continueEnrollment->course->title,
                    'progress' => $continueEnrollment->progress_pct,
                    'enrollment_id' => $continueEnrollment->id,
                    'advance_url' => route('academy.enrollments.advance', $continueEnrollment),
                    'can_advance' => $continueEnrollment->progress_pct < 100,
                ]
                : [
                    'title' => 'Pick a course to begin',
                    'progress' => 0,
                    'enrollment_id' => null,
                    'advance_url' => null,
                    'can_advance' => false,
                ],
            'certificates' => $certificates,
            'stats' => [
                'enrolled' => $enrollments->count(),
                'completed' => $enrollments->where('status', 'completed')->count(),
                'in_progress' => $enrollments->whereIn('status', ['enrolled', 'in_progress'])->where('progress_pct', '<', 100)->count(),
            ],
            'level_filter' => $level,
            'level_options' => ['Beginner', 'Intermediate', 'Advanced'],
            'actions' => [
                'enroll_url' => route('academy.enroll'),
                'filter_url' => route('academy.learning'),
            ],
            'notifications_count' => max(2, ($continueEnrollment ? 1 : 0) + count($certificates)),
        ];
    }

    /**
     * Enroll the user in a course (idempotent if already enrolled).
     */
    public function enroll(User $user, AcademyCourse $course): AcademyEnrollment
    {
        if (! $course->is_active) {
            throw new BusinessLogicException('This course is not available.');
        }

        $existing = AcademyEnrollment::query()
            ->where('user_id', $user->id)
            ->where('academy_course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return AcademyEnrollment::query()->create([
            'user_id' => $user->id,
            'academy_course_id' => $course->id,
            'progress_pct' => 0,
            'status' => 'enrolled',
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Advance course progress by one module step (or complete).
     */
    public function advance(User $user, AcademyEnrollment $enrollment): AcademyEnrollment
    {
        if ($enrollment->user_id !== $user->id) {
            throw new BusinessLogicException('You can only update your own course progress.', statusCode: 403);
        }

        if ($enrollment->isCompleted()) {
            throw new BusinessLogicException('This course is already completed.');
        }

        $enrollment->loadMissing('course');
        $modules = max(1, (int) ($enrollment->course?->modules_count ?? 4));
        $step = (int) ceil(100 / $modules);
        $next = min(100, $enrollment->progress_pct + $step);

        $enrollment->forceFill([
            'progress_pct' => $next,
            'status' => $next >= 100 ? 'completed' : 'in_progress',
            'last_activity_at' => now(),
            'started_at' => $enrollment->started_at ?? now(),
            'completed_at' => $next >= 100 ? now() : null,
            'certificate_code' => $next >= 100
                ? ($enrollment->certificate_code ?: 'CERT-'.strtoupper(Str::random(8)))
                : $enrollment->certificate_code,
        ])->save();

        return $enrollment->fresh(['course']);
    }

    /**
     * Ensure the public academy catalog is available.
     */
    public function ensureCatalog(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (AcademyCourse::query()->exists()) {
            return;
        }

        foreach ($this->seedCourses() as $row) {
            AcademyCourse::query()->create($row);
        }
    }

    /**
     * Seed a continue-learning enrollment so the academy never feels empty.
     */
    protected function ensureStarterEnrollment(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (AcademyEnrollment::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $course = AcademyCourse::query()
            ->where('slug', 'soil-fertility-management')
            ->first();

        if (! $course) {
            return;
        }

        AcademyEnrollment::query()->create([
            'user_id' => $user->id,
            'academy_course_id' => $course->id,
            'progress_pct' => 50,
            'status' => 'in_progress',
            'started_at' => now()->subDays(3),
            'last_activity_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  Collection<int, AcademyEnrollment>|null  $enrollment
     * @return array<string, mixed>
     */
    protected function presentCourse(AcademyCourse $course, ?AcademyEnrollment $enrollment): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'level' => $course->level,
            'level_tone' => $course->level_tone,
            'rating' => number_format((float) $course->rating, 1),
            'duration' => $course->formattedDuration(),
            'image' => $course->image_path,
            'summary' => $course->summary,
            'tags' => collect($course->enterprise_tags ?? [])->implode(', '),
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment?->progress_pct ?? 0,
            'status' => $enrollment?->status,
            'completed' => $enrollment?->isCompleted() ?? false,
            'enroll_url' => route('academy.enroll'),
            'advance_url' => $enrollment ? route('academy.enrollments.advance', $enrollment) : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function seedCourses(): array
    {
        return [
            [
                'title' => 'Modern Maize Farming',
                'slug' => 'modern-maize-farming',
                'level' => 'Beginner',
                'level_tone' => 'muted',
                'rating' => 4.8,
                'duration_minutes' => 90,
                'image_path' => 'images/academy/maize-farming.jpg',
                'summary' => 'Planting, spacing, and yield practices for maize enterprises.',
                'enterprise_tags' => ['Maize', 'Crops'],
                'modules_count' => 4,
                'sort_order' => 10,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'title' => 'Smart Irrigation Techniques',
                'slug' => 'smart-irrigation-techniques',
                'level' => 'Intermediate',
                'level_tone' => 'green',
                'rating' => 4.7,
                'duration_minutes' => 70,
                'image_path' => 'images/academy/irrigation.jpg',
                'summary' => 'Water-efficient irrigation for field crops and horticulture.',
                'enterprise_tags' => ['Crops', 'Vegetables'],
                'modules_count' => 4,
                'sort_order' => 20,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'title' => 'Pest & Disease Management',
                'slug' => 'pest-disease-management',
                'level' => 'Beginner',
                'level_tone' => 'muted',
                'rating' => 4.8,
                'duration_minutes' => 80,
                'image_path' => 'images/academy/pest-disease.jpg',
                'summary' => 'Identify and manage common crop pests and plant diseases.',
                'enterprise_tags' => ['Crops', 'Maize', 'Cassava'],
                'modules_count' => 4,
                'sort_order' => 30,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'title' => 'Soil Fertility Management',
                'slug' => 'soil-fertility-management',
                'level' => 'Intermediate',
                'level_tone' => 'green',
                'rating' => 4.6,
                'duration_minutes' => 100,
                'image_path' => 'images/academy/maize-farming.jpg',
                'summary' => 'Soil testing, organic matter, and fertilizer planning.',
                'enterprise_tags' => ['Crops', 'Soil'],
                'modules_count' => 4,
                'sort_order' => 40,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Broiler Production Basics',
                'slug' => 'broiler-production-basics',
                'level' => 'Beginner',
                'level_tone' => 'muted',
                'rating' => 4.5,
                'duration_minutes' => 85,
                'image_path' => 'images/academy/pest-disease.jpg',
                'summary' => 'Housing, feeding, and biosecurity for broiler poultry.',
                'enterprise_tags' => ['Poultry', 'Broilers'],
                'modules_count' => 4,
                'sort_order' => 50,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Pond Fish Farming',
                'slug' => 'pond-fish-farming',
                'level' => 'Beginner',
                'level_tone' => 'muted',
                'rating' => 4.4,
                'duration_minutes' => 75,
                'image_path' => 'images/academy/irrigation.jpg',
                'summary' => 'Pond setup, stocking, and water quality for aquaculture.',
                'enterprise_tags' => ['Aquaculture', 'Catfish'],
                'modules_count' => 4,
                'sort_order' => 60,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Small Ruminant Care',
                'slug' => 'small-ruminant-care',
                'level' => 'Intermediate',
                'level_tone' => 'green',
                'rating' => 4.5,
                'duration_minutes' => 95,
                'image_path' => 'images/academy/maize-farming.jpg',
                'summary' => 'Goat and sheep health, feeding, and breeding basics.',
                'enterprise_tags' => ['Livestock', 'Goats', 'Sheep'],
                'modules_count' => 4,
                'sort_order' => 70,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];
    }
}
