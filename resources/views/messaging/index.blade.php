@php
    $featuredAnnouncement = $announcements[0] ?? null;
    $otherAnnouncements = array_slice($announcements, 1);
@endphp

<x-dashboard-layout
    title="Notifications & Messaging"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Notifications & Messaging'],
    ]"
>
    <x-page-header
        title="Notifications & Messaging"
        description="Your CyraAgroLink communications hub — alerts, chats, broadcasts, and follow-ups."
    />

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/60 px-4 py-3 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-2xl bg-white ring-1 ring-cyra-line">
        <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
            <x-messaging.hub-nav :active="$tab" :items="$tabs" />
        </div>

        @if ($tab === 'notifications')
            <section aria-labelledby="all-notifications-heading">
                <div class="flex flex-col gap-3 border-b border-cyra-line px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 id="all-notifications-heading" class="font-display text-xl font-extrabold text-cyra-ink">All Notifications</h2>
                        <p class="mt-1 text-sm text-cyra-muted">Payments, orders, weather, and system alerts.</p>
                    </div>
                    <form method="POST" action="{{ $actions['mark_all_read'] }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                            Mark all as read
                        </button>
                    </form>
                </div>

                <div class="space-y-3 p-4 sm:p-6">
                    @forelse ($notifications as $notification)
                        <div class="rounded-2xl ring-1 ring-cyra-line">
                            <x-messaging.notification-card
                                :title="$notification['title']"
                                :body="$notification['body']"
                                :time="$notification['time']"
                                :tone="$notification['tone']"
                                :unread="$notification['unread']"
                                :flush="true"
                            />
                            @if ($notification['unread'])
                                <div class="border-t border-cyra-line px-4 py-2.5">
                                    <form method="POST" action="{{ $notification['mark_read_url'] }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-cyra-mint px-3 py-2 text-xs font-bold text-cyra-forest">Mark read</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-cyra-muted">No notifications yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($tab === 'messages')
            <section aria-label="Conversations">
                <x-messaging.messages-panel
                    :contacts="$contacts"
                    :thread="$activeThread"
                    :search="$search"
                    :send-url="$actions['send_message']"
                />
            </section>
        @endif

        @if ($tab === 'announcements')
            <section aria-labelledby="announcements-heading">
                <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="announcements-heading" class="font-display text-xl font-extrabold text-cyra-ink">Announcements</h2>
                            <p class="mt-1 text-sm text-cyra-muted">Platform broadcasts for your network — read first, publish when ready.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($announcementStats as $stat)
                                <div class="rounded-full bg-cyra-surface px-3 py-1.5 text-sm ring-1 ring-cyra-line">
                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-cyra-muted">{{ $stat['label'] }}</span>
                                    <span class="ml-1 font-extrabold text-cyra-forest">{{ $stat['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-6 p-4 sm:p-6">
                    {{-- Compose first, full width, never overlapping --}}
                    <form method="POST" action="{{ $actions['store_announcement'] }}" class="rounded-2xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line sm:p-5">
                        @csrf
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyra-forest">Compose</p>
                        <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">Publish update</h3>
                        <p class="mt-1 text-sm text-cyra-muted">Keep it short, specific, and actionable.</p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="announcement-title" class="block text-xs font-bold text-cyra-muted">Title</label>
                                <input
                                    id="announcement-title"
                                    name="title"
                                    required
                                    maxlength="160"
                                    value="{{ old('title') }}"
                                    placeholder="e.g. Cold-chain checklist this week"
                                    class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >
                            </div>
                            <div class="md:col-span-2">
                                <label for="announcement-body" class="block text-xs font-bold text-cyra-muted">Message</label>
                                <textarea
                                    id="announcement-body"
                                    name="body"
                                    required
                                    maxlength="500"
                                    rows="4"
                                    placeholder="What should people know or do?"
                                    class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                                >{{ old('body') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green sm:w-auto">
                                Publish announcement
                            </button>
                        </div>
                    </form>

                    {{-- Featured --}}
                    @if ($featuredAnnouncement)
                        <article class="rounded-2xl bg-cyra-forest p-5 text-white sm:p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide">Featured</span>
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-semibold">{{ $featuredAnnouncement['audience'] }}</span>
                                @if ($featuredAnnouncement['acknowledged'])
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold">Acknowledged</span>
                                @endif
                            </div>
                            <h3 class="mt-4 font-display text-xl font-extrabold sm:text-2xl">{{ $featuredAnnouncement['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/90 sm:text-base">{{ $featuredAnnouncement['body'] }}</p>
                            <p class="mt-3 text-xs text-white/70">Published {{ $featuredAnnouncement['time'] }}</p>
                            <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                                @unless ($featuredAnnouncement['acknowledged'])
                                    <form method="POST" action="{{ $featuredAnnouncement['acknowledge_url'] }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest hover:bg-cyra-mint sm:w-auto">Acknowledge</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ $featuredAnnouncement['dismiss_url'] }}">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-white/30 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10 sm:w-auto">Dismiss</button>
                                </form>
                            </div>
                        </article>
                    @endif

                    {{-- Earlier list --}}
                    @if (count($otherAnnouncements) > 0)
                        <div class="rounded-2xl ring-1 ring-cyra-line">
                            <div class="border-b border-cyra-line px-4 py-3 sm:px-5">
                                <h3 class="font-display text-sm font-extrabold text-cyra-ink">Earlier updates</h3>
                            </div>
                            @foreach ($otherAnnouncements as $announcement)
                                <article class="border-b border-cyra-line px-4 py-4 last:border-b-0 sm:px-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="font-semibold uppercase tracking-wide text-cyra-forest">{{ $announcement['audience'] }}</span>
                                                @if ($announcement['acknowledged'])
                                                    <span class="rounded-full bg-cyra-mint px-2 py-0.5 font-bold text-cyra-forest">Acknowledged</span>
                                                @endif
                                                <span class="text-cyra-muted">{{ $announcement['time'] }}</span>
                                            </div>
                                            <h3 class="mt-1.5 font-display text-base font-extrabold text-cyra-ink">{{ $announcement['title'] }}</h3>
                                            <p class="mt-1 text-sm text-cyra-muted">{{ $announcement['body'] }}</p>
                                        </div>
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            @unless ($announcement['acknowledged'])
                                                <form method="POST" action="{{ $announcement['acknowledge_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-3.5 py-2 text-xs font-bold text-white hover:bg-cyra-green sm:w-auto">Acknowledge</button>
                                                </form>
                                            @endunless
                                            <form method="POST" action="{{ $announcement['dismiss_url'] }}">
                                                @csrf
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-cyra-line px-3.5 py-2 text-xs font-bold text-cyra-muted hover:bg-cyra-surface sm:w-auto">Dismiss</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @elseif (! $featuredAnnouncement)
                        <p class="py-8 text-center text-sm text-cyra-muted">No announcements at this time.</p>
                    @endif
                </div>
            </section>
        @endif

        @if ($tab === 'sms')
            <section aria-labelledby="sms-heading">
                <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
                    <h2 id="sms-heading" class="font-display text-xl font-extrabold text-cyra-ink">SMS</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Quick operational texts for drivers, buyers, and field teams.</p>
                </div>
                <div class="space-y-6 p-4 sm:p-6">
                    <form method="POST" action="{{ $actions['send_sms'] }}" class="rounded-2xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line sm:p-5">
                        @csrf
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyra-forest">Compose</p>
                        <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">Compose SMS</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="sms-phone" class="block text-xs font-bold text-cyra-muted">To phone</label>
                                <input id="sms-phone" name="to_phone" required maxlength="32" value="{{ old('to_phone') }}" placeholder="+2348012345678" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label for="sms-body" class="block text-xs font-bold text-cyra-muted">Message</label>
                                <textarea id="sms-body" name="body" required maxlength="480" rows="4" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">{{ old('body') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">Send SMS</button>
                        </div>
                    </form>

                    <div class="rounded-2xl ring-1 ring-cyra-line">
                        @forelse ($smsMessages as $sms)
                            <article class="border-b border-cyra-line px-4 py-4 last:border-b-0 sm:px-5">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full bg-cyra-mint px-2 py-0.5 font-bold text-cyra-forest">{{ $sms['direction'] === 'outbound' ? 'Outbound' : 'Inbound' }}</span>
                                    <span class="text-cyra-muted">{{ $sms['status'] }} · {{ $sms['time'] }}</span>
                                </div>
                                <p class="mt-2 break-all text-xs font-bold text-cyra-ink">{{ $sms['direction'] === 'outbound' ? 'To' : 'From' }} {{ $sms['peer'] }}</p>
                                <p class="mt-1 text-sm text-cyra-muted">{{ $sms['body'] }}</p>
                                @if ($sms['can_retry'])
                                    <form method="POST" action="{{ $sms['retry_url'] }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-cyra-forest px-3.5 py-2 text-xs font-bold text-white">Retry</button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-cyra-muted">No SMS messages yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

        @if ($tab === 'email')
            <section aria-labelledby="email-heading">
                <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
                    <h2 id="email-heading" class="font-display text-xl font-extrabold text-cyra-ink">Email</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Longer confirmations and partner follow-ups.</p>
                </div>
                <div class="space-y-6 p-4 sm:p-6">
                    <form method="POST" action="{{ $actions['send_email'] }}" class="rounded-2xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line sm:p-5">
                        @csrf
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyra-forest">Compose</p>
                        <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">Compose email</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="email-to" class="block text-xs font-bold text-cyra-muted">To</label>
                                <input id="email-to" type="email" name="to_email" required maxlength="160" value="{{ old('to_email') }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label for="email-subject" class="block text-xs font-bold text-cyra-muted">Subject</label>
                                <input id="email-subject" name="subject" required maxlength="200" value="{{ old('subject') }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label for="email-body" class="block text-xs font-bold text-cyra-muted">Body</label>
                                <textarea id="email-body" name="body" required maxlength="5000" rows="5" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">{{ old('body') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">Send email</button>
                        </div>
                    </form>

                    <div class="rounded-2xl ring-1 ring-cyra-line">
                        @forelse ($emailMessages as $email)
                            <article class="border-b border-cyra-line px-4 py-4 last:border-b-0 sm:px-5">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full bg-cyra-mint px-2 py-0.5 font-bold text-cyra-forest">{{ $email['direction'] === 'outbound' ? 'Sent' : 'Received' }}</span>
                                    <span class="text-cyra-muted">{{ $email['status'] }} · {{ $email['time'] }}</span>
                                </div>
                                <p class="mt-2 break-all text-xs font-semibold text-cyra-muted">{{ $email['direction'] === 'outbound' ? 'To' : 'From' }} {{ $email['peer'] }}</p>
                                <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">{{ $email['subject'] }}</h3>
                                <p class="mt-1 text-sm text-cyra-muted">{{ $email['body'] }}</p>
                            </article>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-cyra-muted">No email notifications yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

        @if ($tab === 'tasks')
            <section aria-labelledby="tasks-heading">
                <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
                    <h2 id="tasks-heading" class="font-display text-xl font-extrabold text-cyra-ink">Tasks</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Turn alerts and chats into clear next actions.</p>
                </div>
                <div class="space-y-6 p-4 sm:p-6">
                    <form method="POST" action="{{ $actions['store_task'] }}" class="rounded-2xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line sm:p-5">
                        @csrf
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyra-forest">Compose</p>
                        <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">New task</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="task-title" class="block text-xs font-bold text-cyra-muted">Title</label>
                                <input id="task-title" name="title" required maxlength="160" value="{{ old('title') }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </div>
                            <div>
                                <label for="task-body" class="block text-xs font-bold text-cyra-muted">Details</label>
                                <textarea id="task-body" name="body" maxlength="1000" rows="3" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">{{ old('body') }}</textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="task-priority" class="block text-xs font-bold text-cyra-muted">Priority</label>
                                    <select id="task-priority" name="priority" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                                        <option value="high" @selected(old('priority') === 'high')>High</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="task-due" class="block text-xs font-bold text-cyra-muted">Due date</label>
                                    <input id="task-due" type="date" name="due_at" value="{{ old('due_at') }}" class="mt-1.5 w-full rounded-xl border border-cyra-line bg-white px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                                </div>
                            </div>
                            <button type="submit" class="inline-flex rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">Add task</button>
                        </div>
                    </form>

                    <div class="rounded-2xl ring-1 ring-cyra-line">
                        @forelse ($tasks as $task)
                            <article class="border-b border-cyra-line px-4 py-4 last:border-b-0 sm:px-5">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full bg-cyra-surface px-2 py-0.5 font-bold text-cyra-ink ring-1 ring-cyra-line">{{ $task['status'] }}</span>
                                    <span class="rounded-full bg-cyra-mint px-2 py-0.5 font-bold text-cyra-forest">{{ $task['priority'] }}</span>
                                    <span class="text-cyra-muted">Due {{ $task['due'] }}</span>
                                </div>
                                <h3 class="mt-2 font-display text-base font-extrabold text-cyra-ink">{{ $task['title'] }}</h3>
                                @if ($task['body'])
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $task['body'] }}</p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($task['can_start'])
                                        <form method="POST" action="{{ $task['start_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-forest">Start</button>
                                        </form>
                                    @endif
                                    @if ($task['can_complete'])
                                        <form method="POST" action="{{ $task['complete_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl bg-cyra-forest px-3 py-2 text-xs font-bold text-white">Complete</button>
                                        </form>
                                    @endif
                                    @if ($task['can_cancel'])
                                        <form method="POST" action="{{ $task['cancel_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-muted">Cancel</button>
                                        </form>
                                    @endif
                                    @if ($task['can_reopen'])
                                        <form method="POST" action="{{ $task['reopen_url'] }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-forest">Reopen</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-cyra-muted">No pending tasks.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

        @if ($tab === 'activity')
            <section aria-labelledby="activity-heading">
                <div class="border-b border-cyra-line px-4 py-4 sm:px-6">
                    <h2 id="activity-heading" class="font-display text-xl font-extrabold text-cyra-ink">Activity Log</h2>
                    <p class="mt-1 text-sm text-cyra-muted">A single trail across wallet, chats, SMS, email, and tasks.</p>
                </div>
                <div class="space-y-3 p-4 sm:p-6">
                    @forelse ($activity as $item)
                        <article class="rounded-2xl p-4 ring-1 ring-cyra-line sm:p-5">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-cyra-forest">{{ $item['channel'] }}</p>
                            <h3 class="mt-1 font-display text-sm font-extrabold text-cyra-ink sm:text-base">{{ $item['title'] }}</h3>
                            <p class="mt-1 text-sm text-cyra-muted">{{ $item['body'] }}</p>
                            <p class="mt-2 text-xs text-cyra-muted">{{ $item['time'] }}</p>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-cyra-muted">No recent activity to show.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-dashboard-layout>
