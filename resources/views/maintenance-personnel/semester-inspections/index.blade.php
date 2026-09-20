@extends('layouts.maintenance-layout')

@section('title', 'Semester Inspections')

@section('content')
@php
    $tablesMissing = $tablesMissing ?? false;
@endphp

<div class="space-y-6">
    <div class="flex justify-end">
        <a
            href="{{ url('/maintenance/semester-inspections/create') }}"
            class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            New campaign
        </a>
    </div>

    @if ($tablesMissing)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Semester inspection tables are missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this module.
        </div>
    @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Active campaigns', 'hint' => '', 'value' => number_format($stats['active'])],
                ['label' => 'Due within 7 days', 'hint' => '', 'value' => number_format($stats['dueSoon'])],
                ['label' => 'Overdue', 'hint' => '', 'value' => number_format($stats['overdue'])],
            ],
        ])


        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <form method="GET" action="{{ url('/maintenance/semester-inspections') }}" class="flex flex-1 flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search title, year, semester"
                            class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                        >
                    </div>
                    <select
                        name="status"
                        class="h-10 rounded-xl border-0 bg-slate-50 px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                        onchange="this.form.submit()"
                    >
                        <option value="all" @selected(($status ?? 'all') === 'all')>All statuses</option>
                        @foreach (['Active', 'In Progress', 'Draft', 'Completed', 'Cancelled'] as $opt)
                            <option value="{{ $opt }}" @selected(($status ?? '') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Filter
                    </button>
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($campaigns as $campaign)
                    @php
                        $p = $campaign->progress;
                        $due = \Carbon\Carbon::parse($campaign->campaign_due_date)->startOfDay();
                        $today = now()->startOfDay();
                        $days = $today->diffInDays($due, false);
                        $statusTone = match ($campaign->campaign_status) {
                            'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'Cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
                            'In Progress' => 'bg-blue-50 text-blue-700 ring-blue-100',
                            'Draft' => 'bg-slate-50 text-slate-600 ring-slate-200',
                            default => 'bg-amber-50 text-amber-700 ring-amber-100',
                        };
                        $semester = trim((string) ($campaign->campaign_semester ?? ''));
                        $titleLower = mb_strtolower((string) ($campaign->campaign_title ?? ''));
                        $showSemester = $semester !== '' && ! str_contains($titleLower, mb_strtolower($semester));
                        $metaParts = array_values(array_filter([
                            $showSemester ? $semester : null,
                            $campaign->campaign_academic_year ?: null,
                            $campaign->scope_label ?: null,
                            'Due '.$due->format('M j, Y'),
                        ]));
                    @endphp
                    <a
                        href="{{ url('/maintenance/semester-inspections/'.$campaign->campaign_id) }}"
                        class="block px-4 py-4 transition hover:bg-slate-50/80 sm:px-5"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-base font-semibold text-slate-950">{{ $campaign->campaign_title }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $statusTone }}">
                                        {{ $campaign->campaign_status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ implode(' · ', $metaParts) }}
                                    @if (in_array($campaign->campaign_status, ['Active', 'In Progress'], true))
                                        @if ($days < 0)
                                            · <span class="font-medium text-rose-600">{{ abs((int) $days) }}d overdue</span>
                                        @elseif ($days === 0)
                                            · <span class="font-medium text-amber-600">Due today</span>
                                        @elseif ($days <= 7)
                                            · <span class="font-medium text-amber-600">In {{ (int) $days }}d</span>
                                        @endif
                                    @endif
                                </p>
                            </div>
                            <div class="w-full max-w-xs lg:w-56">
                                <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                                    <span>{{ $p['inspected'] }}/{{ $p['total'] }} inspected</span>
                                    <span>{{ $p['percent'] }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-[#0025cc]" style="width: {{ $p['percent'] }}%"></div>
                                </div>
                                @if ($p['defects'] > 0)
                                    <p class="mt-1 text-[11px] font-medium text-rose-600">{{ $p['defects'] }} defect(s) recorded</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-900">No semester inspections yet</p>
                        <p class="mt-1 text-sm text-slate-500">Create a campaign to check all school equipment for the semester.</p>
                        <a
                            href="{{ url('/maintenance/semester-inspections/create') }}"
                            class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Create campaign
                        </a>
                    </div>
                @endforelse
            </div>

            @if (method_exists($campaigns, 'links') && $campaigns->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $campaigns->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
