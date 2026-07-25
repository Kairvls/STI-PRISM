@extends("layouts.maintenance-layout")

@section("title", "Activity History")

@section("content")

    <div class="mx-auto max-w-7xl">

        {{-- ===================================================== --}}
        {{-- PAGE HEADER --}}
        {{-- ===================================================== --}}

        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-gray-400"
                >
                    Maintenance
                </p>

                <h1
                    class="text-3xl font-bold tracking-tight text-gray-900"
                >
                    Activity History
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Review your recorded maintenance actions across PRISM.
                </p>
            </div>

            <div class="text-sm text-gray-400">
                {{ $activities->total() }}
                {{ Str::plural('activity', $activities->total()) }}
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- FILTERS --}}
        {{-- ===================================================== --}}

        <form
            id="activity-filters"
            method="GET"
            action="{{ route('maintenance.activities.index') }}"
            class="mb-6 rounded-2xl border border-gray-200 bg-white p-4"
        >

            <div
                class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_180px_170px_170px_auto]"
            >

                {{-- SEARCH --}}

                <div class="relative">

                    <i
                        data-lucide="search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                    ></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search activities..."
                        class="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-gray-400"
                    >

                </div>


                {{-- MODULE --}}

                <select
                    name="module"
                    class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none"
                >
                    <option value="">
                        All Modules
                    </option>

                    @foreach ($activityModules as $module)

                        <option
                            value="{{ $module }}"
                            @selected(request('module') === $module)
                        >
                            {{ $module }}
                        </option>

                    @endforeach
                </select>


                {{-- DATE FROM --}}

                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none"
                >


                {{-- DATE TO --}}

                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none"
                >


                {{-- APPLY --}}

                <button
                    type="submit"
                    class="rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    Apply
                </button>

            </div>


            {{-- RESET FILTERS --}}

            @if (
                request()->filled('search') ||
                request()->filled('module') ||
                request()->filled('date_from') ||
                request()->filled('date_to')
            )

                <div class="mt-3">

                    <a
                        href="{{ route('maintenance.activities.index') }}"
                        class="text-xs font-semibold text-gray-500 hover:text-gray-900"
                    >
                        Clear filters
                    </a>

                </div>

            @endif

        </form>


        {{-- ===================================================== --}}
        {{-- ACTIVITY TIMELINE --}}
        {{-- ===================================================== --}}

        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
        >

            @forelse ($activities as $activity)

                <div
                    class="flex gap-4 border-b border-gray-100 px-5 py-5 last:border-b-0"
                >

                    {{-- ICON --}}

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600"
                    >
                        <i
                            data-lucide="{{ $activity->icon }}"
                            class="h-5 w-5"
                        ></i>
                    </div>


                    {{-- ACTIVITY --}}

                    <div class="min-w-0 flex-1">

                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                        >

                            <div>

                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >

                                    <h3
                                        class="font-semibold text-gray-900"
                                    >
                                        {{ $activity->audit_log_action }}
                                    </h3>

                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500"
                                    >
                                        {{ $activity->audit_log_module }}
                                    </span>

                                </div>


                                <p
                                    class="mt-1 text-sm leading-6 text-gray-500"
                                >
                                    {{
                                        $activity->audit_log_description
                                        ?? 'Activity recorded.'
                                    }}
                                </p>

                            </div>


                            {{-- VIEW --}}

                            @if ($activity->url)

                                <a
                                    href="{{ $activity->url }}"
                                    class="shrink-0 text-sm font-semibold text-gray-500 transition hover:text-gray-900"
                                >
                                    View
                                </a>

                            @endif

                        </div>


                        {{-- METADATA --}}

                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-400"
                        >

                            <span
                                class="inline-flex items-center gap-1.5"
                            >
                                <i
                                    data-lucide="clock-3"
                                    class="h-3.5 w-3.5"
                                ></i>

                                {{
                                    \Carbon\Carbon::parse(
                                        $activity->audit_log_created_at
                                    )->format('M d, Y · h:i A')
                                }}
                            </span>


                            @if ($activity->audit_log_reference_id)

                                <span>
                                    Reference
                                    #{{ $activity->audit_log_reference_id }}
                                </span>

                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div
                    class="flex flex-col items-center justify-center px-6 py-16 text-center"
                >

                    <div
                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400"
                    >
                        <i
                            data-lucide="history"
                            class="h-6 w-6"
                        ></i>
                    </div>

                    <h3 class="font-semibold text-gray-800">
                        No activities found
                    </h3>

                    <p class="mt-1 max-w-sm text-sm text-gray-400">
                        Your recorded maintenance actions will appear here.
                    </p>

                </div>

            @endforelse

        </div>


        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ===================================================== --}}

        @if ($activities->hasPages())

            <div class="mt-6">
                {{ $activities->links() }}
            </div>

        @endif

    </div>

@endsection