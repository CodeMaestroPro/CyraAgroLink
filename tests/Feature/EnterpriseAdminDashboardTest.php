<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for Enterprise Admin Dashboard.
 */
class EnterpriseAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_enterprise_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Enterprise Admin Dashboard');
        $response->assertDontSee('23. ENTERPRISE ADMIN DASHBOARD');
        $response->assertSee('Platform Overview', false);
        $response->assertSee('Monitor and manage the entire ecosystem.', false);
        $response->assertSee('Total Users', false);
        $response->assertSee('Active Users', false);
        $response->assertSee('Wallet Volume', false);
        $response->assertSee('Pending Farms', false);
        $response->assertSee('User Distribution', false);
        $response->assertSee('System Activity', false);
        $response->assertSee('Platform Alerts', false);
        $response->assertSee('Recent Verifications', false);
        $response->assertSee('Quick Actions', false);
        $response->assertSee('Approve Farms', false);
        $response->assertSee('Manage Users', false);
        $response->assertSee('User Management', false);
        $response->assertSee('Audit Logs', false);
        $response->assertSee('Security Center', false);
    }

    public function test_admin_tabs_are_reachable(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['role' => UserRole::Farmer]);

        foreach (['users', 'roles', 'verification', 'audit', 'security'] as $tab) {
            $this->actingAs($admin)
                ->get(route('admin.dashboard', ['tab' => $tab]))
                ->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tab' => 'users']))
            ->assertSee('Update status', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tab' => 'roles']))
            ->assertSee('Update role', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tab' => 'security']))
            ->assertSee('Active Sessions', false);
    }

    public function test_admin_can_approve_pending_farm(): void
    {
        $admin = User::factory()->admin()->create();
        $farmer = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $farmer->id,
            'name' => 'Green Valley Farms',
            'status' => FarmStatus::PendingReview,
            'registration_step' => 5,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.farms.approve', $farm))
            ->assertRedirect(route('admin.dashboard', ['tab' => 'verification']));

        $this->assertSame(FarmStatus::Active, $farm->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'farm.approved',
        ]);
    }

    public function test_admin_can_reject_pending_farm(): void
    {
        $admin = User::factory()->admin()->create();
        $farmer = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $farmer->id,
            'name' => 'Dry Creek Farms',
            'status' => FarmStatus::PendingReview,
            'registration_step' => 5,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.farms.reject', $farm))
            ->assertRedirect(route('admin.dashboard', ['tab' => 'verification']));

        $this->assertSame(FarmStatus::Inactive, $farm->fresh()->status);
    }

    public function test_admin_cannot_approve_non_pending_farm(): void
    {
        $admin = User::factory()->admin()->create();
        $farmer = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $farmer->id,
            'name' => 'Already Active Farm',
            'status' => FarmStatus::Active,
            'registration_step' => 5,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard', ['tab' => 'verification']))
            ->post(route('admin.farms.approve', $farm))
            ->assertRedirect(route('admin.dashboard', ['tab' => 'verification']))
            ->assertSessionHas('error');
    }

    public function test_admin_can_update_user_status_and_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create([
            'role' => UserRole::Farmer,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.status', $member), ['status' => UserStatus::Suspended->value, 'tab' => 'users'])
            ->assertRedirect(route('admin.dashboard', ['tab' => 'users']));

        $this->assertSame(UserStatus::Suspended, $member->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.users.role', $member), ['role' => UserRole::Buyer->value])
            ->assertRedirect(route('admin.dashboard', ['tab' => 'roles']));

        $this->assertSame(UserRole::Buyer, $member->fresh()->role);
        $this->assertGreaterThanOrEqual(2, AuditLog::query()->count());
    }

    public function test_admin_cannot_change_own_status_or_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.status', $admin), ['status' => UserStatus::Suspended->value])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.users.role', $admin), ['role' => UserRole::Farmer->value])
            ->assertSessionHas('error');
    }

    public function test_non_admin_cannot_perform_admin_writes(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::Farmer]);
        $target = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $target->id,
            'name' => 'Blocked Farm',
            'status' => FarmStatus::PendingReview,
            'registration_step' => 5,
        ]);

        $this->actingAs($farmer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($farmer)->post(route('admin.farms.approve', $farm))->assertForbidden();
        $this->actingAs($farmer)->post(route('admin.users.status', $target), [
            'status' => UserStatus::Suspended->value,
        ])->assertForbidden();
    }

    public function test_admin_can_revoke_session(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'session-to-revoke-123',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sessions.revoke'), ['session_id' => 'session-to-revoke-123'])
            ->assertRedirect(route('admin.dashboard', ['tab' => 'security']));

        $this->assertDatabaseMissing('sessions', ['id' => 'session-to-revoke-123']);
    }

    public function test_admin_role_sees_enterprise_admin_dashboard_on_main_dashboard_route(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Platform Overview', false);
        $response->assertSee('Enterprise Admin Dashboard', false);
        $response->assertDontSee('Farm Overview');
        $response->assertDontSee('National Agricultural Overview');
    }

    public function test_guest_cannot_view_enterprise_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
