<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Farm;
use App\Models\User;
use App\Models\WeatherAlert;
use App\Models\WeatherSnapshot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live weather intelligence: location forecasts, alerts, and farm advisories.
 */
class WeatherIntelligenceService
{
    /**
     * Preset Nigerian observation points for rainfall map + location picker.
     *
     * @var array<string, array{label: string, lat: float, lng: float, state: string}>
     */
    protected const LOCATIONS = [
        'ibadan' => ['label' => 'Ibadan, Oyo State', 'lat' => 7.3775, 'lng' => 3.9470, 'state' => 'Oyo'],
        'lagos' => ['label' => 'Lagos, Lagos State', 'lat' => 6.5244, 'lng' => 3.3792, 'state' => 'Lagos'],
        'abuja' => ['label' => 'Abuja, FCT', 'lat' => 9.0765, 'lng' => 7.3986, 'state' => 'FCT'],
        'kano' => ['label' => 'Kano, Kano State', 'lat' => 12.0022, 'lng' => 8.5920, 'state' => 'Kano'],
        'port-harcourt' => ['label' => 'Port Harcourt, Rivers', 'lat' => 4.8156, 'lng' => 7.0498, 'state' => 'Rivers'],
        'kaduna' => ['label' => 'Kaduna, Kaduna State', 'lat' => 10.5105, 'lng' => 7.4165, 'state' => 'Kaduna'],
        'maiduguri' => ['label' => 'Maiduguri, Borno State', 'lat' => 11.8333, 'lng' => 13.1500, 'state' => 'Borno'],
        'benin' => ['label' => 'Benin City, Edo State', 'lat' => 6.3350, 'lng' => 5.6037, 'state' => 'Edo'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(User $user, ?string $locationKey = null): array
    {
        $location = $this->resolveLocation($user, $locationKey);
        $snapshot = $this->latestSnapshot($user, $location['key']);

        if (! $snapshot || $snapshot->observed_at->lt(now()->subHours(6))) {
            $snapshot = $this->refresh($user, $location['key']);
        }

        $alerts = WeatherAlert::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest('id')
            ->limit(8)
            ->get();

        $history = WeatherSnapshot::query()
            ->where('user_id', $user->id)
            ->where('location_key', $location['key'])
            ->latest('observed_at')
            ->limit(8)
            ->get()
            ->map(fn (WeatherSnapshot $row) => [
                'when' => $row->observed_at?->format('d M, Y g:i A') ?? '',
                'temp' => $row->temperature_c.'°C',
                'condition' => $row->condition,
                'rain' => number_format((float) $row->rainfall_mm, 1).' mm',
                'source' => $row->source,
            ])
            ->all();

        $locationOptions = $this->locationOptions($user);

        return [
            'location' => [
                'key' => $location['key'],
                'label' => $snapshot->location_label,
                'updated_at' => $snapshot->observed_at?->format('g:i A') ?? now()->format('g:i A'),
            ],
            'location_options' => $locationOptions,
            'current' => [
                'date_label' => 'Today, '.$snapshot->observed_at->format('d M Y'),
                'temperature' => $snapshot->temperature_c.'°C',
                'condition' => $snapshot->condition,
                'icon' => $snapshot->icon,
                'metrics' => [
                    ['label' => 'Humidity', 'value' => $snapshot->humidity_pct.'%', 'icon' => 'humidity'],
                    ['label' => 'Rainfall', 'value' => number_format((float) $snapshot->rainfall_mm, 1).' mm', 'icon' => 'rainfall'],
                    ['label' => 'Wind', 'value' => $snapshot->wind_kmh.' km/h', 'icon' => 'wind'],
                ],
            ],
            'forecast' => collect($snapshot->forecast ?? [])->map(fn (array $day) => [
                'day' => $day['day'] ?? '',
                'icon' => $day['icon'] ?? 'partly_cloudy',
                'temp' => ($day['temp'] ?? 0).'°C',
                'rain' => number_format((float) ($day['rain'] ?? 0), 1).' mm',
            ])->all(),
            'rainfall_zones' => $snapshot->rainfall_zones ?? [],
            'alerts' => $alerts->map(fn (WeatherAlert $alert) => [
                'id' => $alert->id,
                'title' => $alert->title,
                'detail' => $alert->detail,
                'icon' => $alert->icon,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'can_acknowledge' => $alert->status === 'open',
                'can_dismiss' => in_array($alert->status, ['open', 'acknowledged'], true),
                'acknowledge_url' => route('weather.alerts.acknowledge', $alert),
                'dismiss_url' => route('weather.alerts.dismiss', $alert),
            ])->all(),
            'ai_recommendation' => [
                'message' => $snapshot->recommendation,
                'action_label' => 'View Full Forecast',
            ],
            'history' => $history,
            'source' => $snapshot->source,
            'actions' => [
                'refresh_url' => route('weather.refresh'),
                'export_url' => route('weather.export', ['location' => $location['key']]),
                'switch_url' => route('weather.intelligence'),
            ],
            'notifications_count' => max(2, $alerts->where('status', 'open')->count() + 1),
        ];
    }

    /**
     * Recalculate weather for a location and sync alerts.
     */
    public function refresh(User $user, ?string $locationKey = null): WeatherSnapshot
    {
        $location = $this->resolveLocation($user, $locationKey);
        $computed = $this->fetchOrModelWeather($location, $user);

        return DB::transaction(function () use ($user, $location, $computed): WeatherSnapshot {
            $snapshot = WeatherSnapshot::query()->create([
                'user_id' => $user->id,
                'farm_id' => $location['farm_id'],
                'location_key' => $location['key'],
                'location_label' => $location['label'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'temperature_c' => $computed['temperature_c'],
                'condition' => $computed['condition'],
                'icon' => $computed['icon'],
                'humidity_pct' => $computed['humidity_pct'],
                'rainfall_mm' => $computed['rainfall_mm'],
                'wind_kmh' => $computed['wind_kmh'],
                'forecast' => $computed['forecast'],
                'rainfall_zones' => $computed['rainfall_zones'],
                'recommendation' => $computed['recommendation'],
                'source' => $computed['source'],
                'observed_at' => now(),
                'meta' => [
                    'crops' => $computed['crops'] ?? [],
                ],
            ]);

            $this->syncAlerts($user, $snapshot, $computed['alert_rows']);

            return $snapshot;
        });
    }

    public function acknowledgeAlert(User $user, WeatherAlert $alert): WeatherAlert
    {
        $this->assertOwnedAlert($user, $alert);

        if ($alert->status === 'dismissed') {
            throw new BusinessLogicException('Dismissed alerts cannot be acknowledged.');
        }

        $alert->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ])->save();

        return $alert->fresh();
    }

    public function dismissAlert(User $user, WeatherAlert $alert): WeatherAlert
    {
        $this->assertOwnedAlert($user, $alert);

        $alert->forceFill([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ])->save();

        return $alert->fresh();
    }

    public function exportReport(User $user, ?string $locationKey = null): StreamedResponse
    {
        $data = $this->getOverviewData($user, $locationKey);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Weather Intelligence Report', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Location', $data['location']['label']]);
            fputcsv($out, ['Updated', $data['location']['updated_at']]);
            fputcsv($out, ['Temperature', $data['current']['temperature']]);
            fputcsv($out, ['Condition', $data['current']['condition']]);
            fputcsv($out, ['Recommendation', $data['ai_recommendation']['message']]);
            fputcsv($out, []);
            fputcsv($out, ['Forecast Day', 'Temp', 'Rain', 'Icon']);
            foreach ($data['forecast'] as $day) {
                fputcsv($out, [$day['day'], $day['temp'], $day['rain'], $day['icon']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Alerts']);
            fputcsv($out, ['Title', 'Detail', 'Severity', 'Status']);
            foreach ($data['alerts'] as $alert) {
                fputcsv($out, [$alert['title'], $alert['detail'], $alert['severity'], $alert['status']]);
            }
            fclose($out);
        }, 'weather-intelligence-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array{key: string, label: string, lat: float, lng: float, farm_id: int|null, state: string}
     */
    protected function resolveLocation(User $user, ?string $locationKey): array
    {
        $options = $this->locationOptions($user)->keyBy('key');
        $key = $locationKey && $options->has($locationKey) ? $locationKey : 'ibadan';

        if (! $options->has($key)) {
            $key = $options->keys()->first() ?: 'ibadan';
        }

        $option = $options->get($key) ?? [
            'key' => 'ibadan',
            'label' => self::LOCATIONS['ibadan']['label'],
            'lat' => self::LOCATIONS['ibadan']['lat'],
            'lng' => self::LOCATIONS['ibadan']['lng'],
            'farm_id' => null,
            'state' => self::LOCATIONS['ibadan']['state'],
        ];

        return [
            'key' => $option['key'],
            'label' => $option['label'],
            'lat' => (float) $option['lat'],
            'lng' => (float) $option['lng'],
            'farm_id' => $option['farm_id'] ?? null,
            'state' => $option['state'] ?? '',
        ];
    }

    /**
     * @return Collection<int, array{key: string, label: string, lat: float, lng: float, farm_id: int|null, state: string}>
     */
    protected function locationOptions(User $user): Collection
    {
        $presets = collect(self::LOCATIONS)->map(fn (array $row, string $key) => [
            'key' => $key,
            'label' => $row['label'],
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'farm_id' => null,
            'state' => $row['state'],
        ])->values();

        $farms = Farm::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview])
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function (Farm $farm) {
                $lat = $farm->latitude !== null ? (float) $farm->latitude : $this->stateLat($farm->state);
                $lng = $farm->longitude !== null ? (float) $farm->longitude : $this->stateLng($farm->state);

                return [
                    'key' => 'farm-'.$farm->id,
                    'label' => ($farm->name ?: 'Farm #'.$farm->id).($farm->state ? ', '.$farm->state : ''),
                    'lat' => $lat,
                    'lng' => $lng,
                    'farm_id' => $farm->id,
                    'state' => (string) ($farm->state ?? ''),
                ];
            });

