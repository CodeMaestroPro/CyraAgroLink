<?php

declare(strict_types=1);

namespace App\Services\Insurance;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Farm;
use App\Models\InsuranceClaim;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Live farm insurance: plans catalog, wallet-paid policies, and claim workflow.
 */
class FarmInsuranceService
{
    /**
     * @var list<string>
     */
    public const CLAIM_FLOW = ['submitted', 'under_review', 'approved', 'paid'];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlatformData(User $user): array
    {
        $this->ensurePlans();
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty() && \App\Support\DemoSeeding::allowed()) {
            $farms = collect([$this->ensureSeedFarm($user)]);
        }

        $this->expireStalePolicies($user);

        $policies = InsurancePolicy::query()
            ->where('user_id', $user->id)
            ->with(['plan', 'farm'])
            ->latest('id')
            ->limit(20)
            ->get();

        $claims = InsuranceClaim::query()
            ->where('user_id', $user->id)
            ->with(['policy.plan', 'farm'])
            ->latest('id')
            ->limit(20)
            ->get();

        $activePolicies = $policies->filter(fn (InsurancePolicy $p) => $p->isActive());
        $walletBalance = $this->walletService->getBalance($user);

        $claimsThisMonth = InsuranceClaim::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $paidClaimsTotal = (int) InsuranceClaim::query()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount_paid_ngn');

