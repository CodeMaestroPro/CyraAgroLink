<x-dashboard-layout
    title="AI Assistant"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'AI Assistant'],
    ]"
>
    <div class="overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-cyra-line">
        <div class="flex min-h-[34rem] flex-col lg:min-h-[38rem] lg:flex-row">
            <x-ai.chat-sidebar :conversations="$conversations" />

            <section
                class="flex min-w-0 flex-1 flex-col"
                aria-label="CyraAI conversation"
                x-data="cyraAiChat({
                    conversationId: @js($activeConversation['id'] ?? null),
                    title: @js($activeConversation['title'] ?? 'New chat'),
                    messages: @js($activeConversation['messages'] ?? []),
                    messageUrl: @js(!empty($activeConversation['id']) ? route('ai.assistant.message', $activeConversation['id']) : null),
                    reloadUrl: @js(!empty($activeConversation['id']) ? route('ai.assistant', ['conversation' => $activeConversation['id']]) : route('ai.assistant')),
                    csrf: @js(csrf_token()),
                })"
            >
                <header class="flex items-center justify-between gap-3 border-b border-cyra-line px-4 py-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-extrabold text-cyra-forest sm:text-lg">
                                CyraAI Assistant
                            </h1>
                            <p class="truncate text-xs text-cyra-muted sm:text-sm">
                                Ask any farming question — crops, livestock, fish & more
                            </p>
                        </div>
                    </div>

                    <p
                        class="hidden max-w-[12rem] truncate text-right text-xs font-medium text-cyra-muted sm:block"
                        x-text="title"
                        x-show="title"
                    ></p>
                </header>

                @if (session('status'))
                    <div class="border-b border-cyra-line bg-cyra-mint/40 px-4 py-2 text-sm text-cyra-forest sm:px-6" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <div
                    x-show="error"
                    x-cloak
                    class="border-b border-cyra-line bg-red-50 px-4 py-2 text-sm text-red-700 sm:px-6"
                    role="alert"
                    x-text="error"
                ></div>

                <div
                    class="flex-1 space-y-5 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6"
                    x-ref="thread"
                >
                    <template x-for="(message, index) in messages" :key="index">
                        <div>
                            <template x-if="message.role === 'user'">
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] rounded-2xl rounded-br-md bg-cyra-forest px-4 py-3 text-sm leading-relaxed text-white shadow-sm sm:max-w-[70%]" x-text="message.body"></div>
                                </div>
                            </template>

                            <template x-if="message.role !== 'user' && message.type === 'diagnosis'">
                                <div class="flex gap-3">
                                    <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1 rounded-2xl rounded-tl-md bg-cyra-surface/80 p-4 text-sm leading-relaxed text-cyra-ink ring-1 ring-cyra-line/80 sm:p-5">
                                        <p>
                                            <span x-text="message.intro"></span>
                                            <span class="font-extrabold text-cyra-forest" x-text="message.diagnosis"></span>
                                        </p>
                                        <p class="mt-3 font-extrabold" x-text="message.recommendations_title"></p>
                                        <ul class="mt-2 space-y-1.5">
                                            <template x-for="item in (message.recommendations || [])" :key="item">
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-cyra-forest"></span>
                                                    <span x-text="item"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </template>

                            <template x-if="message.role !== 'user' && message.type !== 'diagnosis'">
                                <div class="flex gap-3">
                                    <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                                        </svg>
                                    </span>
                                    <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-cyra-surface/80 px-4 py-3 text-sm leading-relaxed text-cyra-ink ring-1 ring-cyra-line/80 sm:max-w-[75%]" x-text="message.body"></div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- ChatGPT-style thinking loader --}}
                    <div x-show="loading" x-cloak class="flex gap-3" aria-live="polite" aria-label="CyraAI is thinking">
                        <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm">
                            <svg class="h-4 w-4 animate-pulse" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                            </svg>
                        </span>
                        <div class="inline-flex items-center gap-3 rounded-2xl rounded-tl-md bg-cyra-surface/80 px-4 py-3 ring-1 ring-cyra-line/80">
                            <span class="text-sm text-cyra-muted">CyraAI is thinking</span>
                            <span class="inline-flex items-center gap-1" aria-hidden="true">
                                <span class="cyra-ai-dot h-1.5 w-1.5 rounded-full bg-cyra-forest"></span>
                                <span class="cyra-ai-dot h-1.5 w-1.5 rounded-full bg-cyra-forest"></span>
                                <span class="cyra-ai-dot h-1.5 w-1.5 rounded-full bg-cyra-forest"></span>
                            </span>
                        </div>
                    </div>

                    <div id="chat-end" x-ref="chatEnd"></div>
                </div>

                @if (! empty($activeConversation['id']))
                    <form
                        class="border-t border-cyra-line p-4 sm:p-5"
                        @submit.prevent="send"
                    >
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            <label class="relative min-w-0 flex-1">
                                <span class="sr-only">Ask anything about farming</span>
                                <input
                                    type="text"
                                    x-model="prompt"
                                    x-ref="input"
                                    :disabled="loading"
                                    placeholder="Ask anything about farming..."
                                    required
                                    maxlength="2000"
                                    autocomplete="off"
                                    class="w-full rounded-2xl border-cyra-line bg-cyra-surface/60 py-3 pl-4 pr-11 text-sm text-cyra-ink placeholder:text-cyra-muted focus:border-cyra-forest focus:ring-cyra-forest disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-cyra-muted">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4a4 4 0 0 1 4 4v4a4 4 0 0 1-8 0V8a4 4 0 0 1 4-4zM6 12a6 6 0 0 0 12 0M12 18v2"/>
                                    </svg>
                                </span>
                            </label>

                            <button
                                type="submit"
                                :disabled="loading || !prompt.trim()"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cyra-forest text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                aria-label="Send message"
                            >
                                <svg x-show="!loading" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                                </svg>
                                <svg x-cloak x-show="loading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    </div>

    <style>
        .cyra-ai-dot {
            animation: cyra-ai-bounce 1.2s ease-in-out infinite;
        }
        .cyra-ai-dot:nth-child(2) { animation-delay: 0.15s; }
        .cyra-ai-dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes cyra-ai-bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.35; }
            40% { transform: translateY(-4px); opacity: 1; }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cyraAiChat', (config) => ({
                conversationId: config.conversationId,
                title: config.title || 'New chat',
                messages: Array.isArray(config.messages) ? config.messages : [],
                messageUrl: config.messageUrl,
                reloadUrl: config.reloadUrl,
                csrf: config.csrf,
                prompt: '',
                loading: false,
                error: '',

                init() {
                    this.$nextTick(() => this.scrollToEnd());
                },

                scrollToEnd() {
                    const el = this.$refs.thread;
                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                },

                async send() {
                    const text = (this.prompt || '').trim();
                    if (!text || this.loading || !this.messageUrl) {
                        return;
                    }

                    this.error = '';
                    this.prompt = '';
                    this.messages.push({ role: 'user', type: 'text', body: text });
                    this.loading = true;
                    this.$nextTick(() => this.scrollToEnd());

                    try {
                        const response = await fetch(this.messageUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ prompt: text }),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const message = data?.message || data?.errors?.prompt?.[0] || 'Could not get a reply. Please try again.';
                            throw new Error(message);
                        }

                        if (data.title) {
                            this.title = data.title;
                        }

                        if (data.reply) {
                            this.messages.push(data.reply);
                        }
                    } catch (e) {
                        this.error = e?.message || 'Could not get a reply. Please try again.';
                        // Keep the user message visible; they can retry.
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            this.scrollToEnd();
                            this.$refs.input?.focus();
                        });
                    }
                },
            }));
        });
    </script>
</x-dashboard-layout>
