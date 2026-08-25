@extends("layouts.maintenance-layout")

@section("title", "Activity History")

@section("content")

@php
    $eqField = 'h-9 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $hasFilters = request()->filled('search')
        || request()->filled('module')
        || request()->filled('date_from')
        || request()->filled('date_to');

    $moduleTone = function ($module) {
        return match ($module) {
            "Reports" => ["bg-blue-50 text-blue-600 ring-blue-100", "border-blue-100 bg-blue-50 text-blue-700"],
            "Equipment" => ["bg-indigo-50 text-indigo-600 ring-indigo-100", "border-indigo-100 bg-indigo-50 text-indigo-700"],
            "Schedules" => ["bg-amber-50 text-amber-600 ring-amber-100", "border-amber-100 bg-amber-50 text-amber-700"],
            "Borrowing" => ["bg-sky-50 text-sky-600 ring-sky-100", "border-sky-100 bg-sky-50 text-sky-700"],
            "Infrastructure" => ["bg-emerald-50 text-emerald-600 ring-emerald-100", "border-emerald-100 bg-emerald-50 text-emerald-700"],
            default => ["bg-slate-50 text-slate-500 ring-slate-200/80", "border-slate-200 bg-slate-50 text-slate-600"],
        };
    };
@endphp

    <div class="space-y-6">
        <header>
            <div class="mb-4 flex items-center gap-2 text-sm text-slate-400">
                <a
                    href="{{ url('/maintenance/dashboard') }}"
                    class="transition hover:text-slate-700"
                >
                    Dashboard
                </a>
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                <span class="font-medium text-slate-600">Activity history</span>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    {{ number_format($activities->total()) }}
                    {{ Str::plural("activity", $activities->total()) }} recorded
                </p>

                <a
                    href="{{ url('/maintenance/dashboard') }}"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back
                </a>
            </div>
        </header>

        <form
            id="activity-filters"
            method="GET"
            action="{{ route('maintenance.activities.index') }}"
            class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5"
        >
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_180px_170px_170px_auto]">
                <div class="relative">
                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search activities..."
                        class="{{ $eqField }} pl-10"
                    >
                </div>

                <select name="module" class="{{ $eqField }}">
                    <option value="">All modules</option>
                    @foreach ($activityModules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>
                            {{ $module }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="{{ $eqField }}"
                >

                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="{{ $eqField }}"
                >

                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0
                                items-center justify-center gap-2
                                rounded-lg bg-[#0025cc] px-4
                                text-sm font-semibold text-white
                                transition hover:bg-blue-800"
                >
                            <i
                                data-lucide="sliders-horizontal"
                                class="h-4 w-4"
                            ></i>
                    Apply
                </button>
            </div>

            @if ($hasFilters)
                <div class="mt-3">
                    <a
                        href="{{ route('maintenance.activities.index') }}"
                        class="text-xs font-semibold text-slate-500 transition hover:text-slate-900"
                    >
                        Clear filters
                    </a>
                </div>
            @endif
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @forelse ($activities as $activity)
                @php
                    $module = $activity->audit_log_module ?? "";
                    [$iconClass, $pillClass] = $moduleTone($module);
                @endphp

                <div class="flex gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 transition hover:bg-slate-50/70">
                    <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $iconClass }}">
                        <i data-lucide="{{ $activity->icon }}" class="h-5 w-5"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">
                                        {{ $activity->audit_log_action }}
                                    </h3>

                                    @if ($module)
                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $pillClass }}">
                                            {{ $module }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ $activity->audit_log_description ?? "Activity recorded." }}
                                </p>
                            </div>

                            @if ($activity->url)
                                <a
                                    href="{{ $activity->url }}"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-white hover:text-[#0025cc]"
                                >
                                    View
                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                </a>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                                {{ \Carbon\Carbon::parse($activity->audit_log_created_at)->format("M d, Y · h:i A") }}
                            </span>

                            @if ($activity->audit_log_reference_id)
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="hash" class="h-3.5 w-3.5"></i>
                                    Reference #{{ $activity->audit_log_reference_id }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-200/80">
                        <i data-lucide="history" class="h-6 w-6"></i>
                    </div>
                    <h3 class="font-semibold text-slate-800">No activities found</h3>
                    <p class="mt-1 max-w-sm text-sm text-slate-400">
                        {{ $hasFilters ? "Try adjusting your filters to see more results." : "Your recorded maintenance actions will appear here." }}
                    </p>
                    @if ($hasFilters)
                        <a
                            href="{{ route('maintenance.activities.index') }}"
                            class="mt-4 text-sm font-semibold text-[#0025cc] hover:text-blue-800"
                        >
                            Clear filters
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="mt-2">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

@endsection