        $plans = InsurancePlan::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return [
            'kpis' => [
                [
                    'label' => 'Active Policies',
                    'value' => (string) $activePolicies->count(),
                ],
                [
                    'label' => 'Total Coverage',
                    'value' => '₦'.number_format((int) $activePolicies->sum('coverage_ngn')),
                ],
                [
                    'label' => 'Claims This Month',
                    'value' => (string) $claimsThisMonth,
                ],
                [
                    'label' => 'Paid Claims',
                    'value' => '₦'.number_format($paidClaimsTotal),
                ],
            ],
            'plans' => $plans->map(fn (InsurancePlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'category' => ucfirst($plan->category),
                'description' => $plan->description,
                'premium' => '₦'.number_format($plan->premium_ngn),
                'coverage' => '₦'.number_format($plan->coverage_ngn),
                'duration' => $plan->duration_days.' days',
                'enterprises' => collect($plan->enterprise_tags ?? [])->implode(', '),
            ])->all(),
            'policies' => $policies->map(fn (InsurancePolicy $policy) => [
                'id' => $policy->id,
                'reference' => $policy->reference,
                'name' => $policy->plan?->name ?? 'Insurance Policy',
                'farm' => $policy->farm?->name ?? 'Farm',
                'status' => $policy->isActive() ? 'Active' : ucfirst($policy->status),
                'expires' => $policy->expires_at?->format('j M, Y') ?? '—',
                'coverage' => '₦'.number_format($policy->coverage_ngn),
                'can_claim' => $policy->isActive(),
            ])->all(),
            'claims' => $claims->map(fn (InsuranceClaim $claim) => $this->presentClaim($claim))->all(),
            'farms' => $farms->map(fn (Farm $farm) => [
                'id' => $farm->id,
                'name' => $farm->name ?: 'Unnamed farm',
                'enterprises' => collect($farm->crops ?? [])->implode(', '),
            ])->all(),
            'claimable_policies' => $activePolicies->map(fn (InsurancePolicy $policy) => [
                'id' => $policy->id,
                'label' => ($policy->plan?->name ?? 'Policy').' · '.$policy->reference,
                'max_amount' => $policy->coverage_ngn,
            ])->values()->all(),
            'wallet_balance' => $walletBalance,
            'actions' => [
                'purchase_url' => route('insurance.policies.store'),
                'claim_url' => route('insurance.claims.store'),
                'wallet_url' => route('wallet.index'),
            ],
            'notifications_count' => max(
                2,
                $activePolicies->count() + $claims->whereIn('status', ['submitted', 'under_review', 'approved'])->count()
            ),
        ];
    }

    /**
     * Purchase a plan for one of the user's farms via wallet premium.
     */
    public function purchasePolicy(User $user, int $planId, int $farmId): InsurancePolicy
    {
        $plan = InsurancePlan::query()->whereKey($planId)->where('is_active', true)->first();
        if (! $plan) {
            throw new BusinessLogicException('Selected insurance plan is unavailable.');
        }

        $farm = $this->ownedFarm($user, $farmId);

        return DB::transaction(function () use ($user, $plan, $farm): InsurancePolicy {
            $this->walletService->ensureWallet($user);

            $policy = InsurancePolicy::query()->create([
                'user_id' => $user->id,
                'farm_id' => $farm->id,
                'plan_id' => $plan->id,
                'reference' => $this->nextPolicyReference($user),
                'status' => 'active',
                'premium_ngn' => $plan->premium_ngn,
                'coverage_ngn' => $plan->coverage_ngn,
                'starts_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'covered_enterprises' => $farm->crops ?? [],
                'meta' => [
                    'plan_name' => $plan->name,
                    'plan_category' => $plan->category,
                    'farm_name' => $farm->name,
                ],
            ]);

            $this->walletService->payForInsurancePremium(
                $user,
                (int) $plan->premium_ngn,
                $policy,
                $policy->reference.' · '.$plan->name
            );

            return $policy->load(['plan', 'farm']);
        });
    }

    /**
     * File a claim against an active policy.
     *
     * @param  array{policy_id: int, title: string, description?: string|null, amount_requested_ngn: int}  $data
     */
    public function fileClaim(User $user, array $data): InsuranceClaim
    {
        $policy = InsurancePolicy::query()
            ->whereKey($data['policy_id'])
            ->where('user_id', $user->id)
            ->with('plan')
            ->first();

        if (! $policy || ! $policy->isActive()) {
            throw new BusinessLogicException('You can only claim against an active policy.');
        }

        $amount = (int) $data['amount_requested_ngn'];
        if ($amount < 1) {
            throw new BusinessLogicException('Claim amount must be at least ₦1.');
        }

        if ($amount > $policy->coverage_ngn) {
            throw new BusinessLogicException('Claim amount cannot exceed policy coverage.');
        }

        $openClaim = InsuranceClaim::query()
            ->where('policy_id', $policy->id)
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->exists();

        if ($openClaim) {
            throw new BusinessLogicException('This policy already has an open claim in progress.');
        }

        return InsuranceClaim::query()->create([
            'user_id' => $user->id,
            'policy_id' => $policy->id,
            'farm_id' => $policy->farm_id,
            'reference' => $this->nextClaimReference($user),
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'amount_requested_ngn' => $amount,
            'status' => 'submitted',
            'meta' => [
                'plan_name' => $policy->plan?->name,
            ],
        ])->load(['policy.plan', 'farm']);
    }

    /**
     * Advance a claim through review → approval → wallet payout (or reject).
     */
    public function advanceClaim(User $user, InsuranceClaim $claim, string $action = 'next'): InsuranceClaim
    {
        $this->assertOwnedClaim($user, $claim);

        return DB::transaction(function () use ($user, $claim, $action): InsuranceClaim {
            /** @var InsuranceClaim $locked */
            $locked = InsuranceClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if ($action === 'reject') {
                if (! in_array($locked->status, ['submitted', 'under_review'], true)) {
                    throw new BusinessLogicException('Only submitted or under-review claims can be rejected.');
                }

                $locked->forceFill(['status' => 'rejected'])->save();

                return $locked->refresh()->load(['policy.plan', 'farm']);
            }

            $next = match ($locked->status) {
                'submitted' => 'under_review',
                'under_review' => 'approved',
                'approved' => 'paid',
                default => null,
            };

            if ($next === null) {
                throw new BusinessLogicException('This claim cannot be advanced further.');
            }

            if ($next === 'approved') {
                $locked->forceFill([
                    'status' => 'approved',
                    'amount_paid_ngn' => $locked->amount_requested_ngn,
                ])->save();
            } elseif ($next === 'paid') {
                $payout = (int) ($locked->amount_paid_ngn ?? $locked->amount_requested_ngn);
                $this->walletService->creditInsuranceClaim(
                    $user,
                    $payout,
                    $locked,
                    $locked->reference.' · '.$locked->title
                );
                $locked->forceFill([
                    'status' => 'paid',
                    'amount_paid_ngn' => $payout,
                ])->save();
            } else {
                $locked->forceFill(['status' => $next])->save();
            }

            return $locked->refresh()->load(['policy.plan', 'farm']);
        });
    }

    protected function ensurePlans(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (InsurancePlan::query()->exists()) {
            return;
        }

        foreach ($this->seedPlans() as $row) {
            InsurancePlan::query()->create($row);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function seedPlans(): array
    {
        return [
            [
                'name' => 'Crop Multi-Peril Cover',
                'slug' => 'crop-multi-peril',
                'category' => 'crop',
                'premium_ngn' => 185_000,
                'coverage_ngn' => 2_500_000,
                'duration_days' => 365,
                'description' => 'Covers maize, cassava, rice and other field crops against fire, flood, and drought loss.',
                'enterprise_tags' => ['Maize', 'Cassava', 'Rice', 'Yam', 'Vegetables'],
                'is_active' => true,
            ],
            [
                'name' => 'Weather Index Policy',
                'slug' => 'weather-index',
                'category' => 'weather',
                'premium_ngn' => 95_000,
                'coverage_ngn' => 1_200_000,
                'duration_days' => 180,
                'description' => 'Parametric payouts for extreme rainfall or drought thresholds near your farm.',
                'enterprise_tags' => ['Maize', 'Rice', 'Cocoa', 'Oil Palm'],
                'is_active' => true,
            ],
            [
                'name' => 'Poultry Flock Cover',
                'slug' => 'poultry-flock',
                'category' => 'poultry',
                'premium_ngn' => 120_000,
                'coverage_ngn' => 1_800_000,
                'duration_days' => 365,
                'description' => 'Protects broilers, layers, and hatchery stock against disease and housing damage.',
                'enterprise_tags' => ['Broilers', 'Layers', 'Hatchery', 'Poultry Feed'],
                'is_active' => true,
            ],
            [
                'name' => 'Aquaculture Pond Cover',
                'slug' => 'aquaculture-pond',
                'category' => 'aquaculture',
                'premium_ngn' => 140_000,
                'coverage_ngn' => 2_000_000,
                'duration_days' => 365,
                'description' => 'Covers catfish and tilapia ponds against flood, contamination, and mass mortality.',
                'enterprise_tags' => ['Catfish', 'Tilapia', 'Fish Hatchery'],
                'is_active' => true,
            ],
            [
                'name' => 'Livestock Enterprise Cover',
                'slug' => 'livestock-enterprise',
                'category' => 'livestock',
                'premium_ngn' => 160_000,
                'coverage_ngn' => 2_200_000,
                'duration_days' => 365,
                'description' => 'Covers pigs, goats, cattle and similar livestock against theft and mortality events.',
                'enterprise_tags' => ['Pig', 'Goat', 'Cattle', 'Snail', 'Rabbit'],
                'is_active' => true,
            ],
            [
                'name' => 'Farm Equipment Cover',
                'slug' => 'farm-equipment',
                'category' => 'equipment',
                'premium_ngn' => 75_000,
                'coverage_ngn' => 3_500_000,
                'duration_days' => 365,
                'description' => 'Protects tractors, pumps, mills and other farm machinery against fire, theft, and accident.',
                'enterprise_tags' => ['Equipment'],
                'is_active' => true,
            ],
        ];
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
        if (! \App\Support\DemoSeeding::allowed()) {
            throw new BusinessLogicException('Register a farm before using insurance.', 'FARM_REQUIRED', 422);
        }

        return Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Valley Farm',
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Insurance demo enterprise',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '12.00',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Layers', 'Catfish'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);
    }

    protected function ownedFarm(User $user, int $farmId): Farm
    {
        $farm = Farm::query()
            ->whereKey($farmId)
            ->where('user_id', $user->id)
            ->first();

        if (! $farm) {
            throw new BusinessLogicException('Select one of your registered farms.');
        }

        return $farm;
    }

    protected function expireStalePolicies(User $user): void
    {
        InsurancePolicy::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentClaim(InsuranceClaim $claim): array
    {
        $tone = match ($claim->status) {
            'paid', 'approved' => 'approved',
            'submitted', 'under_review' => 'review',
            default => 'rejected',
        };

        $amount = match ($claim->status) {
            'paid' => '₦'.number_format((int) $claim->amount_paid_ngn),
            'approved' => '₦'.number_format((int) ($claim->amount_paid_ngn ?? $claim->amount_requested_ngn)),
            default => '₦'.number_format($claim->amount_requested_ngn),
        };

        $nextLabel = match ($claim->status) {
            'submitted' => 'Send to review',
            'under_review' => 'Approve claim',
            'approved' => 'Pay claim',
            default => null,
        };

        return [
            'id' => $claim->id,
            'reference' => $claim->reference,
            'name' => $claim->title,
            'policy' => $claim->policy?->plan?->name ?? ($claim->meta['plan_name'] ?? 'Policy'),
            'status' => Str::headline($claim->status),
            'status_tone' => $tone,
            'amount' => $amount,
            'can_advance' => in_array($claim->status, ['submitted', 'under_review', 'approved'], true),
            'can_reject' => in_array($claim->status, ['submitted', 'under_review'], true),
            'next_label' => $nextLabel,
            'advance_url' => route('insurance.claims.advance', $claim),
        ];
    }

    protected function nextPolicyReference(User $user): string
    {
        $count = InsurancePolicy::query()->where('user_id', $user->id)->count() + 1;

        return 'POL-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function nextClaimReference(User $user): string
    {
        $count = InsuranceClaim::query()->where('user_id', $user->id)->count() + 1;

        return 'CLM-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function assertOwnedClaim(User $user, InsuranceClaim $claim): void
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if ((int) $claim->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own claims.', 'CLAIM_FORBIDDEN', 403);
        }
    }
}
