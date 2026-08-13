<?php

declare(strict_types=1);

namespace App\Services\Crop;

use App\Contracts\Repositories\CropRepositoryInterface;
use App\Contracts\Repositories\FarmRepositoryInterface;
use App\Enums\CropActivityType;
use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Crop;
use App\Models\CropActivity;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crop management domain service with full lifecycle actions.
 */
class CropManagementService
{
    public function __construct(
        protected CropRepositoryInterface $cropRepository,
        protected FarmRepositoryInterface $farmRepository
    ) {
    }

    /**
     * Resolve the crop to display, seeding a demo crop when none exist yet.
     */
    public function resolveCropForUser(User $user, ?int $cropId = null): Crop
    {
        if ($cropId !== null) {
            return $this->cropRepository->findOwnedOrFail($user, $cropId);
        }

        $crop = $this->cropRepository->findLatestActiveForUser($user);

        if ($crop !== null) {
            return $crop;
        }

        $any = $this->cropRepository->getForUser($user)->first();

        if ($any !== null) {
            return $any;
        }

        if (! \App\Support\DemoSeeding::allowed()) {
            throw new BusinessLogicException(
                'Register a farm and start a crop cycle before using Crop Management.',
                'CROP_REQUIRED',
                422
            );
        }

        return $this->seedDemoCrop($user);
    }