        return $presets->concat($farms)->values();
    }

    /**
     * @param  array{key: string, label: string, lat: float, lng: float, farm_id: int|null, state: string}  $location
     * @return array<string, mixed>
     */
    protected function fetchOrModelWeather(array $location, User $user): array
    {
        $remote = $this->tryOpenMeteo($location['lat'], $location['lng']);
        $modeled = $this->modelWeather($location, $user);

        if ($remote === null) {
            return $modeled;
        }

        return [
            'temperature_c' => $remote['temperature_c'],
            'condition' => $remote['condition'],
            'icon' => $remote['icon'],
            'humidity_pct' => $remote['humidity_pct'],
            'rainfall_mm' => $remote['rainfall_mm'],
            'wind_kmh' => $remote['wind_kmh'],
            'forecast' => $remote['forecast'],
            'rainfall_zones' => $modeled['rainfall_zones'],
            'recommendation' => $this->buildRecommendation(
                $remote['forecast'],
                $modeled['crops'],
                $remote['temperature_c'],
                $location['label']
            ),
            'alert_rows' => $this->buildAlertRows($remote['forecast'], $location['label'], $remote['temperature_c']),
            'source' => 'open_meteo',
            'crops' => $modeled['crops'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function tryOpenMeteo(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'current' => 'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
                    'daily' => 'weather_code,temperature_2m_max,precipitation_sum',
                    'forecast_days' => 6,
                    'timezone' => 'Africa/Lagos',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $current = $json['current'] ?? null;
            $daily = $json['daily'] ?? null;

            if (! is_array($current) || ! is_array($daily)) {
                return null;
            }

            $code = (int) ($current['weather_code'] ?? 2);
            [$condition, $icon] = $this->mapWeatherCode($code);

            $forecast = [];
            $times = $daily['time'] ?? [];
            for ($i = 1; $i <= 5; $i++) {
                if (! isset($times[$i])) {
                    break;
                }
                $dayCode = (int) ($daily['weather_code'][$i] ?? 2);
                [, $dayIcon] = $this->mapWeatherCode($dayCode);
                $forecast[] = [
                    'day' => Carbon::parse($times[$i])->timezone('Africa/Lagos')->format('D d M'),
                    'icon' => $dayIcon,
                    'temp' => (int) round((float) ($daily['temperature_2m_max'][$i] ?? 28)),
                    'rain' => round((float) ($daily['precipitation_sum'][$i] ?? 0), 1),
                ];
            }

            return [
                'temperature_c' => (int) round((float) ($current['temperature_2m'] ?? 28)),
                'condition' => $condition,
                'icon' => $icon,
                'humidity_pct' => (int) round((float) ($current['relative_humidity_2m'] ?? 65)),
                'rainfall_mm' => round((float) ($current['precipitation'] ?? 0), 1),
                'wind_kmh' => (int) round((float) ($current['wind_speed_10m'] ?? 10)),
                'forecast' => $forecast,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Deterministic climate model for offline / test environments.
     *
     * @param  array{key: string, label: string, lat: float, lng: float, farm_id: int|null, state: string}  $location
     * @return array<string, mixed>
     */
    protected function modelWeather(array $location, User $user): array
    {
        $now = now()->timezone('Africa/Lagos');
        $doy = (int) $now->dayOfYear;
        $lat = $location['lat'];
        $seed = crc32($location['key'].'|'.$now->toDateString());

        // Wet season bias for southern latitudes / mid-year.
        $wetFactor = (sin(($doy - 80) / 365 * 2 * M_PI) + 1) / 2;
        if ($lat < 8) {
            $wetFactor = min(1, $wetFactor + 0.15);
        } elseif ($lat > 11) {
            $wetFactor = max(0, $wetFactor - 0.2);
        }

        $baseTemp = 33 - ($lat * 0.35) + (sin(($doy - 15) / 365 * 2 * M_PI) * 3);
        $tempJitter = (($seed % 7) - 3);
        $temperature = (int) max(22, min(40, round($baseTemp + $tempJitter)));

        $rainfall = round(max(0, ($wetFactor * 18) + (($seed % 50) / 10) - 2), 1);
        $humidity = (int) max(35, min(95, round(45 + ($wetFactor * 40) + (($seed % 9) - 4))));
        $wind = (int) max(4, min(28, 8 + ($seed % 12)));

        [$condition, $icon] = $this->conditionFromRainTemp($rainfall, $temperature);

        $forecast = [];
        for ($i = 1; $i <= 5; $i++) {
            $day = $now->copy()->addDays($i);
            $daySeed = crc32($location['key'].'|'.$day->toDateString());
            $dayWet = (sin(($day->dayOfYear - 80) / 365 * 2 * M_PI) + 1) / 2;
            if ($lat < 8) {
                $dayWet = min(1, $dayWet + 0.15);
            }
            $dayRain = round(max(0, ($dayWet * 16) + (($daySeed % 60) / 10) - 1.5), 1);
            $dayTemp = (int) max(22, min(40, round($baseTemp + (($daySeed % 7) - 3))));
            [, $dayIcon] = $this->conditionFromRainTemp($dayRain, $dayTemp);
            $forecast[] = [
                'day' => $day->format('D d M'),
                'icon' => $dayIcon,
                'temp' => $dayTemp,
                'rain' => $dayRain,
            ];
        }

        $crops = $this->cropsForUser($user, $location['farm_id']);
        $zones = $this->rainfallZonesForDay($now);

        return [
            'temperature_c' => $temperature,
            'condition' => $condition,
            'icon' => $icon,
            'humidity_pct' => $humidity,
            'rainfall_mm' => $rainfall,
            'wind_kmh' => $wind,
            'forecast' => $forecast,
            'rainfall_zones' => $zones,
            'recommendation' => $this->buildRecommendation($forecast, $crops, $temperature, $location['label']),
            'alert_rows' => $this->buildAlertRows($forecast, $location['label'], $temperature),
            'source' => 'model',
            'crops' => $crops,
        ];
    }

    /**
     * @return list<array{lat: float, lng: float, mm: int, label: string}>
     */
    protected function rainfallZonesForDay(CarbonInterface $day): array
    {
        return collect(self::LOCATIONS)->map(function (array $row, string $key) use ($day) {
            $seed = crc32($key.'|'.$day->toDateString());
            $wet = (sin(($day->dayOfYear - 80) / 365 * 2 * M_PI) + 1) / 2;
            if ($row['lat'] < 8) {
                $wet = min(1, $wet + 0.2);
            } elseif ($row['lat'] > 11) {
                $wet = max(0, $wet - 0.25);
            }
            $mm = (int) max(1, min(70, round(($wet * 45) + ($seed % 20))));

            return [
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'mm' => $mm,
                'label' => explode(',', $row['label'])[0],
            ];
        })->values()->all();
    }

    /**
     * @param  list<array{day: string, icon: string, temp: int, rain: float}>  $forecast
     * @param  list<string>  $crops
     */
    protected function buildRecommendation(array $forecast, array $crops, int $temperature, string $locationLabel): string
    {
        $crop = $crops[0] ?? 'maize';
        $totalRain = collect($forecast)->sum(fn (array $d) => (float) ($d['rain'] ?? 0));
        $heavyDays = collect($forecast)->filter(fn (array $d) => (float) ($d['rain'] ?? 0) >= 8)->count();
        $hotDays = collect($forecast)->filter(fn (array $d) => (int) ($d['temp'] ?? 0) >= 34)->count();

        if ($heavyDays >= 2) {
            return 'Heavy rain expected near '.$locationLabel.'. Delay fertilizer and protect '.$crop.' seedlings.';
        }

        if ($hotDays >= 2 || $temperature >= 35) {
            return 'High heat stress risk for '.$crop.'. Irrigate early morning and mulch beds.';
        }

        if ($totalRain >= 10 && $totalRain <= 35) {
            return 'Good weather for planting '.$crop.' in the next 5 days.';
        }

        if ($totalRain < 5) {
            return 'Dry stretch ahead near '.$locationLabel.'. Schedule irrigation for '.$crop.' plots.';
        }

        return 'Monitor conditions around '.$locationLabel.' and stage '.$crop.' field work between showers.';
    }

    /**
     * @param  list<array{day: string, icon: string, temp: int, rain: float}>  $forecast
     * @return list<array{alert_key: string, title: string, detail: string, icon: string, severity: string, starts_at: CarbonInterface, ends_at: CarbonInterface}>
     */
    protected function buildAlertRows(array $forecast, string $locationLabel, int $temperature): array
    {
        $rows = [];
        $rainWindows = [];
        foreach ($forecast as $index => $day) {
            if ((float) ($day['rain'] ?? 0) >= 8) {
                $rainWindows[] = $index;
            }
        }

        if ($rainWindows !== []) {
            $start = now()->addDays($rainWindows[0] + 1);
            $end = now()->addDays($rainWindows[array_key_last($rainWindows)] + 1);
            $rows[] = [
                'alert_key' => 'heavy-rain-'.$start->toDateString(),
                'title' => 'Heavy rainfall alert',
                'detail' => $start->format('d M Y').' - '.$end->format('d M Y').' | '.$locationLabel,
                'icon' => 'storm',
                'severity' => 'high',
                'starts_at' => $start->copy()->startOfDay(),
                'ends_at' => $end->copy()->endOfDay(),
            ];
        }

        $hotIndexes = [];
        foreach ($forecast as $index => $day) {
            if ((int) ($day['temp'] ?? 0) >= 34) {
                $hotIndexes[] = $index;
            }
        }

        if ($hotIndexes !== [] || $temperature >= 35) {
            $hotStartIndex = $hotIndexes[0] ?? 0;
            $hotEndIndex = $hotIndexes !== [] ? $hotIndexes[array_key_last($hotIndexes)] : 2;
            $start = now()->addDays($hotStartIndex + 1);
            $end = now()->addDays($hotEndIndex + 1);
            $rows[] = [
                'alert_key' => 'high-temp-'.$start->toDateString(),
                'title' => 'High temperature alert',
                'detail' => $start->format('d M Y').' - '.$end->format('d M Y').' | '.$locationLabel,
                'icon' => 'heat',
                'severity' => 'medium',
                'starts_at' => $start->copy()->startOfDay(),
                'ends_at' => $end->copy()->endOfDay(),
            ];
        }

        if ($rows === []) {
            $rows[] = [
                'alert_key' => 'stable-'.now()->toDateString(),
                'title' => 'Stable conditions',
                'detail' => 'No severe weather flags for '.$locationLabel.' over the next 5 days.',
                'icon' => 'storm',
                'severity' => 'low',
                'starts_at' => now(),
                'ends_at' => now()->addDays(5),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{alert_key: string, title: string, detail: string, icon: string, severity: string, starts_at: CarbonInterface, ends_at: CarbonInterface}>  $rows
     */
    protected function syncAlerts(User $user, WeatherSnapshot $snapshot, array $rows): void
    {
        $activeKeys = [];

        foreach ($rows as $row) {
            $key = $row['alert_key'];
            $activeKeys[] = $key;

            $dismissed = WeatherAlert::query()
                ->where('user_id', $user->id)
                ->where('alert_key', $key)
                ->where('status', 'dismissed')
                ->where('dismissed_at', '>=', now()->subDays(3))
                ->exists();

            if ($dismissed) {
                continue;
            }

            $existing = WeatherAlert::query()
                ->where('user_id', $user->id)
                ->where('alert_key', $key)
                ->whereIn('status', ['open', 'acknowledged'])
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'weather_snapshot_id' => $snapshot->id,
                    'title' => $row['title'],
                    'detail' => $row['detail'],
                    'icon' => $row['icon'],
                    'severity' => $row['severity'],
                    'starts_at' => $row['starts_at'],
                    'ends_at' => $row['ends_at'],
                ])->save();

                continue;
            }

            WeatherAlert::query()->create([
                'user_id' => $user->id,
                'weather_snapshot_id' => $snapshot->id,
                'alert_key' => $key,
                'title' => $row['title'],
                'detail' => $row['detail'],
                'icon' => $row['icon'],
                'severity' => $row['severity'],
                'status' => 'open',
                'starts_at' => $row['starts_at'],
                'ends_at' => $row['ends_at'],
            ]);
        }

        WeatherAlert::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged'])
            ->whereNotIn('alert_key', $activeKeys)
            ->where('alert_key', 'like', 'stable-%')
            ->update([
                'status' => 'dismissed',
                'dismissed_at' => now(),
            ]);
    }

    /**
     * @return list<string>
     */
    protected function cropsForUser(User $user, ?int $farmId): array
    {
        $query = Farm::query()->where('user_id', $user->id);
        if ($farmId) {
            $query->whereKey($farmId);
        }

        $farm = $query->orderByDesc('id')->first();
        $crops = collect($farm?->crops ?? [])
            ->map(fn ($crop) => is_array($crop) ? ($crop['name'] ?? null) : $crop)
            ->filter()
            ->map(fn ($crop) => strtolower((string) $crop))
            ->values()
            ->all();

        return $crops !== [] ? $crops : ['maize'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function conditionFromRainTemp(float $rain, int $temp): array
    {
        if ($rain >= 8) {
            return ['Rain', 'rain'];
        }

        if ($rain >= 1.5) {
            return ['Partly Cloudy', 'partly_cloudy'];
        }

        if ($temp >= 34) {
            return ['Sunny', 'sunny'];
        }

        return ['Partly Cloudy', 'partly_cloudy'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function mapWeatherCode(int $code): array
    {
        return match (true) {
            $code === 0 => ['Clear', 'sunny'],
            $code <= 2 => ['Partly Cloudy', 'partly_cloudy'],
            $code <= 3 => ['Overcast', 'partly_cloudy'],
            $code >= 51 && $code <= 67, $code >= 80 && $code <= 82 => ['Rain', 'rain'],
            $code >= 95 => ['Storm', 'rain'],
            default => ['Partly Cloudy', 'partly_cloudy'],
        };
    }

    protected function latestSnapshot(User $user, string $locationKey): ?WeatherSnapshot
    {
        return WeatherSnapshot::query()
            ->where('user_id', $user->id)
            ->where('location_key', $locationKey)
            ->latest('observed_at')
            ->latest('id')
            ->first();
    }

    protected function assertOwnedAlert(User $user, WeatherAlert $alert): void
    {
        if ($alert->user_id !== $user->id) {
            throw new BusinessLogicException('This weather alert belongs to another account.', statusCode: 403);
        }
    }

    protected function stateLat(?string $state): float
    {
        $state = strtolower((string) $state);

        return match (true) {
            str_contains($state, 'lagos') => 6.5244,
            str_contains($state, 'kano') => 12.0022,
            str_contains($state, 'rivers'), str_contains($state, 'port') => 4.8156,
            str_contains($state, 'kaduna') => 10.5105,
            str_contains($state, 'borno') => 11.8333,
            str_contains($state, 'edo') => 6.3350,
            str_contains($state, 'fct'), str_contains($state, 'abuja') => 9.0765,
            default => 7.3775,
        };
    }

    protected function stateLng(?string $state): float
    {
        $state = strtolower((string) $state);

        return match (true) {
            str_contains($state, 'lagos') => 3.3792,
            str_contains($state, 'kano') => 8.5920,
            str_contains($state, 'rivers'), str_contains($state, 'port') => 7.0498,
            str_contains($state, 'kaduna') => 7.4165,
            str_contains($state, 'borno') => 13.1500,
            str_contains($state, 'edo') => 5.6037,
            str_contains($state, 'fct'), str_contains($state, 'abuja') => 7.3986,
            default => 3.9470,
        };
    }
}
