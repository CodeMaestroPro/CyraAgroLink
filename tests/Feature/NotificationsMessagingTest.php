<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MessagingTask;
use App\Models\PlatformAnnouncement;
use App\Models\User;
use App\Models\UserInboxNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Notifications & Messaging.
 */
class NotificationsMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_notifications_tab(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('messaging.index'));

        $response->assertOk();
        $response->assertSee('Notifications & Messaging');
        $response->assertDontSee('19. NOTIFICATIONS & MESSAGING');
        $response->assertDontSee('19. Notifications & Messaging');
        $response->assertSee('All Notifications', false);
        $response->assertSee('Mark all as read', false);
        $response->assertSee('Payment received', false);
        $response->assertSee('Your payment of ₦450,000 was successful.', false);
        $response->assertSee('Order Update', false);
        $response->assertSee('Investment Update', false);
        $response->assertSee('Weather Alert', false);
        $response->assertSee('New Message', false);
        $response->assertSee('System Update', false);
        $response->assertSee(route('messaging.index', ['tab' => 'messages']), false);
        $response->assertSee(route('messaging.index', ['tab' => 'announcements']), false);
        $response->assertSee(route('messaging.index', ['tab' => 'sms']), false);
        $response->assertSee(route('messaging.index', ['tab' => 'email']), false);
        $response->assertSee(route('messaging.index', ['tab' => 'tasks']), false);
        $response->assertSee(route('messaging.index', ['tab' => 'activity']), false);
    }

    public function test_conversations_tab_shows_contacts_and_thread(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'messages']))
            ->assertOk()
            ->assertSee('Messages', false)
            ->assertSee('Search messages...', false)
            ->assertSee('Green Valley Farms', false)
            ->assertSee('Sunrise Farms', false)
            ->assertSee('Tony (Buyer)', false)
            ->assertSee('Logistics Support', false)
            ->assertSee('Warehouse Team', false)
            ->assertSee('Online', false)
            ->assertSee('Hello Adewale', false)
            ->assertSee('Great! Please keep me updated.', false)
            ->assertSee('Type a message...', false);
    }

    public function test_announcements_sms_email_tasks_and_activity_tabs_are_live(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'announcements']))
            ->assertOk()
            ->assertSee('Announcements', false)
            ->assertSee('AI Disease Detection is live', false)
            ->assertSee('Publish announcement', false)
            ->assertSee('Acknowledge', false);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'sms']))
            ->assertOk()
            ->assertSee('Compose SMS', false)
            ->assertSee('Order #ORD1234 left the warehouse', false);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'email']))
            ->assertOk()
            ->assertSee('Compose email', false)
            ->assertSee('Weekly farm performance summary', false);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Follow up on order #ORD1234', false)
            ->assertSee('New task', false)
            ->assertSee('Complete', false);

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'activity']))
            ->assertOk()
            ->assertSee('Activity Log', false)
            ->assertSee('SMS', false);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)->get(route('messaging.index'))->assertOk();

        $this->assertSame(
            3,
            UserInboxNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count()
        );

        $this->actingAs($user)
            ->post(route('messaging.notifications.read-all'))
            ->assertRedirect(route('messaging.index', ['tab' => 'notifications']));

        $this->assertSame(
            0,
            UserInboxNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count()
        );
    }

    public function test_user_can_send_a_message_and_switch_contacts(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)->get(route('messaging.index', ['tab' => 'messages']))->assertOk();

        $this->actingAs($user)
            ->post(route('messaging.messages.send'), [
                'contact' => 'sunrise',
                'message' => 'When will the tomatoes ship?',
            ])
            ->assertRedirect(route('messaging.index', ['tab' => 'messages', 'contact' => 'sunrise']));

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'messages', 'contact' => 'sunrise']))
            ->assertOk()
            ->assertSee('When will the tomatoes ship?', false)
            ->assertSee('Sunrise Farms', false)
            ->assertSee('Thanks, we received your message', false);
    }

    public function test_user_can_search_contacts(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)->get(route('messaging.index', ['tab' => 'messages']))->assertOk();

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'messages', 'q' => 'Tony']))
            ->assertOk()
            ->assertSee('Tony (Buyer)', false)
            ->assertDontSee('Warehouse Team', false);
    }

    public function test_user_can_publish_acknowledge_and_dismiss_announcement(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)->get(route('messaging.index', ['tab' => 'announcements']))->assertOk();

        $this->actingAs($user)
            ->post(route('messaging.announcements.store'), [
                'title' => 'Harvest festival reminder',
                'body' => 'Register your farm stall by Friday.',
            ])
            ->assertRedirect(route('messaging.index', ['tab' => 'announcements']));

        $announcement = PlatformAnnouncement::query()->where('title', 'Harvest festival reminder')->firstOrFail();

        $this->actingAs($user)
            ->post(route('messaging.announcements.acknowledge', $announcement))
            ->assertRedirect(route('messaging.index', ['tab' => 'announcements']));

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'announcements']))
            ->assertOk()
            ->assertSee('Harvest festival reminder', false)
            ->assertSee('Acknowledged', false);

        $this->actingAs($user)
            ->post(route('messaging.announcements.dismiss', $announcement))
            ->assertRedirect(route('messaging.index', ['tab' => 'announcements']));

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'announcements']))
            ->assertOk()
            ->assertDontSee('Harvest festival reminder', false);
    }

    public function test_user_can_send_sms_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)
            ->post(route('messaging.sms.send'), [
                'to_phone' => '+2348099998888',
                'body' => 'Driver arriving in 20 minutes.',
            ])
            ->assertRedirect(route('messaging.index', ['tab' => 'sms']));

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'sms']))
            ->assertOk()
            ->assertSee('Driver arriving in 20 minutes.', false)
            ->assertSee('+2348099998888', false);

        $this->actingAs($user)
            ->post(route('messaging.email.send'), [
                'to_email' => 'ops@sunrise.local',
                'subject' => 'Cold chain check',
                'body' => 'Please confirm cooler temperature before loading.',
            ])
            ->assertRedirect(route('messaging.index', ['tab' => 'email']));

        $this->actingAs($user)
            ->get(route('messaging.index', ['tab' => 'email']))
            ->assertOk()
            ->assertSee('Cold chain check', false)
            ->assertSee('ops@sunrise.local', false);
    }

    public function test_user_can_manage_tasks(): void
    {
        $user = User::factory()->create(['name' => 'Adewale Okonkwo']);

        $this->actingAs($user)->get(route('messaging.index', ['tab' => 'tasks']))->assertOk();

        $this->actingAs($user)
            ->post(route('messaging.tasks.store'), [
                'title' => 'Inspect poultry house 2',
                'body' => 'Check feeders and water lines.',
                'priority' => 'high',
                'due_at' => now()->addDays(2)->toDateString(),
            ])
            ->assertRedirect(route('messaging.index', ['tab' => 'tasks']));

        $task = MessagingTask::query()->where('title', 'Inspect poultry house 2')->firstOrFail();

        $this->actingAs($user)
            ->post(route('messaging.tasks.start', $task))
            ->assertRedirect(route('messaging.index', ['tab' => 'tasks']));

        $this->actingAs($user)
            ->post(route('messaging.tasks.complete', $task))
            ->assertRedirect(route('messaging.index', ['tab' => 'tasks']));

        $this->assertSame('done', $task->fresh()->status);

        $this->actingAs($user)
            ->post(route('messaging.tasks.reopen', $task))
            ->assertRedirect(route('messaging.index', ['tab' => 'tasks']));

        $this->assertSame('open', $task->fresh()->status);
    }

    public function test_guest_cannot_view_messaging(): void
    {
        $this->get(route('messaging.index'))->assertRedirect(route('login'));
    }
}
