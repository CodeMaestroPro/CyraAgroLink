<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\CooperativeLoan;
use App\Models\CooperativeMember;
use App\Models\CooperativeVote;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Smart Cooperative Management.
 */
class CooperativeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_cooperative_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cooperative.management'));

        $response->assertOk();
        $response->assertSee('Smart Cooperative Management');
        $response->assertDontSee('38. SMART COOPERATIVE MANAGEMENT');
        $response->assertDontSee('38. Smart Cooperative Management');
        $response->assertSee('Cooperative Overview', false);
        $response->assertSee('Total Members', false);
        $response->assertSee('Active Groups', false);
        $response->assertSee('Total Savings', false);
        $response->assertSee('Loans Issued', false);
        $response->assertSee('Recent Activities', false);
        $response->assertSee('Cooperative Founded', false);
        $response->assertSee('Upcoming Vote', false);
        $response->assertSee('Approve Purchase of New Thresher Machine', false);
        $response->assertSee('Vote Yes', false);
        $response->assertSee('Vote No', false);
        $response->assertSee('Members', false);
        $response->assertSee('Loans', false);
        $response->assertSee('Savings', false);
        $response->assertSee('Contribute', false);
        $response->assertSee('Request Loan', false);
        $response->assertSee('Equipment', false);
        $response->assertSee('Reports', false);

        $this->assertSame(1, Cooperative::query()->count());
        $this->assertSame(1, CooperativeMember::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, CooperativeVote::query()->where('status', 'open')->count());
    }

    public function test_guest_cannot_view_cooperative_management(): void
    {
        $this->get(route('cooperative.management'))->assertRedirect(route('login'));
    }

    public function test_member_can_contribute_vote_and_run_loan_cycle(): void
    {
        $user = User::factory()->create();
        app(DigitalWalletService::class)->deposit($user, 10_000_000, 'Test funding');

        $this->actingAs($user)->get(route('cooperative.management'))->assertOk();

        $this->actingAs($user)
            ->post(route('cooperative.contribute'), ['amount' => 150_000])
            ->assertRedirect(route('cooperative.management').'#savings');

        $coop = Cooperative::query()->firstOrFail();
        $this->assertSame(150_000, $coop->fresh()->savings_pool_ngn);

        $member = CooperativeMember::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(150_000, $member->savings_balance_ngn);

        $vote = CooperativeVote::query()->where('cooperative_id', $coop->id)->where('status', 'open')->firstOrFail();

        $this->actingAs($user)
            ->post(route('cooperative.votes.cast', $vote), ['choice' => 'yes'])
            ->assertRedirect(route('cooperative.management').'#vote');

        $this->assertSame(1, $vote->fresh()->yes_count);

        $this->actingAs($user)
            ->get(route('cooperative.management'))
            ->assertOk()
            ->assertSee('You voted YES', false)
            ->assertSee('Member Contribution', false);

        $this->actingAs($user)
            ->post(route('cooperative.loans.store'), [
                'amount' => 50_000,
                'purpose' => 'Buy shared inputs',
            ])
            ->assertRedirect(route('cooperative.management').'#loans');

        $loan = CooperativeLoan::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $loan->status);

        $this->actingAs($user)
            ->post(route('cooperative.loans.review', $loan), ['decision' => 'approve'])
            ->assertRedirect(route('cooperative.management').'#loans');

        $this->assertSame('disbursed', $loan->fresh()->status);
        $this->assertSame(100_000, $coop->fresh()->savings_pool_ngn);

        $this->actingAs($user)
            ->post(route('cooperative.loans.repay', $loan))
            ->assertRedirect(route('cooperative.management').'#loans');

        $this->assertSame('repaid', $loan->fresh()->status);
        $this->assertSame(150_000, $coop->fresh()->savings_pool_ngn);

        $this->actingAs($user)
            ->get(route('cooperative.management'))
            ->assertOk()
            ->assertSee('Group Loan Disbursed', false)
            ->assertSee('Loan Repaid', false)
            ->assertSee('₦150K', false);
    }

    public function test_contribution_requires_wallet_balance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('cooperative.management'))->assertOk();

        $this->actingAs($user)
            ->from(route('cooperative.management'))
            ->post(route('cooperative.contribute'), ['amount' => 25_000])
            ->assertRedirect(route('cooperative.management').'#savings')
            ->assertSessionHas('error');

        $this->assertSame(0, Cooperative::query()->firstOrFail()->savings_pool_ngn);
    }
}
