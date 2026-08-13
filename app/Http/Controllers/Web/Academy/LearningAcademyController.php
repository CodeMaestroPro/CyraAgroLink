<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Academy;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\AcademyCourse;
use App\Models\AcademyEnrollment;
use App\Services\Academy\LearningAcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agricultural learning academy for farmer courses and progress.
 */
class LearningAcademyController extends Controller
{
    public function __construct(
        protected LearningAcademyService $learningAcademyService
    ) {
    }

    /**
     * Display the agricultural learning academy.
     */
    public function index(Request $request): View
    {
        $data = $this->learningAcademyService->getAcademyData(
            $request->user(),
            $request->string('level')->toString() ?: null
        );

        return view('academy.learning', [
            'courses' => $data['courses'],
            'library' => $data['library'],
            'continue' => $data['continue'],
            'certificates' => $data['certificates'],
            'stats' => $data['stats'],
            'levelFilter' => $data['level_filter'],
            'levelOptions' => $data['level_options'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Enroll in a course.
     */
    public function enroll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:academy_courses,id'],
        ]);

        $course = AcademyCourse::query()->findOrFail((int) $data['course_id']);

        try {
            $enrollment = $this->learningAcademyService->enroll($request->user(), $course);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('academy.learning')
                ->with('error', $e->getMessage())
                ->withFragment('library');
        }

        return redirect()
            ->route('academy.learning')
            ->with('status', $enrollment->wasRecentlyCreated
                ? 'Enrolled in '.$course->title.'.'
                : 'You are already enrolled in '.$course->title.'.')
            ->withFragment('continue');
    }

    /**
     * Advance progress on an enrollment.
     */
    public function advance(Request $request, AcademyEnrollment $enrollment): RedirectResponse
    {
        try {
            $updated = $this->learningAcademyService->advance($request->user(), $enrollment);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('academy.learning')
                ->with('error', $e->getMessage())
                ->withFragment('continue');
        }

        $title = $updated->course?->title ?? 'Course';
        $message = $updated->isCompleted()
            ? 'Completed '.$title.'. Certificate '.$updated->certificate_code.' issued.'
            : 'Progress updated: '.$updated->progress_pct.'% on '.$title.'.';

        return redirect()
            ->route('academy.learning')
            ->with('status', $message)
            ->withFragment($updated->isCompleted() ? 'certificates' : 'continue');
    }
}
