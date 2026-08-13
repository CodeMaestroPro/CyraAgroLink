<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Exceptions\BusinessLogicException;
use App\Models\MessageThread;
use App\Models\MessagingContact;
use App\Models\MessagingEmailMessage;
use App\Models\MessagingSmsMessage;
use App\Models\MessagingTask;
use App\Models\PlatformAnnouncement;
use App\Models\ThreadMessage;
use App\Models\User;
use App\Models\UserAnnouncementRead;
use App\Models\UserInboxNotification;
use App\Models\WalletTransaction;
use App\Services\Messaging\Sms\SmsGatewayManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Live notifications inbox, conversations, SMS, email, tasks, and announcements.
 */
class NotificationsMessagingService
{
    public function __construct(
        protected SmsGatewayManager $smsGatewayManager
    ) {
    }

    /**
     * @var list<string>
     */
    public const TABS = [
        'notifications',
        'messages',
        'announcements',
        'sms',
        'email',
        'tasks',
        'activity',
    ];

    /**
     * Default directory contacts shown in the messaging sidebar.
     *
     * @var list<array{slug: string, name: string, image_path: string, is_online: bool}>
     */
    protected const DEFAULT_CONTACTS = [
        [
            'slug' => 'green-valley',
            'name' => 'Green Valley Farms',
            'image_path' => 'images/marketplace/supplier-1.jpg',
            'is_online' => true,
        ],
        [
            'slug' => 'sunrise',
            'name' => 'Sunrise Farms',
            'image_path' => 'images/marketplace/supplier-2.jpg',
            'is_online' => false,
        ],
        [
            'slug' => 'tony',
            'name' => 'Tony (Buyer)',
            'image_path' => 'images/avatars/adewale.jpg',
            'is_online' => true,
        ],
        [
            'slug' => 'logistics',
            'name' => 'Logistics Support',
            'image_path' => 'images/logistics/truck-10t.jpg',
            'is_online' => false,
        ],
        [
            'slug' => 'warehouse',
            'name' => 'Warehouse Team',
            'image_path' => 'images/marketplace/supplier-3.jpg',
            'is_online' => false,
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function getInboxData(
        User $user,
        ?string $tab = null,
        ?string $contactSlug = null,
        ?string $search = null
    ): array {
        $tab = $this->resolveTab($tab);
        $this->ensureDirectory();
        $this->ensureAnnouncements();
        $this->ensureUserInbox($user);
        $this->ensureChannelSeeds($user);

        $search = trim((string) $search);
        $contacts = $this->contactsFor($user, $contactSlug, $search !== '' ? $search : null);
        $activeSlug = $contactSlug
            ?: collect($contacts)->firstWhere('active', true)['id']
            ?? 'green-valley';

        $thread = $this->threadPayload($user, $activeSlug);
        $notifications = $this->notificationPayload($user);
        $unread = collect($notifications)->where('unread', true)->count();
        $openTasks = MessagingTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $announcements = $this->announcementPayload($user);
        $smsMessages = $this->smsPayload($user);
        $emailMessages = $this->emailPayload($user);
        $tasks = $this->taskPayload($user);

        return [
            'tab' => $tab,
            'greeting_name' => $this->firstName($user->name),
            'notifications' => $notifications,
            'contacts' => $contacts,
            'active_thread' => $thread,
            'announcements' => $announcements,
            'sms_messages' => $smsMessages,
            'email_messages' => $emailMessages,
            'tasks' => $tasks,
            'activity' => $this->activityPayload($user),
            'unread_notifications' => $unread,
            'notifications_count' => $unread,
            'open_tasks_count' => $openTasks,
            'announcement_stats' => [
                ['label' => 'Active', 'value' => (string) count($announcements)],
                ['label' => 'Pending', 'value' => (string) collect($announcements)->where('acknowledged', false)->count()],
            ],
            'sms_stats' => [
                ['label' => 'Messages', 'value' => (string) count($smsMessages)],
                ['label' => 'Outbound', 'value' => (string) collect($smsMessages)->where('direction', 'outbound')->count()],
            ],
            'email_stats' => [
                ['label' => 'Messages', 'value' => (string) count($emailMessages)],
                ['label' => 'Sent', 'value' => (string) collect($emailMessages)->where('direction', 'outbound')->count()],
            ],
            'task_stats' => [
                ['label' => 'Open', 'value' => (string) $openTasks],
                ['label' => 'Total', 'value' => (string) count($tasks)],
            ],
            'search' => $search,
            'tabs' => $this->tabLinks($tab, $contactSlug, $search !== '' ? $search : null),
            'actions' => [
                'mark_all_read' => route('messaging.notifications.read-all'),
                'send_message' => route('messaging.messages.send'),
                'send_sms' => route('messaging.sms.send'),
                'send_email' => route('messaging.email.send'),
                'store_task' => route('messaging.tasks.store'),
                'store_announcement' => route('messaging.announcements.store'),
            ],
        ];
    }

    public function resolveTab(?string $tab): string
    {
        $tab = $tab ?: 'notifications';

        return in_array($tab, self::TABS, true) ? $tab : 'notifications';
    }

    /**
     * Mark every unread inbox notification as read for the user.
     */
    public function markAllNotificationsRead(User $user): int
    {
        return UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark a single inbox notification as read.
     */
    public function markNotificationRead(User $user, UserInboxNotification $notification): void
    {
        if ($notification->user_id !== $user->id) {
            throw new BusinessLogicException('You can only update your own notifications.', statusCode: 403);
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * Send an outgoing message in a contact thread.
     */
    public function sendMessage(User $user, string $contactSlug, string $body): ThreadMessage
    {
        $body = trim($body);
        if ($body === '') {
            throw new BusinessLogicException('Message cannot be empty.');
        }

        if (mb_strlen($body) > 2000) {
            throw new BusinessLogicException('Message is too long (max 2000 characters).');
        }

        $this->ensureDirectory();
        $contact = MessagingContact::query()->where('slug', $contactSlug)->first();
        if (! $contact) {
            throw new BusinessLogicException('Contact not found.');
        }

        return DB::transaction(function () use ($user, $contact, $body): ThreadMessage {
            $thread = MessageThread::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'messaging_contact_id' => $contact->id,
                ],
                [
                    'last_message_at' => now(),
                    'last_read_at' => now(),
                ]
            );

            $message = ThreadMessage::query()->create([
                'message_thread_id' => $thread->id,
                'user_id' => $user->id,
                'role' => 'outgoing',
                'body' => $body,
            ]);

            $thread->forceFill([
                'last_message_at' => $message->created_at,
                'last_read_at' => now(),
            ])->save();

            if ($thread->messages()->where('role', 'incoming')->count() <= 3) {
                ThreadMessage::query()->create([
                    'message_thread_id' => $thread->id,
                    'user_id' => null,
                    'role' => 'incoming',
                    'body' => 'Thanks, we received your message and will follow up shortly.',
                ]);
                $thread->forceFill(['last_message_at' => now()])->save();
            }

            return $message;
        });
    }

    /**
     * Publish a platform announcement visible in the hub.
     */
    public function publishAnnouncement(User $user, string $title, string $body): PlatformAnnouncement
    {
        $title = trim($title);
        $body = trim($body);

        if ($title === '' || $body === '') {
            throw new BusinessLogicException('Announcement title and body are required.');
        }

        return PlatformAnnouncement::query()->create([
            'title' => $title,
            'body' => $body,
            'audience' => 'all',
            'is_active' => true,
            'published_at' => now(),
            'meta' => ['published_by' => $user->id],
        ]);
    }

    /**
     * Acknowledge an announcement for the current user.
     */
    public function acknowledgeAnnouncement(User $user, PlatformAnnouncement $announcement): void
    {
        if (! $announcement->is_active) {
            throw new BusinessLogicException('This announcement is no longer active.');
        }

        $read = UserAnnouncementRead::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'platform_announcement_id' => $announcement->id,
            ]
        );

        $read->forceFill([
            'acknowledged_at' => now(),
            'dismissed_at' => null,
        ])->save();
    }

    /**
     * Dismiss an announcement for the current user.
     */
    public function dismissAnnouncement(User $user, PlatformAnnouncement $announcement): void
    {
        $read = UserAnnouncementRead::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'platform_announcement_id' => $announcement->id,
            ]
        );

