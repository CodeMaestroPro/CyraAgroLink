<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Financial Institution Dashboard.
 */
class FinancialInstitutionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_financial_institution_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('financial.dashboard'));

        $response->assertOk();
        $response->assertSee('Financial Institution Dashboard');
        $response->assertDontSee('22. FINANCIAL INSTITUTION DASHBOARD');
        $response->assertDontSee('22. Financial Institution Dashboard');
        $response->assertSee('Loan Portfolio Overview', false);
        $response->assertSee('Total Loans', false);
        $response->assertSee('Active Loans', false);
        $response->assertSee('Total Borrowers', false);
        $response->assertSee('NPL Ratio', false);
        $response->assertSee('Loan Portfolio by Sector', false);
        $response->assertSee('Recent Loan Applications', false);
        $response->assertSee('Green Valley Farms', false);
        $response->assertSee('Export portfolio', false);
        $response->assertSee('Poultry', false);
        $response->assertSee('Aquaculture', false);

        $this->assertGreaterThanOrEqual(5, LoanApplication::query()->count());
    }

    public function test_user_can_apply_approve_repay_and_export(): void
    {
        $user = User::factory()->create();
        $wallet = app(DigitalWalletService::class);

        $this->actingAs($user)->get(route('financial.dashboard'))->assertOk();

        $this->actingAs($user)
            ->post(route('financial.applications.store'), [
                'borrower' => $user->name,
                'sector' => 'Crop Farming',
                'amount' => 1_500_000,
                'purpose' => 'Seasonal maize inputs',
            ])
            ->assertRedirect(route('financial.dashboard', ['tab' => 'loan-applications']).'#recent-applications-heading');

        $application = LoanApplication::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(ApplicationStatus::Pending, $application->status);

        $before = $wallet->getBalance($user);

        $this->actingAs($user)
            ->post(route('financial.applications.approve', $application))
            ->assertRedirect(route('financial.dashboard', ['tab' => 'loan-applications']));

        $application->refresh();
        $this->assertSame(ApplicationStatus::Approved, $application->status);
        $this->assertNotNull($application->disbursed_at);
        $this->assertSame($before + 1_500_000, $wallet->getBalance($user));

        $this->actingAs($user)
            ->post(route('financial.applications.repay', $application), [
                'amount' => 500_000,
                'note' => 'First installment',
            ])
            ->assertRedirect(route('financial.dashboard', ['tab' => 'repayments']).'#recent-applications-heading');

        $this->assertSame(500_000, $application->fresh()->amount_repaid);
        $this->assertSame(1, LoanRepayment::query()->where('loan_application_id', $application->id)->count());
        $this->assertSame($before + 1_000_000, $wallet->getBalance($user));

        $pending = LoanApplication::query()
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
            ->whereNull('user_id')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('financial.applications.reject', $pending))
            ->assertRedirect(route('financial.dashboard', ['tab' => 'loan-applications']));

        $this->assertSame(ApplicationStatus::Rejected, $pending->fresh()->status);

        $export = $this->actingAs($user)->get(route('financial.export'));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Financial Institution Portfolio', $csv);
        $this->assertStringContainsString('Total Loans', $csv);
        $this->assertStringContainsString($user->name, $csv);
    }

    public function test_guest_cannot_view_financial_institution_dashboard(): void
    {
        $this->get(route('financial.dashboard'))->assertRedirect(route('login'));
    }
}
