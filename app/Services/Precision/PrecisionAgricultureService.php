<?php

declare(strict_types=1);

namespace App\Services\Precision;

use App\Enums\CropActivityType;
use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\User;
use App\Services\Crop\CropManagementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Live precision agriculture overview from the farmer's registered farms.
 *
 * Soil / NDVI / irrigation overlays are derived from farm + crop records,
 * with session overrides for scan, irrigate, and fertilizer plan actions.
 */
class PrecisionAgricultureService
{
    public function __construct(
        protected CropManagementService $cropManagementService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(User $user, ?int $farmId = null): array
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            if (! \App\Support\DemoSeeding::allowed()) {
                throw new BusinessLogicException('Register a farm before using Precision Agriculture.', 'FARM_REQUIRED', 422);
            }
            $this->ensureSeedFarm($user);
            $farms = $this->farmsForUser($user);
        }

        /** @var Farm $farm */
        $farm = $farmId !== null
            ? ($farms->firstWhere('id', $farmId) ?? $farms->first())
            : $farms->first();

        $farm->loadMissing('cropRecords');
        $overlay = $this->overlayFor($farm->id);
        $crops = $farm->cropRecords;

        return [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name ?: 'Untitled farm',
                'location' => trim(implode(', ', array_filter([$farm->local_government, $farm->state]))) ?: 'Nigeria',
                'soil_type' => $farm->soil_type ?: 'Not set',
            ],
            'farms' => $farms->map(fn (Farm $item) => [
                'id' => $item->id,
                'name' => $item->name ?: 'Untitled farm',
                'active' => $item->id === $farm->id,
                'url' => route('precision.agriculture', ['farm' => $item->id]),
            ])->values()->all(),
            'soil' => $this->soilMetrics($farm, $crops, $overlay),
            'irrigation' => $this->irrigation($overlay),
            'fertilizer' => $this->fertilizer($crops, $overlay),
            'ndvi_zones' => $this->ndviZones($farm, $crops, $overlay),
            'map' => $this->mapCenter($farm),
            'recommendation_detail' => $this->recommendationDetail($crops, $overlay),
            'actions' => [
                'scan_url' => route('precision.scan', $farm),
                'irrigate_url' => route('precision.irrigate', $farm),
                'fertilizer_url' => route('precision.fertilizer', $farm),
            ],
            'last_scan_at' => $overlay['last_scan_at'] ?? null,
            'notifications_count' => 3,
        ];
    }

    /**
     * Recalculate NDVI / soil snapshot for the farm twin.
     *
     * @return array<string, mixed>
     */
    public function runNdviScan(User $user, Farm $farm): array
    {
        abort_unless($farm->user_id === $user->id, 403);
        $farm->loadMissing('cropRecords');

        $overlay = $this->overlayFor($farm->id);
        $overlay['last_scan_at'] = now()->toIso8601String();
        $currentJitter = (float) ($overlay['ndvi_jitter'] ?? 0);
        $overlay['ndvi_jitter'] = round(fmod($currentJitter + 0.02, 0.08), 3);
        $overlay['soil_n'] = min(95, ($overlay['soil_n'] ?? $this->baseNutrient($farm, 'n')) + 2);
        $overlay['notes'] = 'NDVI scan refreshed from field health signals.';
        $this->putOverlay($farm->id, $overlay);

        return $overlay;
    }

    /**
     * Schedule / activate next irrigation window.
     *
     * @return array<string, mixed>
     */
    public function scheduleIrrigation(User $user, Farm $farm): array
    {
        abort_unless($farm->user_id === $user->id, 403);

        $overlay = $this->overlayFor($farm->id);
        $overlay['irrigation_status'] = 'System Active';
        $overlay['next_irrigation'] = now()->addHours(6)->format('h:i A');
        $overlay['last_irrigation_scheduled_at'] = now()->toIso8601String();
        $overlay['notes'] = 'Next irrigation window scheduled.';
        $this->putOverlay($farm->id, $overlay);

        return $overlay;
    }

    /**
     * Apply the fertilizer recommendation: log a crop fertilizer activity and refresh soil overlay.
     *
     * @return array<string, mixed>
     */
    public function applyFertilizerPlan(User $user, Farm $farm): array
    {
        abort_unless($farm->user_id === $user->id, 403);
        $farm->loadMissing('cropRecords');

        $crop = $this->resolvePrimaryCrop($user, $farm);
        $crops = $farm->cropRecords->isNotEmpty()
            ? $farm->cropRecords
            : collect([$crop]);

        $plan = $this->fertilizer($crops, $this->overlayFor($farm->id));

        $this->cropManagementService->recordCareEvent($user, $crop, CropActivityType::Fertilizer, [
            'title' => 'Precision plan applied: '.$plan['formula'],
            'notes' => $plan['recommendation'].'. Logged from Precision Agriculture for '.$farm->name.'.',
            'quantity' => $plan['formula'],
            'next_activity' => 'Monitor fertilizer uptake',
            'next_activity_at' => now()->addDays(5)->toDateString(),
        ]);

        $crop->forceFill([
            'ai_recommendation' => $plan['recommendation'].' — applied via Precision Agriculture on '.now()->format('M j, Y'),
        ])->save();

        $overlay = $this->overlayFor($farm->id);
        $overlay['fertilizer_applied'] = true;
        $overlay['fertilizer_applied_at'] = now()->toIso8601String();
        $overlay['fertilizer_crop_id'] = $crop->id;
        $overlay['fertilizer_crop_name'] = $crop->name;
        $overlay['fertilizer_formula'] = $plan['formula'];
        $overlay['fertilizer_recommendation'] = $plan['recommendation'];
        $overlay['soil_n'] = min(95, ($overlay['soil_n'] ?? $this->baseNutrient($farm, 'n')) + 4);
        $overlay['notes'] = 'Fertilizer plan applied and logged on '.$crop->name.'.';
        $this->putOverlay($farm->id, $overlay);

        return $overlay;
    }

    /**
     * @return Collection<int, Farm>
     */
    protected function farmsForUser(User $user): Collection
    {
        return Farm::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview, FarmStatus::Draft])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->get();
    }

    protected function ensureSeedFarm(User $user): Farm
    {
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Valley Farm',
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Precision demo field',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '4.50',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Cassava'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        Crop::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'variety' => 'SAMMAZ 15',
            'growth_stage' => CropGrowthStage::Vegetative,
            'progress_percent' => 50,
            'health_status' => CropHealthStatus::Good,
            'status' => 'active',
            'planted_at' => now()->subDays(45),
            'expected_harvest_at' => now()->addDays(60),
            'ai_recommendation' => 'Apply NPK 20-10-10 as top dress.',
        ]);

        return $farm;
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     * @return list<array{label: string, value: string, status: string}>
     */
    protected function soilMetrics(Farm $farm, Collection $crops, array $overlay): array
    {
        $n = (int) ($overlay['soil_n'] ?? $this->baseNutrient($farm, 'n'));
        $p = (int) ($overlay['soil_p'] ?? $this->baseNutrient($farm, 'p'));
        $k = (int) ($overlay['soil_k'] ?? $this->baseNutrient($farm, 'k'));
        $ph = $overlay['soil_ph'] ?? $this->basePh($farm);

        return [
            ['label' => 'Soil N (Nitrogen)', 'value' => $n.'%', 'status' => $this->nutrientStatus($n)],
            ['label' => 'Soil P (Phosphorus)', 'value' => $p.'%', 'status' => $this->nutrientStatus($p)],
            ['label' => 'Soil K (Potassium)', 'value' => $k.'%', 'status' => $this->nutrientStatus($k)],
            ['label' => 'pH Level', 'value' => number_format((float) $ph, 1), 'status' => $this->phStatus((float) $ph)],
        ];
    }

    /**
     * @param  array<string, mixed>  $overlay
     * @return array{status: string, next: string}
     */
    protected function irrigation(array $overlay): array
    {
        return [
            'status' => $overlay['irrigation_status'] ?? 'System Standby',
            'next' => $overlay['next_irrigation'] ?? now()->setTime(16, 30)->format('h:i A'),
        ];
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     * @return array{
     *     recommendation: string,
     *     formula: string,
     *     applied: bool,
     *     applied_at: string|null,
     *     crop_name: string|null,
     *     crop_url: string|null
     * }
     */
    protected function fertilizer(Collection $crops, array $overlay): array
    {
        $crop = $crops->sortByDesc(fn (Crop $item) => $item->progress_percent)->first();

        if (! empty($overlay['fertilizer_recommendation']) && ! empty($overlay['fertilizer_formula'])) {
            $formula = (string) $overlay['fertilizer_formula'];
            $recommendation = (string) $overlay['fertilizer_recommendation'];
        } else {
            $name = Str::lower($crop?->name ?? 'maize');

            [$formula, $recommendation] = match (true) {
                Str::contains($name, ['rice']) => ['NPK 15-15-15', 'Apply NPK 15-15-15'],
                Str::contains($name, ['cassava']) => ['NPK 12-12-17', 'Apply NPK 12-12-17'],
                Str::contains($name, ['tomato', 'pepper', 'vegetable']) => ['NPK 15-15-15', 'Apply NPK 15-15-15'],
                default => ['NPK 20-10-10', 'Apply NPK 20-10-10'],
            };

            if ($crop?->growth_stage === CropGrowthStage::Flowering || $crop?->growth_stage === CropGrowthStage::Vegetative) {
                $recommendation .= ' as top dress';
            }
        }

        $cropId = isset($overlay['fertilizer_crop_id'])
            ? (int) $overlay['fertilizer_crop_id']
            : ($crop?->id);

        return [
            'recommendation' => $recommendation,
            'formula' => $formula,
            'applied' => ! empty($overlay['fertilizer_applied']),
            'applied_at' => isset($overlay['fertilizer_applied_at'])
                ? Carbon::parse((string) $overlay['fertilizer_applied_at'])->format('M j, Y g:i A')
                : null,
            'crop_name' => $overlay['fertilizer_crop_name'] ?? $crop?->name,
            'crop_url' => $cropId
                ? route('crops.manage', ['crop' => $cropId, 'tab' => 'fertilizer'])
                : null,
        ];
    }

    /**
     * Pick the farm's primary active crop, seeding one when the farm has none.
     */
    protected function resolvePrimaryCrop(User $user, Farm $farm): Crop
    {
        $crop = $farm->cropRecords
            ->filter(fn (Crop $item) => $item->status === 'active')
            ->sortByDesc(fn (Crop $item) => $item->progress_percent)
            ->first()
            ?? $farm->cropRecords->sortByDesc(fn (Crop $item) => $item->progress_percent)->first();

        if ($crop !== null) {
            return $crop;
        }

        /** @var Crop $created */
        $created = Crop::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'variety' => 'SAMMAZ 15',
            'growth_stage' => CropGrowthStage::Vegetative,
            'progress_percent' => 50,
            'health_status' => CropHealthStatus::Good,
            'status' => 'active',
            'planted_at' => now()->subDays(45),
            'expected_harvest_at' => now()->addDays(60),
            'next_activity' => 'Apply fertilizer',
            'next_activity_at' => now()->addDay(),
            'ai_recommendation' => 'Apply NPK 20-10-10 as top dress.',
        ]);

        $farm->setRelation('cropRecords', collect([$created]));

        return $created;
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     * @return list<array{lat: float, lng: float, ndvi: float, label: string}>
     */
    protected function ndviZones(Farm $farm, Collection $crops, array $overlay): array
    {
        $lat = (float) ($farm->latitude ?: 7.3775);
        $lng = (float) ($farm->longitude ?: 3.9470);
        $jitter = (float) ($overlay['ndvi_jitter'] ?? 0);
        $base = $this->baseNdvi($crops) + $jitter;

        $offsets = [
            [0.0015, -0.0020], [0.0028, 0.0010], [0.0000, 0.0005],
            [-0.0012, 0.0025], [0.0010, 0.0035], [-0.0020, -0.0010],
            [-0.0025, 0.0020], [0.0030, 0.0028], [-0.0005, -0.0025],
            [-0.0030, 0.0000],
        ];

        $zones = [];
        foreach ($offsets as $index => [$dLat, $dLng]) {
            $wave = (($index % 3) - 1) * 0.08;
            $ndvi = max(0.28, min(0.92, $base + $wave - ($index * 0.015)));
            $zones[] = [
                'lat' => $lat + $dLat,
                'lng' => $lng + $dLng,
                'ndvi' => round($ndvi, 2),
                'label' => $this->ndviLabel($ndvi),
            ];
        }

        return $zones;
    }

    /**
     * @return array{lat: float, lng: float, zoom: float}
     */
    protected function mapCenter(Farm $farm): array
    {
        return [
            'lat' => (float) ($farm->latitude ?: 7.3775),
            'lng' => (float) ($farm->longitude ?: 3.9470),
            'zoom' => 15.2,
        ];
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     */
    protected function recommendationDetail(Collection $crops, array $overlay): string
    {
        $plan = $this->fertilizer($crops, $overlay);
        $crop = $crops->first();
        $stage = $crop?->growth_stage->label() ?? 'current stage';

        return "For {$plan['formula']} on "
            .($crop?->name ?? 'your crop')
            ." ({$stage}): split nitrogen where possible, water in after application, and avoid burning seedlings on dry soil. "
            .'Confirm rates with a soil test when available.';
    }

    /**
     * @param  Collection<int, Crop>  $crops
     */
    protected function baseNdvi(Collection $crops): float
    {
        if ($crops->isEmpty()) {
            return 0.68;
        }

        $avg = $crops->avg(fn (Crop $crop) => match ($crop->health_status) {
            CropHealthStatus::Good => 0.78,
            CropHealthStatus::Fair => 0.62,
            CropHealthStatus::Poor => 0.45,
            CropHealthStatus::Critical => 0.32,
            default => 0.60,
        });

        return (float) $avg;
    }

    protected function baseNutrient(Farm $farm, string $key): int
    {
        $seed = crc32($farm->id.'|'.$key);
        $base = match ($key) {
            'n' => 70,
            'p' => 58,
            'k' => 72,
            default => 65,
        };

        return $base + ($seed % 16);
    }

    protected function basePh(Farm $farm): float
    {
        $seed = crc32($farm->id.'|ph');

        return round(5.8 + (($seed % 12) / 10), 1);
    }

    protected function nutrientStatus(int $value): string
    {
        return $value >= 65 ? 'Optimal' : ($value >= 45 ? 'Fair' : 'Low');
    }

    protected function phStatus(float $ph): string
    {
        return ($ph >= 5.5 && $ph <= 7.2) ? 'Good' : 'Adjust';
    }

    protected function ndviLabel(float $ndvi): string
    {
        return match (true) {
            $ndvi >= 0.7 => 'High vigor',
            $ndvi >= 0.55 => 'Healthy',
            $ndvi >= 0.45 => 'Moderate',
            default => 'Stress',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function overlayFor(int $farmId): array
    {
        return session($this->overlayKey($farmId), []);
    }

    /**
     * @param  array<string, mixed>  $overlay
     */
    protected function putOverlay(int $farmId, array $overlay): void
    {
        session([$this->overlayKey($farmId) => $overlay]);
    }

    protected function overlayKey(int $farmId): string
    {
        return 'precision_overlay_'.$farmId;
    }
}