        $read->forceFill([
            'dismissed_at' => now(),
            'acknowledged_at' => $read->acknowledged_at ?? now(),
        ])->save();
    }

    /**
     * Send an SMS via the configured gateway (log or Termii).
     */
    public function sendSms(User $user, string $toPhone, string $body): MessagingSmsMessage
    {
        $toPhone = trim($toPhone);
        $body = trim($body);

        if ($toPhone === '' || $body === '') {
            throw new BusinessLogicException('Phone number and message body are required.');
        }

        if (mb_strlen($body) > 480) {
            throw new BusinessLogicException('SMS body is too long (max 480 characters).');
        }

        $from = $user->phone ?: null;
        $result = $this->smsGatewayManager->driver()->send($toPhone, $body, $from);

        return MessagingSmsMessage::query()->create([
            'user_id' => $user->id,
            'direction' => 'outbound',
            'to_phone' => $toPhone,
            'from_phone' => $from ?: '+2348000000000',
            'body' => $body,
            'status' => $result->ok ? 'sent' : 'failed',
            'provider' => $result->provider,
            'sent_at' => $result->ok ? now() : null,
            'failure_reason' => $result->failureReason,
            'meta' => array_filter([
                'provider_message_id' => $result->providerMessageId,
                'dispatch' => $result->meta,
            ]),
        ]);
    }

    /**
     * Retry a failed SMS.
     */
    public function retrySms(User $user, MessagingSmsMessage $sms): MessagingSmsMessage
    {
        if ($sms->user_id !== $user->id) {
            throw new BusinessLogicException('You can only retry your own SMS messages.', statusCode: 403);
        }

        if ($sms->status !== 'failed') {
            throw new BusinessLogicException('Only failed SMS messages can be retried.');
        }

        $result = $this->smsGatewayManager->driver()->send(
            $sms->to_phone,
            $sms->body,
            $sms->from_phone
        );

        $sms->forceFill([
            'status' => $result->ok ? 'sent' : 'failed',
            'sent_at' => $result->ok ? now() : null,
            'failure_reason' => $result->failureReason,
            'provider' => $result->provider,
            'meta' => array_merge($sms->meta ?? [], array_filter([
                'provider_message_id' => $result->providerMessageId,
                'dispatch' => $result->meta,
                'retried_at' => now()->toIso8601String(),
            ])),
        ])->save();

        return $sms;
    }

    /**
     * Send an email through the configured Laravel mailer.
     */
    public function sendEmail(User $user, string $toEmail, string $subject, string $body): MessagingEmailMessage
    {
        $toEmail = trim($toEmail);
        $subject = trim($subject);
        $body = trim($body);

        if ($toEmail === '' || $subject === '' || $body === '') {
            throw new BusinessLogicException('Recipient, subject, and body are required.');
        }

        if (! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessLogicException('Enter a valid email address.');
        }

        $from = $user->email ?: (string) config('mail.from.address');
        $provider = (string) config('mail.default', 'log');
        $status = 'sent';
        $failure = null;

        try {
            Mail::raw($body, function ($message) use ($toEmail, $subject, $from): void {
                $message->to($toEmail)->subject($subject);
                if (filled($from) && filter_var($from, FILTER_VALIDATE_EMAIL)) {
                    $message->from($from);
                }
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            $failure = mb_substr($e->getMessage(), 0, 240);
        }

        return MessagingEmailMessage::query()->create([
            'user_id' => $user->id,
            'direction' => 'outbound',
            'to_email' => $toEmail,
            'from_email' => $from,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'provider' => $provider,
            'sent_at' => $status === 'sent' ? now() : null,
            'failure_reason' => $failure,
        ]);
    }

    /**
     * Create a follow-up task.
     */
    public function createTask(
        User $user,
        string $title,
        ?string $body = null,
        string $priority = 'medium',
        ?string $dueAt = null
    ): MessagingTask {
        $title = trim($title);
        $body = $body !== null ? trim($body) : null;

        if ($title === '') {
            throw new BusinessLogicException('Task title is required.');
        }

        if (! in_array($priority, ['low', 'medium', 'high'], true)) {
            throw new BusinessLogicException('Invalid task priority.');
        }

        return MessagingTask::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body !== '' ? $body : null,
            'priority' => $priority,
            'status' => 'open',
            'due_at' => $dueAt ? \Illuminate\Support\Carbon::parse($dueAt)->startOfDay() : null,
            'source' => 'manual',
        ]);
    }

    /**
     * Mark a task as done.
     */
    public function completeTask(User $user, MessagingTask $task): void
    {
        $this->assertTaskOwner($user, $task);

        if ($task->status === 'done') {
            return;
        }

        if ($task->status === 'cancelled') {
            throw new BusinessLogicException('Cancelled tasks cannot be completed.');
        }

        $task->forceFill([
            'status' => 'done',
            'completed_at' => now(),
        ])->save();
    }

    /**
     * Start working on a task.
     */
    public function startTask(User $user, MessagingTask $task): void
    {
        $this->assertTaskOwner($user, $task);

        if (! in_array($task->status, ['open', 'in_progress'], true)) {
            throw new BusinessLogicException('Only open tasks can be started.');
        }

        $task->forceFill([
            'status' => 'in_progress',
            'completed_at' => null,
        ])->save();
    }

    /**
     * Cancel a task.
     */
    public function cancelTask(User $user, MessagingTask $task): void
    {
        $this->assertTaskOwner($user, $task);

        if ($task->status === 'done') {
            throw new BusinessLogicException('Completed tasks cannot be cancelled.');
        }

        $task->forceFill([
            'status' => 'cancelled',
            'completed_at' => null,
        ])->save();
    }

    /**
     * Reopen a done or cancelled task.
     */
    public function reopenTask(User $user, MessagingTask $task): void
    {
        $this->assertTaskOwner($user, $task);

        if (! in_array($task->status, ['done', 'cancelled'], true)) {
            throw new BusinessLogicException('Only done or cancelled tasks can be reopened.');
        }

        $task->forceFill([
            'status' => 'open',
            'completed_at' => null,
        ])->save();
    }

    protected function assertTaskOwner(User $user, MessagingTask $task): void
    {
        if ($task->user_id !== $user->id) {
            throw new BusinessLogicException('You can only manage your own tasks.', statusCode: 403);
        }
    }

    protected function ensureDirectory(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        foreach (self::DEFAULT_CONTACTS as $contact) {
            MessagingContact::query()->firstOrCreate(
                ['slug' => $contact['slug']],
                [
                    'name' => $contact['name'],
                    'image_path' => $contact['image_path'],
                    'is_online' => $contact['is_online'],
                    'is_system' => true,
                ]
            );
        }
    }

    protected function ensureAnnouncements(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (PlatformAnnouncement::query()->exists()) {
            return;
        }

        PlatformAnnouncement::query()->create([
            'title' => 'AI Disease Detection is live',
            'body' => 'Upload crop photos from the Disease Detection module for instant AI diagnosis and treatment guidance.',
            'audience' => 'all',
            'is_active' => true,
            'published_at' => now()->subHours(5),
        ]);

        PlatformAnnouncement::query()->create([
            'title' => 'Marketplace escrow tips',
            'body' => 'Always confirm delivery in Logistics before releasing escrow payments to suppliers.',
            'audience' => 'all',
            'is_active' => true,
            'published_at' => now()->subDays(1),
        ]);
    }

    protected function ensureChannelSeeds(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (! MessagingSmsMessage::query()->where('user_id', $user->id)->exists()) {
            MessagingSmsMessage::query()->create([
                'user_id' => $user->id,
                'direction' => 'inbound',
                'to_phone' => $user->phone ?: '+2348000000000',
                'from_phone' => '+2348011112222',
                'body' => 'Your CyraAgroLink OTP is 482910. Do not share this code.',
                'status' => 'delivered',
                'provider' => 'cyra-local',
                'sent_at' => now()->subHours(4),
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ]);

            MessagingSmsMessage::query()->create([
                'user_id' => $user->id,
                'direction' => 'outbound',
                'to_phone' => '+2348098765432',
                'from_phone' => $user->phone ?: '+2348000000000',
                'body' => 'Order #ORD1234 left the warehouse and is en route to Ibadan.',
                'status' => 'sent',
                'provider' => 'cyra-local',
                'sent_at' => now()->subHours(2),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
        }

        if (! MessagingEmailMessage::query()->where('user_id', $user->id)->exists()) {
            MessagingEmailMessage::query()->create([
                'user_id' => $user->id,
                'direction' => 'inbound',
                'to_email' => $user->email,
                'from_email' => 'alerts@cyraagrolink.local',
                'subject' => 'Weekly farm performance summary',
                'body' => 'Your farms recorded a 12% yield improvement this week. Open Reports for the full breakdown.',
                'status' => 'opened',
                'provider' => 'cyra-local',
                'sent_at' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);

            MessagingEmailMessage::query()->create([
                'user_id' => $user->id,
                'direction' => 'outbound',
                'to_email' => 'buyer@greenvalley.local',
                'from_email' => $user->email,
                'subject' => 'Confirm tomato delivery window',
                'body' => 'Please confirm the Tuesday 10am delivery slot for the 2-tonne tomato order.',
                'status' => 'sent',
                'provider' => 'cyra-local',
                'sent_at' => now()->subHours(6),
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ]);
        }

        if (! MessagingTask::query()->where('user_id', $user->id)->exists()) {
            MessagingTask::query()->create([
                'user_id' => $user->id,
                'title' => 'Follow up on order #ORD1234',
                'body' => 'Call Logistics Support if the shipment is still pending by evening.',
                'priority' => 'high',
                'status' => 'open',
                'due_at' => now()->addDay()->startOfDay(),
                'source' => 'system',
            ]);

            MessagingTask::query()->create([
                'user_id' => $user->id,
                'title' => 'Review weather advisory for Ibadan',
                'body' => 'Heavy rainfall expected tomorrow — delay fertilizer application if needed.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_at' => now()->startOfDay(),
                'source' => 'notification',
            ]);

            MessagingTask::query()->create([
                'user_id' => $user->id,
                'title' => 'Upload disease detection photo',
                'body' => 'Capture leaf samples from plot B for AI diagnosis.',
                'priority' => 'low',
                'status' => 'open',
                'due_at' => now()->addDays(3)->startOfDay(),
                'source' => 'manual',
            ]);
        }
    }

    protected function ensureUserInbox(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (! UserInboxNotification::query()->where('user_id', $user->id)->exists()) {
            $seeds = [
                [
                    'title' => 'Payment received',
                    'body' => 'Your payment of ₦450,000 was successful.',
                    'tone' => 'payment',
                    'notification_key' => 'seed-payment',
                    'minutes_ago' => 5,
                    'unread' => true,
                ],
                [
                    'title' => 'Order Update',
                    'body' => 'Your order #ORD1234 is in transit.',
                    'tone' => 'order',
                    'notification_key' => 'seed-order',
                    'minutes_ago' => 30,
                    'unread' => true,
                ],
                [
                    'title' => 'Investment Update',
                    'body' => 'Your investment in Green Valley Farm earned ₦25,000.',
                    'tone' => 'investment',
                    'notification_key' => 'seed-investment',
                    'minutes_ago' => 60,
                    'unread' => true,
                ],
                [
                    'title' => 'Weather Alert',
                    'body' => 'Heavy rainfall expected in Ibadan tomorrow.',
                    'tone' => 'weather',
                    'notification_key' => 'seed-weather',
                    'minutes_ago' => 120,
                    'unread' => false,
                ],
                [
                    'title' => 'New Message',
                    'body' => 'You have a new message from Green Valley Farms.',
                    'tone' => 'message',
                    'notification_key' => 'seed-message',
                    'minutes_ago' => 180,
                    'unread' => false,
                ],
                [
                    'title' => 'System Update',
                    'body' => 'New feature: AI Disease Detection is now live.',
                    'tone' => 'system',
                    'notification_key' => 'seed-system',
                    'minutes_ago' => 300,
                    'unread' => false,
                ],
            ];

            foreach ($seeds as $seed) {
                $createdAt = now()->subMinutes($seed['minutes_ago']);
                UserInboxNotification::query()->create([
                    'user_id' => $user->id,
                    'title' => $seed['title'],
                    'body' => $seed['body'],
                    'tone' => $seed['tone'],
                    'category' => 'alert',
                    'notification_key' => $seed['notification_key'],
                    'read_at' => $seed['unread'] ? null : $createdAt->copy()->addMinute(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $greenValley = MessagingContact::query()->where('slug', 'green-valley')->first();
        if (! $greenValley) {
            return;
        }

        $thread = MessageThread::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'messaging_contact_id' => $greenValley->id,
            ],
            [
                'last_message_at' => now()->subMinutes(24),
                'last_read_at' => now()->subMinutes(24),
            ]
        );

        if (! $thread->messages()->exists()) {
            $name = $this->firstName($user->name);
            $messages = [
                [
                    'role' => 'incoming',
                    'body' => "Hello {$name}, We have received your order. It will be shipped tomorrow.",
                    'minutes_ago' => 30,
                ],
                [
                    'role' => 'outgoing',
                    'body' => 'Great! Please keep me updated.',
                    'minutes_ago' => 25,
                ],
                [
                    'role' => 'incoming',
                    'body' => 'Sure, we will.',
                    'minutes_ago' => 24,
                ],
            ];

            foreach ($messages as $row) {
                $at = now()->subMinutes($row['minutes_ago']);
                ThreadMessage::query()->create([
                    'message_thread_id' => $thread->id,
                    'user_id' => $row['role'] === 'outgoing' ? $user->id : null,
                    'role' => $row['role'],
                    'body' => $row['body'],
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }

            $thread->forceFill([
                'last_message_at' => now()->subMinutes(24),
                'last_read_at' => now()->subMinutes(24),
            ])->save();
        }

        $this->seedContactPreviews($user);
    }

    protected function seedContactPreviews(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        $previews = [
            'sunrise' => ['Shipment ready', 75],
            'tony' => ['Price update?', 60 * 20],
            'logistics' => ['Driver assigned', 60 * 22],
            'warehouse' => ['Stock confirmed', 60 * 48],
        ];

        foreach ($previews as $slug => [$body, $minutesAgo]) {
            $contact = MessagingContact::query()->where('slug', $slug)->first();
            if (! $contact) {
                continue;
            }

            $thread = MessageThread::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'messaging_contact_id' => $contact->id,
                ],
                [
                    'last_message_at' => now()->subMinutes($minutesAgo),
                    'last_read_at' => now()->subMinutes($minutesAgo),
                ]
            );

            if ($thread->messages()->exists()) {
                continue;
            }

            $at = now()->subMinutes($minutesAgo);
            ThreadMessage::query()->create([
                'message_thread_id' => $thread->id,
                'user_id' => null,
                'role' => 'incoming',
                'body' => $body,
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            $thread->forceFill([
                'last_message_at' => $at,
                'last_read_at' => $at,
            ])->save();
        }
    }

    /**
     * @return list<array{id: string, label: string, href: string}>
     */
    protected function tabLinks(string $activeTab, ?string $contact, ?string $search): array
    {
        $labels = [
            'notifications' => 'Notifications',
            'messages' => 'Conversations',
            'announcements' => 'Announcements',
            'sms' => 'SMS',
            'email' => 'Email',
            'tasks' => 'Tasks',
            'activity' => 'Activity Log',
        ];

        $links = [];
        foreach ($labels as $id => $label) {
            $params = array_filter([
                'tab' => $id,
                'contact' => $id === 'messages' ? $contact : null,
                'q' => $id === 'messages' ? $search : null,
            ]);
            $links[] = [
                'id' => $id,
                'label' => $label,
                'href' => route('messaging.index', $params),
            ];
        }

        return $links;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function notificationPayload(User $user): array
    {
        return UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (UserInboxNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'time' => $n->created_at?->diffForHumans(short: true) ?? '',
                'tone' => $n->tone,
                'unread' => $n->isUnread(),
                'mark_read_url' => route('messaging.notifications.read', $n),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function contactsFor(User $user, ?string $activeSlug, ?string $search): array
    {
        $contacts = MessagingContact::query()
            ->orderBy('name')
            ->get();

        if ($search) {
            $needle = mb_strtolower($search);
            $contacts = $contacts->filter(
                fn (MessagingContact $c) => str_contains(mb_strtolower($c->name), $needle)
            )->values();
        }

        $threads = MessageThread::query()
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->get()
            ->keyBy('messaging_contact_id');

        $activeSlug = $activeSlug ?: 'green-valley';
        if ($contacts->isNotEmpty() && ! $contacts->contains(fn (MessagingContact $c) => $c->slug === $activeSlug)) {
            $activeSlug = $contacts->first()->slug;
        }

        return $contacts->map(function (MessagingContact $contact) use ($threads, $activeSlug) {
            /** @var MessageThread|null $thread */
            $thread = $threads->get($contact->id);
            $last = $thread?->messages->first();

            return [
                'id' => $contact->slug,
                'name' => $contact->name,
                'preview' => $last?->body ? $this->preview($last->body) : 'No messages yet',
                'time' => $this->sidebarTime($thread?->last_message_at ?? $last?->created_at),
                'active' => $contact->slug === $activeSlug,
                'online' => (bool) $contact->is_online,
                'image' => $contact->imageUrl(),
                'url' => route('messaging.index', [
                    'tab' => 'messages',
                    'contact' => $contact->slug,
                ]),
            ];
        })->sortByDesc(fn (array $row) => $row['active'])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function threadPayload(User $user, string $contactSlug): array
    {
        $contact = MessagingContact::query()->where('slug', $contactSlug)->first()
            ?? MessagingContact::query()->where('slug', 'green-valley')->firstOrFail();

        $thread = MessageThread::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'messaging_contact_id' => $contact->id,
            ],
            [
                'last_message_at' => now(),
                'last_read_at' => now(),
            ]
        );

        $thread->forceFill(['last_read_at' => now()])->save();

        $messages = ThreadMessage::query()
            ->where('message_thread_id', $thread->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ThreadMessage $m) => [
                'role' => $m->role,
                'body' => $m->body,
                'time' => $m->created_at?->format('g:i A') ?? '',
            ])
            ->all();

        return [
            'contact_id' => $contact->slug,
            'contact_name' => $contact->name,
            'online' => (bool) $contact->is_online,
            'image' => $contact->imageUrl(),
            'messages' => $messages,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function announcementPayload(User $user): array
    {
        $reads = UserAnnouncementRead::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('platform_announcement_id');

        return PlatformAnnouncement::query()
            ->where('is_active', true)
            ->latest('published_at')
            ->limit(20)
            ->get()
            ->filter(function (PlatformAnnouncement $a) use ($reads) {
                /** @var UserAnnouncementRead|null $read */
                $read = $reads->get($a->id);

                return ! $read || $read->dismissed_at === null;
            })
            ->map(function (PlatformAnnouncement $a) use ($reads) {
                /** @var UserAnnouncementRead|null $read */
                $read = $reads->get($a->id);

                $audience = match ($a->audience) {
                    'farmers' => 'Farmers',
                    'buyers' => 'Buyers',
                    'partners' => 'Partners',
                    default => 'All users',
                };

                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'body' => $a->body,
                    'audience' => $audience,
                    'time' => $a->published_at?->diffForHumans() ?? '',
                    'acknowledged' => $read?->acknowledged_at !== null,
                    'acknowledge_url' => route('messaging.announcements.acknowledge', $a),
                    'dismiss_url' => route('messaging.announcements.dismiss', $a),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function smsPayload(User $user): array
    {
        return MessagingSmsMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (MessagingSmsMessage $sms) => [
                'id' => $sms->id,
                'direction' => $sms->direction,
                'peer' => $sms->direction === 'outbound' ? $sms->to_phone : ($sms->from_phone ?: 'Unknown'),
                'body' => $sms->body,
                'status' => $sms->statusLabel(),
                'status_key' => $sms->status,
                'time' => ($sms->sent_at ?? $sms->created_at)?->diffForHumans() ?? '',
                'can_retry' => $sms->status === 'failed',
                'retry_url' => route('messaging.sms.retry', $sms),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function emailPayload(User $user): array
    {
        return MessagingEmailMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (MessagingEmailMessage $email) => [
                'id' => $email->id,
                'direction' => $email->direction,
                'peer' => $email->direction === 'outbound' ? $email->to_email : ($email->from_email ?: 'Unknown'),
                'subject' => $email->subject,
                'body' => $email->body,
                'status' => $email->statusLabel(),
                'time' => ($email->sent_at ?? $email->created_at)?->diffForHumans() ?? '',
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function taskPayload(User $user): array
    {
        $priority = ['in_progress' => 0, 'open' => 1, 'done' => 2, 'cancelled' => 3];

        return MessagingTask::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(30)
            ->get()
            ->sortBy(fn (MessagingTask $task) => $priority[$task->status] ?? 9)
            ->values()
            ->map(fn (MessagingTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'body' => $task->body,
                'priority' => $task->priorityLabel(),
                'priority_key' => $task->priority,
                'status' => $task->statusLabel(),
                'status_key' => $task->status,
                'due' => $task->due_at?->format('d M Y') ?? 'No due date',
                'time' => $task->created_at?->diffForHumans() ?? '',
                'is_open' => $task->isOpen(),
                'can_start' => $task->status === 'open',
                'can_complete' => $task->isOpen(),
                'can_cancel' => $task->isOpen(),
                'can_reopen' => in_array($task->status, ['done', 'cancelled'], true),
                'start_url' => route('messaging.tasks.start', $task),
                'complete_url' => route('messaging.tasks.complete', $task),
                'cancel_url' => route('messaging.tasks.cancel', $task),
                'reopen_url' => route('messaging.tasks.reopen', $task),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activityPayload(User $user): array
    {
        $items = collect();

        WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(6)
            ->get()
            ->each(function (WalletTransaction $tx) use ($items): void {
                $items->push([
                    'title' => $tx->title,
                    'body' => $tx->detail ?: ($tx->isCredit() ? 'Credit' : 'Debit').' · ₦'.number_format($tx->amount),
                    'time' => $tx->created_at?->diffForHumans() ?? '',
                    'at' => $tx->created_at?->timestamp ?? 0,
                    'channel' => 'Wallet',
                ]);
            });

        ThreadMessage::query()
            ->whereHas('thread', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->limit(6)
            ->get()
            ->each(function (ThreadMessage $m) use ($items): void {
                $items->push([
                    'title' => $m->role === 'outgoing' ? 'You sent a message' : 'Message received',
                    'body' => $this->preview($m->body, 80),
                    'time' => $m->created_at?->diffForHumans() ?? '',
                    'at' => $m->created_at?->timestamp ?? 0,
                    'channel' => 'Conversations',
                ]);
            });

        MessagingSmsMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(4)
            ->get()
            ->each(function (MessagingSmsMessage $sms) use ($items): void {
                $items->push([
                    'title' => $sms->direction === 'outbound' ? 'SMS sent' : 'SMS received',
                    'body' => $this->preview($sms->body, 80),
                    'time' => ($sms->sent_at ?? $sms->created_at)?->diffForHumans() ?? '',
                    'at' => ($sms->sent_at ?? $sms->created_at)?->timestamp ?? 0,
                    'channel' => 'SMS',
                ]);
            });

        MessagingEmailMessage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(4)
            ->get()
            ->each(function (MessagingEmailMessage $email) use ($items): void {
                $items->push([
                    'title' => $email->subject,
                    'body' => $this->preview($email->body, 80),
                    'time' => ($email->sent_at ?? $email->created_at)?->diffForHumans() ?? '',
                    'at' => ($email->sent_at ?? $email->created_at)?->timestamp ?? 0,
                    'channel' => 'Email',
                ]);
            });

        MessagingTask::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->each(function (MessagingTask $task) use ($items): void {
                $items->push([
                    'title' => 'Task · '.$task->statusLabel(),
                    'body' => $task->title,
                    'time' => $task->updated_at?->diffForHumans() ?? '',
                    'at' => $task->updated_at?->timestamp ?? 0,
                    'channel' => 'Tasks',
                ]);
            });

        return $items
            ->sortByDesc('at')
            ->take(15)
            ->values()
            ->map(fn (array $row) => [
                'title' => $row['title'],
                'body' => $row['body'],
                'time' => $row['time'],
                'channel' => $row['channel'],
            ])
            ->all();
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Farmer';
    }

    protected function preview(string $body, int $limit = 40): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? $body);
        if (mb_strlen($body) <= $limit) {
            return $body;
        }

        return mb_substr($body, 0, $limit - 1).'…';
    }

    protected function sidebarTime(mixed $when): string
    {
        if (! $when) {
            return '';
        }

        $at = \Illuminate\Support\Carbon::parse($when);
        if ($at->isToday()) {
            return $at->format('g:i');
        }
        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        return $at->format('D');
    }
}
