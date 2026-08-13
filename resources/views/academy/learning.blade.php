<x-dashboard-layout
    title="Agricultural Learning Academy"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Learning'],
        ['label' => 'Academy'],
    ]"
>
    <x-page-header
        title="Agricultural Learning Academy"
        description="Build practical farming skills with featured courses and guided learning journeys."
    >
        <x-slot:actions>
            <form method="GET" action="{{ $actions['filter_url'] }}" class="flex items-center gap-2">
                <label class="sr-only" for="level-filter">Level</label>
                <select
                    id="level-filter"
                    name="level"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-cyra-line bg-white px-3 py-2 text-sm font-semibold text-cyra-ink focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                    <option value="">All levels</option>
                    @foreach ($levelOptions as $option)
                        <option value="{{ $option }}" @selected($levelFilter === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/50 px-4 py-3 text-sm text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-2 grid grid-cols-3 gap-3" aria-label="Learning stats">
        <article class="rounded-2xl bg-white p-3 text-center shadow-sm ring-1 ring-cyra-line sm:p-4">
            <p class="text-xs font-medium text-cyra-muted">Enrolled</p>
            <p class="mt-1 text-xl font-extrabold text-cyra-forest">{{ $stats['enrolled'] }}</p>
        </article>
        <article class="rounded-2xl bg-white p-3 text-center shadow-sm ring-1 ring-cyra-line sm:p-4">
            <p class="text-xs font-medium text-cyra-muted">In progress</p>
            <p class="mt-1 text-xl font-extrabold text-cyra-forest">{{ $stats['in_progress'] }}</p>
        </article>
        <article class="rounded-2xl bg-white p-3 text-center shadow-sm ring-1 ring-cyra-line sm:p-4">
            <p class="text-xs font-medium text-cyra-muted">Completed</p>
            <p class="mt-1 text-xl font-extrabold text-cyra-forest">{{ $stats['completed'] }}</p>
        </article>
    </div>

    <x-section-heading class="mt-6" title="Featured Courses" description="Curated learning paths for modern farming." />

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <section id="courses" class="xl:col-span-2" aria-labelledby="featured-courses-heading">
            <h2 id="featured-courses-heading" class="sr-only">Featured Courses</h2>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($courses as $course)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30">
                        <div class="aspect-[4/3] overflow-hidden bg-cyra-panel">
                            <img
                                src="{{ asset($course['image']) }}"
                                alt="{{ $course['title'] }}"
                                class="h-full w-full object-cover"
                            >
                        </div>
                        <div class="p-3.5">
                            <h3 class="text-sm font-extrabold leading-snug text-cyra-ink sm:text-base">
                                {{ $course['title'] }}
                            </h3>
                            <p @class([
                                'mt-1.5 text-sm font-semibold',
                                'text-cyra-forest' => $course['level_tone'] === 'green',
                                'text-cyra-muted' => $course['level_tone'] === 'muted',
                            ])>
                                {{ $course['level'] }}
                            </p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 font-semibold text-cyra-sun">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    {{ $course['rating'] }}
                                </span>
                                <span class="inline-flex items-center gap-1 font-medium text-cyra-muted">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $course['duration'] }}
                                </span>
                            </div>

                            @if ($course['completed'])
                                <p class="mt-3 text-xs font-bold text-cyra-forest">Completed</p>
                            @elseif ($course['enrolled'])
                                <form method="POST" action="{{ $course['advance_url'] }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                        Continue ({{ $course['progress'] }}%)
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ $course['enroll_url'] }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="course_id" value="{{ $course['id'] }}">
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-cyra-forest/30 bg-white px-3 py-2 text-xs font-bold text-cyra-forest hover:bg-cyra-mint/40">
                                        Enroll
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">
                <a
                    href="#library"
                    class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                >
                    View full journey
                </a>
            </div>
        </section>

        <section id="continue" aria-labelledby="continue-learning-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="continue-learning-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                    Continue Learning
                </h2>
                <p class="mt-4 text-base font-semibold text-cyra-ink">
                    {{ $continue['title'] }}
                </p>
                <p class="mt-3 text-sm font-medium text-cyra-muted">
                    {{ $continue['progress'] }}% Complete
                </p>
                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-cyra-line/80">
                    <div
                        class="h-full rounded-full bg-cyra-forest"
                        style="width: {{ $continue['progress'] }}%"
                    ></div>
                </div>
                @if ($continue['can_advance'] && $continue['advance_url'])
                    <form method="POST" action="{{ $continue['advance_url'] }}" class="mt-6">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                        >
                            Visit Now
                        </button>
                    </form>
                @else
                    <a
                        href="#library"
                        class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        Browse courses
                    </a>
                @endif
            </article>

            <article id="certificates" class="mt-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Certificates</h2>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @forelse ($certificates as $certificate)
                        <li class="py-3 first:pt-0 last:pb-0">
                            <p class="font-semibold text-cyra-ink">{{ $certificate['title'] }}</p>
                            <p class="text-xs text-cyra-muted">{{ $certificate['code'] }} · {{ $certificate['completed'] }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-cyra-muted">Complete a course to earn a certificate.</li>
                    @endforelse
                </ul>
            </article>
        </section>
    </div>

    <section id="library" class="mt-8" aria-labelledby="library-heading">
        <h2 id="library-heading" class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
            Course Library
        </h2>
        <p class="mt-1 text-sm text-cyra-muted">Crops, poultry, aquaculture, and livestock pathways.</p>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($library as $course)
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line">
                    <h3 class="font-extrabold text-cyra-ink">{{ $course['title'] }}</h3>
                    <p class="mt-1 text-sm text-cyra-muted">{{ $course['level'] }} · {{ $course['duration'] }} · ★ {{ $course['rating'] }}</p>
                    @if ($course['tags'])
                        <p class="mt-1 text-xs text-cyra-muted">{{ $course['tags'] }}</p>
                    @endif
                    @if ($course['summary'])
                        <p class="mt-2 text-sm text-cyra-ink/80">{{ $course['summary'] }}</p>
                    @endif

                    <div class="mt-3">
                        @if ($course['completed'])
                            <span class="text-xs font-bold text-cyra-forest">Completed · {{ $course['progress'] }}%</span>
                        @elseif ($course['enrolled'])
                            <form method="POST" action="{{ $course['advance_url'] }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">
                                    Continue ({{ $course['progress'] }}%)
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ $course['enroll_url'] }}">
                                @csrf
                                <input type="hidden" name="course_id" value="{{ $course['id'] }}">
                                <button type="submit" class="inline-flex items-center rounded-lg border border-cyra-forest/30 bg-white px-3 py-1.5 text-xs font-bold text-cyra-forest hover:bg-cyra-mint/40">
                                    Enroll
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-dashboard-layout>
