<?php

declare(strict_types=1);

namespace App\Services\DigitalTwin;

use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\User;
use App\Services\Weather\WeatherIntelligenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Live digital twin farm overview from the user's registered farms and crops.
 *
 * Plot geometry is synthesized around the farm coordinates. Moisture / pest
 * overlays are session-backed so irrigate and scan actions update the twin.
 */
class DigitalTwinFarmService
{
    public function __construct(
        protected WeatherIntelligenceService $weatherService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFarmData(User $user, ?int $farmId = null): array
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            if (! \App\Support\DemoSeeding::allowed()) {
                throw new BusinessLogicException('Register a farm before using Digital Twin.', 'FARM_REQUIRED', 422);
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
        $enterprises = $this->enterpriseList($farm, $crops);

        return [
            'farm' => $this->farmCard($farm, $crops),
            'farms' => $farms->map(fn (Farm $item) => [
                'id' => $item->id,
                'name' => $item->name ?: 'Untitled farm',
                'active' => $item->id === $farm->id,
                'url' => route('digital.twin', ['farm' => $item->id]),
            ])->values()->all(),
            'kpis' => $this->kpis($farm, $crops, $overlay),
            'widgets' => $this->widgets($user, $farm, $crops, $overlay),
            'plots' => $this->plots($farm, $enterprises, $crops),
            'map' => $this->mapCenter($farm),
            'alerts' => $overlay['alerts'] ?? [],
            'actions' => [
                'scan_url' => route('digital.twin.scan', $farm),
                'irrigate_url' => route('digital.twin.irrigate', $farm),
            ],
            'last_scan_at' => $overlay['last_scan_at'] ?? null,
            'notifications_count' => 2,
        ];
    }

    /**
     * Run a twin health scan and refresh pest / moisture advisories.
     *
     * @return array<string, mixed>
     */
    public function runScan(User $user, Farm $farm): array
    {
        abort_unless($farm->user_id === $user->id, 403);
        $farm->loadMissing('cropRecords');

        $overlay = $this->overlayFor($farm->id);
        $avgHealth = $this->averageHealthPercent($farm->cropRecords);
        $moisture = (int) ($overlay['moisture'] ?? $this->baseMoisture($farm));

        $pest = match (true) {
            $avgHealth < 50 => 'High',
            $avgHealth < 70 => 'Moderate',
            default => 'Low',
        };

        $alerts = [];
        if ($moisture < 45) {
            $alerts[] = 'Soil moisture is low — schedule irrigation within 24–48 hours.';
        }
        if ($pest !== 'Low') {
            $alerts[] = "Pest risk is {$pest}. Scout plots and check leaves for armyworm or blight.";
        }
        foreach ($farm->cropRecords as $crop) {
            if ($crop->health_status === CropHealthStatus::Poor || $crop->health_status === CropHealthStatus::Critical) {
                $alerts[] = "{$crop->name} health is {$crop->health_status->label()} — review AI crop recommendations.";
            }
        }
        if ($alerts === []) {
            $alerts[] = 'Twin scan complete — no critical stress flags on this farm.';
        }

        $overlay['pest_risk'] = $pest;
        $overlay['moisture'] = $moisture;
        $overlay['last_scan_at'] = now()->toIso8601String();
        $overlay['alerts'] = array_values(array_unique($alerts));
        $overlay['crop_health_boost'] = min(5, (int) ($overlay['crop_health_boost'] ?? 0) + 1);
        $this->putOverlay($farm->id, $overlay);

        return $overlay;
    }

    /**
     * Simulate an irrigation event on the twin (raises soil moisture).
     *
     * @return array<string, mixed>
     */
    public function simulateIrrigation(User $user, Farm $farm): array
    {
        abort_unless($farm->user_id === $user->id, 403);

        $overlay = $this->overlayFor($farm->id);
        $current = (int) ($overlay['moisture'] ?? $this->baseMoisture($farm));
        $overlay['moisture'] = min(95, $current + 12);
        $overlay['last_irrigation_at'] = now()->toIso8601String();
        $overlay['alerts'] = array_values(array_filter(
            $overlay['alerts'] ?? [],
            fn (string $alert): bool => ! Str::contains(Str::lower($alert), 'moisture is low')
        ));
        $overlay['alerts'][] = 'Irrigation applied on twin — soil moisture updated.';
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
            'address' => 'Green Valley Twin Plot',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '4.50',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Cassava', 'Catfish Farming'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        Crop::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'name' => 'Maize',
            'variety' => 'SAMMAZ 15',
            'growth_stage' => CropGrowthStage::Flowering,
            'progress_percent' => 75,
            'health_status' => CropHealthStatus::Good,
            'health_notes' => 'Twin seed crop',
            'next_activity' => 'Scout for fall armyworm',
            'next_activity_at' => now()->addDays(2),
            'planted_at' => now()->subDays(70),
            'expected_harvest_at' => now()->addDays(40),
            'ai_recommendation' => 'Maintain moisture through grain fill.',
            'status' => 'active',
        ]);