    /**
     * Build overview payload for the Crop Management UI.
     *
     * @return array<string, mixed>
     */
    public function getOverview(User $user, ?int $cropId = null, string $tab = 'overview'): array
    {
        $crop = $this->resolveCropForUser($user, $cropId);
        $crops = $this->cropRepository->getForUser($user);
        $farms = $this->farmRepository->getForUser($user)
            ->filter(fn (Farm $farm) => in_array($farm->status, [FarmStatus::Active, FarmStatus::PendingReview, FarmStatus::Draft], true))
            ->values();

        $activityType = match ($tab) {
            'irrigation' => CropActivityType::Irrigation,
            'fertilizer' => CropActivityType::Fertilizer,
            'health' => CropActivityType::Health,
            'harvest' => CropActivityType::Harvest,
            'activities' => null,
            default => null,
        };

        $activitiesQuery = CropActivity::query()
            ->where('crop_id', $crop->id)
            ->latest('occurred_at')
            ->latest('id');

        if ($activityType !== null) {
            $activitiesQuery->where('type', $activityType);
        } elseif ($tab === 'activities') {
            $activitiesQuery->whereIn('type', [
                CropActivityType::Activity,
                CropActivityType::Irrigation,
                CropActivityType::Fertilizer,
                CropActivityType::Health,
            ]);
        }

        return [
            'crop' => $crop->loadMissing('farm'),
            'crops' => $crops,
            'farms' => $farms,
            'tabs' => $this->tabs(),
            'stages' => $this->stageTimeline($crop),
            'timeline_percent' => min(100, max(0, (int) $crop->progress_percent)),
            'activities' => $tab === 'overview'
                ? $activitiesQuery->limit(5)->get()
                : $activitiesQuery->limit(40)->get(),
            'growth_stages' => CropGrowthStage::cases(),
            'health_statuses' => CropHealthStatus::cases(),
            'crop_options' => config('cyra.enterprise_options', config('cyra.crop_options', [])),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCrop(User $user, array $data): Crop
    {
        $farm = $this->farmRepository->findOwnedOrFail($user, (int) $data['farm_id']);

        $stage = CropGrowthStage::tryFrom((string) ($data['growth_stage'] ?? ''))
            ?? CropGrowthStage::Seedling;

        /** @var Crop $crop */
        $crop = $this->cropRepository->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'variety' => $data['variety'] ?? null,
            'growth_stage' => $stage,
            'progress_percent' => $stage->timelinePercent(),
            'health_status' => CropHealthStatus::Good,
            'health_notes' => 'Newly registered crop cycle',
            'next_activity' => 'Field inspection',
            'next_activity_at' => now()->addDays(3),
            'planted_at' => $data['planted_at'] ?? now(),
            'expected_harvest_at' => $data['expected_harvest_at'] ?? now()->addDays(90),
            'ai_recommendation' => $this->recommendationFor($stage, CropHealthStatus::Good),
            'status' => 'active',
        ]);

        $this->logActivity($user, $crop, CropActivityType::Activity, 'Crop cycle started', 'Crop registered on '.$farm->name);

        return $crop->load('farm');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function logActivity(User $user, Crop $crop, CropActivityType $type, string $title, ?string $notes = null, ?string $quantity = null, array $meta = []): CropActivity
    {
        $this->assertOwned($user, $crop);

        return CropActivity::query()->create([
            'crop_id' => $crop->id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'notes' => $notes,
            'quantity' => $quantity,
            'occurred_at' => now(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordCareEvent(User $user, Crop $crop, CropActivityType $type, array $data): Crop
    {
        $this->assertOwnedActive($user, $crop);

        $title = match ($type) {
            CropActivityType::Irrigation => $data['title'] ?? 'Irrigation applied',
            CropActivityType::Fertilizer => $data['title'] ?? 'Fertilizer applied',
            CropActivityType::Activity => $data['title'] ?? 'Field activity',
            default => $data['title'] ?? $type->label(),
        };

        $this->logActivity(
            $user,
            $crop,
            $type,
            $title,
            $data['notes'] ?? null,
            $data['quantity'] ?? null
        );

        $crop->forceFill([
            'next_activity' => $data['next_activity'] ?? $this->defaultNextActivity($type),
            'next_activity_at' => isset($data['next_activity_at'])
                ? Carbon::parse((string) $data['next_activity_at'])
                : now()->addDays(7),
            'ai_recommendation' => $this->recommendationFor($crop->growth_stage, $crop->health_status),
        ])->save();

        return $crop->refresh()->load('farm');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateHealth(User $user, Crop $crop, array $data): Crop
    {
        $this->assertOwnedActive($user, $crop);

        $status = CropHealthStatus::from((string) $data['health_status']);

        $crop->forceFill([
            'health_status' => $status,
            'health_notes' => $data['health_notes'] ?? null,
            'ai_recommendation' => $this->recommendationFor($crop->growth_stage, $status),
        ])->save();

        $this->logActivity(
            $user,
            $crop,
            CropActivityType::Health,
            'Health status updated to '.$status->label(),
            $data['health_notes'] ?? null
        );

        return $crop->refresh()->load('farm');
    }

    public function advanceStage(User $user, Crop $crop): Crop
    {
        $this->assertOwnedActive($user, $crop);

        $order = CropGrowthStage::timeline();
        $index = array_search($crop->growth_stage, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            throw new BusinessLogicException('Crop is already at the final growth stage.');
        }

        /** @var CropGrowthStage $next */
        $next = $order[$index + 1];

        $crop->forceFill([
            'growth_stage' => $next,
            'progress_percent' => $next->timelinePercent(),
            'next_activity' => $next === CropGrowthStage::Maturity ? 'Prepare harvest' : 'Monitor growth',
            'next_activity_at' => now()->addDays(5),
            'ai_recommendation' => $this->recommendationFor($next, $crop->health_status),
        ])->save();

        $this->logActivity($user, $crop, CropActivityType::Activity, 'Advanced to '.$next->label());

        return $crop->refresh()->load('farm');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordHarvest(User $user, Crop $crop, array $data): Crop
    {
        $this->assertOwnedActive($user, $crop);

        $quantity = $data['quantity'] ?? null;

        $crop->forceFill([
            'growth_stage' => CropGrowthStage::Maturity,
            'progress_percent' => 100,
            'status' => 'harvested',
            'next_activity' => 'Post-harvest handling',
            'next_activity_at' => now()->addDays(2),
            'ai_recommendation' => 'Dry, grade, and list surplus on the Smart Marketplace.',
            'health_notes' => $data['notes'] ?? $crop->health_notes,
        ])->save();

        $this->logActivity(
            $user,
            $crop,
            CropActivityType::Harvest,
            'Harvest recorded',
            $data['notes'] ?? null,
            is_string($quantity) || is_numeric($quantity) ? (string) $quantity : null
        );

        return $crop->refresh()->load('farm');
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    protected function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview'],
            ['key' => 'activities', 'label' => 'Activities'],
            ['key' => 'irrigation', 'label' => 'Irrigation'],
            ['key' => 'fertilizer', 'label' => 'Fertilizer'],
            ['key' => 'health', 'label' => 'Health'],
            ['key' => 'harvest', 'label' => 'Harvest'],
        ];
    }

    /**
     * @return list<array{key: string, label: string, reached: bool, current: bool}>
     */
    protected function stageTimeline(Crop $crop): array
    {
        $current = $crop->growth_stage;
        $order = CropGrowthStage::timeline();
        $currentIndex = array_search($current, $order, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        $stages = [];

        foreach ($order as $index => $stage) {
            $stages[] = [
                'key' => $stage->value,
                'label' => $stage->label(),
                'reached' => $index <= $currentIndex,
                'current' => $index === $currentIndex,
            ];
        }

        return $stages;
    }

    protected function seedDemoCrop(User $user): Crop
    {
        return DB::transaction(function () use ($user): Crop {
            $farm = $this->farmRepository->getForUser($user)
                ->first(fn (Farm $item) => $item->status === FarmStatus::Active);

            if ($farm === null) {
                /** @var Farm $farm */
                $farm = $this->farmRepository->create([
                    'user_id' => $user->id,
                    'name' => 'Green Valley Farm',
                    'state' => 'Oyo',
                    'local_government' => 'Ibadan North',
                    'address' => 'Akinyele, Ibadan',
                    'latitude' => 7.3775,
                    'longitude' => 3.9470,
                    'status' => FarmStatus::Active,
                    'registration_step' => 5,
                    'registered_at' => now(),
                    'crops' => ['Maize'],
                ]);
            }

            /** @var Crop $crop */
            $crop = $this->cropRepository->create([
                'farm_id' => $farm->id,
                'user_id' => $user->id,
                'name' => 'Maize',
                'variety' => 'Hybrid Yellow',
                'growth_stage' => CropGrowthStage::Vegetative,
                'progress_percent' => 75,
                'health_status' => CropHealthStatus::Good,
                'health_notes' => 'No issues detected',
                'next_activity' => 'Top Dressing',
                'next_activity_at' => now()->addDays(5),
                'planted_at' => now()->subDays(40),
                'expected_harvest_at' => now()->addDays(45)->startOfDay(),
                'ai_recommendation' => 'Apply NPK 20-10-10 fertilizer in 5 days',
                'status' => 'active',
            ]);

            $this->logActivity($user, $crop, CropActivityType::Activity, 'Demo crop cycle ready', 'Starter maize cycle for Crop Management');
            $this->logActivity($user, $crop, CropActivityType::Irrigation, 'Drip irrigation cycle', 'Morning irrigation completed', '25 mm');

            return $crop->load('farm');
        });
    }

    protected function assertOwned(User $user, Crop $crop): void
    {
        if ($crop->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to manage this crop.', 'CROP_FORBIDDEN', 403);
        }
    }

    protected function assertOwnedActive(User $user, Crop $crop): void
    {
        $this->assertOwned($user, $crop);

        if ($crop->status !== 'active') {
            throw new BusinessLogicException('This crop cycle is closed and can no longer be updated.');
        }
    }

    protected function defaultNextActivity(CropActivityType $type): string
    {
        return match ($type) {
            CropActivityType::Irrigation => 'Check soil moisture',
            CropActivityType::Fertilizer => 'Monitor nutrient response',
            default => 'Field inspection',
        };
    }

    protected function recommendationFor(CropGrowthStage $stage, CropHealthStatus $health): string
    {
        if ($health === CropHealthStatus::Critical || $health === CropHealthStatus::Poor) {
            return 'Inspect for pests/disease today and adjust irrigation before the next fertilizer cycle.';
        }

        return match ($stage) {
            CropGrowthStage::Seedling => 'Keep soil moist and protect seedlings from heat stress for 7 days.',
            CropGrowthStage::Vegetative => 'Apply NPK 20-10-10 fertilizer in 5 days',
            CropGrowthStage::Flowering => 'Maintain consistent irrigation and avoid nitrogen overload.',
            CropGrowthStage::Maturity => 'Reduce irrigation and prepare drying/storage for harvest.',
        };
    }
}
