<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Messaging;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\MessagingSmsMessage;
use App\Models\MessagingTask;
use App\Models\PlatformAnnouncement;
use App\Models\UserInboxNotification;
use App\Services\Messaging\NotificationsMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications feed and messaging inbox.
 */
class NotificationsMessagingController extends Controller
{
    public function __construct(
        protected NotificationsMessagingService $notificationsMessagingService
    ) {
    }

    /**
     * Display the notifications and messaging screen.
     */
    public function index(Request $request): View
    {
        $data = $this->notificationsMessagingService->getInboxData(
            $request->user(),
            $request->string('tab')->toString() ?: null,
            $request->string('contact')->toString() ?: null,
            $request->string('q')->toString() ?: null
        );

        return view('messaging.index', [
            'tab' => $data['tab'],
            'tabs' => $data['tabs'],
            'greetingName' => $data['greeting_name'],
            'notifications' => $data['notifications'],
            'contacts' => $data['contacts'],
            'activeThread' => $data['active_thread'],
            'announcements' => $data['announcements'],
            'smsMessages' => $data['sms_messages'],
            'emailMessages' => $data['email_messages'],
            'tasks' => $data['tasks'],
            'activity' => $data['activity'],
            'unreadNotifications' => $data['unread_notifications'],
            'notificationsCount' => $data['notifications_count'],
            'openTasksCount' => $data['open_tasks_count'],
            'announcementStats' => $data['announcement_stats'],
            'smsStats' => $data['sms_stats'],
            'emailStats' => $data['email_stats'],
            'taskStats' => $data['task_stats'],
            'search' => $data['search'],
            'actions' => $data['actions'],
        ]);
    }

    /**
     * Mark all inbox notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $count = $this->notificationsMessagingService->markAllNotificationsRead($request->user());

        return redirect()
            ->route('messaging.index', $this->preserveQuery($request, 'notifications'))
            ->with('status', $count > 0
                ? "Marked {$count} notification(s) as read."
                : 'All notifications are already read.');
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, UserInboxNotification $notification): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->markNotificationRead($request->user(), $notification);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'notifications');
        }

        return redirect()
            ->route('messaging.index', $this->preserveQuery($request, 'notifications'))
            ->with('status', 'Notification marked as read.');
    }

    /**
     * Send a message in the active conversation thread.
     */
    public function sendMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact' => ['required', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->notificationsMessagingService->sendMessage(
                $request->user(),
                $data['contact'],
                $data['message']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('messaging.index', ['tab' => 'messages', 'contact' => $data['contact']])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'messages', 'contact' => $data['contact']])
            ->with('status', 'Message sent.');
    }

    /**
     * Publish a new announcement.
     */
    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        try {
            $announcement = $this->notificationsMessagingService->publishAnnouncement(
                $request->user(),
                $data['title'],
                $data['body']
            );
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'announcements');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'announcements'])
            ->with('status', 'Announcement published: '.$announcement->title);
    }

    /**
     * Acknowledge an announcement.
     */
    public function acknowledgeAnnouncement(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->acknowledgeAnnouncement($request->user(), $announcement);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'announcements');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'announcements'])
            ->with('status', 'Announcement acknowledged.');
    }

    /**
     * Dismiss an announcement.
     */
    public function dismissAnnouncement(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->dismissAnnouncement($request->user(), $announcement);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'announcements');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'announcements'])
            ->with('status', 'Announcement dismissed.');
    }

    /**
     * Send an SMS.
     */
    public function sendSms(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to_phone' => ['required', 'string', 'max:32'],
            'body' => ['required', 'string', 'max:480'],
        ]);

        try {
            $sms = $this->notificationsMessagingService->sendSms(
                $request->user(),
                $data['to_phone'],
                $data['body']
            );
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'sms');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'sms'])
            ->with('status', 'SMS sent to '.$sms->to_phone.'.');
    }

    /**
     * Retry a failed SMS.
     */
    public function retrySms(Request $request, MessagingSmsMessage $sms): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->retrySms($request->user(), $sms);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'sms');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'sms'])
            ->with('status', 'SMS resent successfully.');
    }

    /**
     * Send an email.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to_email' => ['required', 'email', 'max:160'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $email = $this->notificationsMessagingService->sendEmail(
                $request->user(),
                $data['to_email'],
                $data['subject'],
                $data['body']
            );
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'email');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'email'])
            ->with('status', 'Email sent to '.$email->to_email.'.');
    }

    /**
     * Create a task.
     */
    public function storeTask(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_at' => ['nullable', 'date'],
        ]);

        try {
            $task = $this->notificationsMessagingService->createTask(
                $request->user(),
                $data['title'],
                $data['body'] ?? null,
                $data['priority'],
                $data['due_at'] ?? null
            );
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'tasks');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'tasks'])
            ->with('status', 'Task created: '.$task->title);
    }

    /**
     * Start a task.
     */
    public function startTask(Request $request, MessagingTask $task): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->startTask($request->user(), $task);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'tasks');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'tasks'])
            ->with('status', 'Task started.');
    }

    /**
     * Complete a task.
     */
    public function completeTask(Request $request, MessagingTask $task): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->completeTask($request->user(), $task);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'tasks');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'tasks'])
            ->with('status', 'Task marked done.');
    }

    /**
     * Cancel a task.
     */
    public function cancelTask(Request $request, MessagingTask $task): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->cancelTask($request->user(), $task);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'tasks');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'tasks'])
            ->with('status', 'Task cancelled.');
    }

    /**
     * Reopen a task.
     */
    public function reopenTask(Request $request, MessagingTask $task): RedirectResponse
    {
        try {
            $this->notificationsMessagingService->reopenTask($request->user(), $task);
        } catch (BusinessLogicException $e) {
            return $this->fail($e, $request, 'tasks');
        }

        return redirect()
            ->route('messaging.index', ['tab' => 'tasks'])
            ->with('status', 'Task reopened.');
    }

    /**
     * @return array<string, string>
     */
    protected function preserveQuery(Request $request, string $tab): array
    {
        return array_filter([
            'tab' => $tab,
            'contact' => $request->input('contact'),
            'q' => $request->input('q'),
        ]);
    }

    protected function fail(BusinessLogicException $e, Request $request, string $tab): RedirectResponse
    {
        if ($e->getStatusCode() === 403) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('messaging.index', $this->preserveQuery($request, $tab))
            ->with('error', $e->getMessage());
    }
}