        return $farm;
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @return array<string, mixed>
     */
    protected function farmCard(Farm $farm, Collection $crops): array
    {
        $health = $this->averageHealthPercent($crops);

        return [
            'overview_label' => 'Farm Overview',
            'name' => $farm->name ?: 'Untitled farm',
            'status' => match (true) {
                $health >= 75 => 'Healthy',
                $health >= 55 => 'Watch',
                default => 'At Risk',
            },
            'location' => trim(implode(', ', array_filter([
                $farm->local_government,
                $farm->state,
            ]))) ?: 'Nigeria',
            'size_hectares' => $farm->size_hectares,
            'soil_type' => $farm->soil_type ?: 'Not set',
        ];
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     * @return list<array{label: string, value: string}>
     */
    protected function kpis(Farm $farm, Collection $crops, array $overlay): array
    {
        $health = $this->averageHealthPercent($crops) + (int) ($overlay['crop_health_boost'] ?? 0);
        $health = min(99, $health);
        $moisture = (int) ($overlay['moisture'] ?? $this->baseMoisture($farm));
        $stage = $crops->sortByDesc(fn (Crop $crop) => $crop->progress_percent)->first();
        $yieldTons = $this->estimateYieldTons($farm, $crops);

        return [
            ['label' => 'Crop Health', 'value' => $health.'%'],
            ['label' => 'Soil Moisture', 'value' => $moisture.'%'],
            ['label' => 'Growth Stage', 'value' => $stage?->growth_stage->label() ?? 'Not planted'],
            ['label' => 'Estimated Yield', 'value' => number_format($yieldTons, 1).' Ton'],
        ];
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @param  array<string, mixed>  $overlay
     * @return list<array{label: string, value: string, icon: string, tone: string}>
     */
    protected function widgets(User $user, Farm $farm, Collection $crops, array $overlay): array
    {
        $weather = $this->weatherService->getOverviewData($user);
        $temp = $weather['current']['temperature'] ?? '28°C';
        $condition = $weather['current']['condition'] ?? 'Sunny';
        $moisture = (int) ($overlay['moisture'] ?? $this->baseMoisture($farm));
        $pest = $overlay['pest_risk'] ?? $this->pestFromCrops($crops);

        $soilLabel = match (true) {
            filled($farm->soil_type) && Str::contains(Str::lower((string) $farm->soil_type), ['loam', 'fertile']) => 'Good',
            filled($farm->soil_type) => (string) $farm->soil_type,
            default => 'Fair',
        };

        $waterLabel = match (true) {
            $moisture >= 70 => 'Optimal',
            $moisture >= 45 => 'Adequate',
            default => 'Low',
        };

        return [
            ['label' => 'Soil Health', 'value' => $soilLabel, 'icon' => 'soil', 'tone' => 'green'],
            ['label' => 'Water Level', 'value' => $waterLabel, 'icon' => 'water', 'tone' => 'blue'],
            ['label' => 'Pest Risk', 'value' => $pest, 'icon' => 'pest', 'tone' => $pest === 'Low' ? 'lime' : 'amber'],
            ['label' => 'Weather', 'value' => trim($temp.' '.$condition), 'icon' => 'sun', 'tone' => 'amber'],
        ];
    }

    /**
     * @param  list<string>  $enterprises
     * @param  Collection<int, Crop>  $crops
     * @return list<array{name: string, color: string, opacity: float, coords: list<list<float>>, health?: string}>
     */
    protected function plots(Farm $farm, array $enterprises, Collection $crops): array
    {
        $lat = (float) ($farm->latitude ?: 7.3775);
        $lng = (float) ($farm->longitude ?: 3.9470);
        $colors = ['#22C55E', '#10853F', '#E6A817', '#C4782B', '#4ADE80', '#0EA5E9', '#A3E635'];
        $step = 0.0045;
        $plots = [];

        if ($enterprises === []) {
            $enterprises = ['General plot'];
        }

        foreach (array_values($enterprises) as $index => $name) {
            $row = intdiv($index, 2);
            $col = $index % 2;
            $baseLat = $lat + ($row * $step);
            $baseLng = $lng + ($col * $step);
            $crop = $crops->first(fn (Crop $item) => Str::lower($item->name) === Str::lower($name));

            $plots[] = [
                'name' => 'Plot '.chr(65 + $index).' — '.$name,
                'color' => $colors[$index % count($colors)],
                'opacity' => 0.42,
                'health' => $crop?->health_status->label() ?? 'Watch',
                'coords' => [
                    [$baseLat + $step, $baseLng],
                    [$baseLat + $step, $baseLng + $step],
                    [$baseLat, $baseLng + $step],
                    [$baseLat, $baseLng],
                ],
            ];
        }

        return $plots;
    }

    /**
     * @return array{lat: float, lng: float, zoom: float}
     */
    protected function mapCenter(Farm $farm): array
    {
        return [
            'lat' => (float) ($farm->latitude ?: 7.3775),
            'lng' => (float) ($farm->longitude ?: 3.9470),
            'zoom' => 15.0,
        ];
    }

    /**
     * @param  Collection<int, Crop>  $crops
     * @return list<string>
     */
    protected function enterpriseList(Farm $farm, Collection $crops): array
    {
        $fromJson = collect($farm->crops ?? [])->filter()->map(fn ($item) => (string) $item);
        $fromCrops = $crops->pluck('name')->filter();

        return $fromJson->merge($fromCrops)->unique()->take(6)->values()->all();
    }

    /**
     * @param  Collection<int, Crop>  $crops
     */
    protected function averageHealthPercent(Collection $crops): int
    {
        if ($crops->isEmpty()) {
            return 72;
        }

        $scores = $crops->map(fn (Crop $crop) => match ($crop->health_status) {
            CropHealthStatus::Good => 90,
            CropHealthStatus::Fair => 74,
            CropHealthStatus::Poor => 52,
            CropHealthStatus::Critical => 34,
            default => 70,
        });

        return (int) round($scores->avg());
    }

    /**
     * @param  Collection<int, Crop>  $crops
     */
    protected function estimateYieldTons(Farm $farm, Collection $crops): float
    {
        $hectares = (float) ($farm->size_hectares ?: max(1, $crops->count()));
        $factor = 1.0;
        foreach ($crops as $crop) {
            $name = Str::lower($crop->name);
            $factor = match (true) {
                Str::contains($name, ['maize', 'corn']) => 1.1,
                Str::contains($name, ['rice']) => 1.0,
                Str::contains($name, ['cassava']) => 2.2,
                Str::contains($name, ['broiler', 'layer', 'poultry']) => 0.4,
                Str::contains($name, ['fish', 'catfish', 'tilapia']) => 0.6,
                default => 0.9,
            };
            break;
        }

        $healthFactor = $this->averageHealthPercent($crops) / 100;

        return round(max(0.5, $hectares * $factor * $healthFactor), 1);
    }

    /**
     * @param  Collection<int, Crop>  $crops
     */
    protected function pestFromCrops(Collection $crops): string
    {
        if ($crops->contains(fn (Crop $crop) => $crop->health_status === CropHealthStatus::Critical)) {
            return 'High';
        }
        if ($crops->contains(fn (Crop $crop) => $crop->health_status === CropHealthStatus::Poor)) {
            return 'Moderate';
        }

        return 'Low';
    }

    protected function baseMoisture(Farm $farm): int
    {
        $seed = crc32((string) $farm->id.'|moisture');

        return 48 + ($seed % 28);
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
        return 'digital_twin_overlay_'.$farmId;
    }
}
