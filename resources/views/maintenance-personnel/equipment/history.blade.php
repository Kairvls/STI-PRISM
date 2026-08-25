@extends('layouts.maintenance-layout')

@section('title', 'Equipment History')

@section('content')
@php
    $reportTrendCounts = collect($reportsMonthlyTrend)->pluck('count');
    $reportTrendMax = max(1, $reportTrendCounts->max() ?? 0);
    $reportTrendTotalPoints = max(1, $reportTrendCounts->count() - 1);

    $reportTrendPoints = collect($reportsMonthlyTrend)
        ->values()
        ->map(function ($item, $index) use ($reportTrendMax, $reportTrendTotalPoints) {
            $x = ($index / $reportTrendTotalPoints) * 300;
            $y = 90 - (($item['count'] / $reportTrendMax) * 75);

            return round($x, 2).','.round($y, 2);
        })
        ->implode(' ');

    $reportTrendAreaPoints = $reportTrendPoints.' 300,100 0,100';

    $statusStyles = [
        'Pending' => 'bg-orange-50 text-orange-700',
        'Processing' => 'bg-sky-50 text-sky-700',
        'Resolved' => 'bg-emerald-50 text-emerald-700',
        'Rejected' => 'bg-rose-50 text-rose-700',
        'For Replacement' => 'bg-amber-50 text-amber-700',
    ];

    $urgencyStyles = [
        'Urgent' => 'bg-rose-50 text-rose-700',
        'Normal' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<style>[x-cloak]{display:none!important}</style>

<div class="min-h-full">
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Dashboard --}}
    <div class="mb-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm">
        <div class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]">
            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Total Reports</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalReports) }}
                    </h2>
                    <p class="mt-3 text-sm">
                        @if ($reportsMonthlyPercentage === null)
                            <span class="font-semibold text-emerald-500">New activity</span>
                        @else
                            <span
                                class="font-semibold {{ $reportsMonthlyPercentage > 0 ? 'text-emerald-500' : ($reportsMonthlyPercentage < 0 ? 'text-red-500' : 'text-slate-500') }}"
                            >
                                {{ $reportsMonthlyPercentage > 0 ? '+' : '' }}{{ number_format($reportsMonthlyPercentage, 2) }}%
                            </span>
                        @endif
                        <span class="text-slate-500">From last month</span>
                    </p>
                </div>

                <div class="ml-6 h-20 w-40 shrink-0">
                    <svg viewBox="0 0 300 100" class="h-full w-full" fill="none">
                        <polygon
                            points="{{ $reportTrendAreaPoints }}"
                            fill="currentColor"
                            fill-opacity=".08"
                            class="text-slate-900"
                        />
                        <polyline
                            points="{{ $reportTrendPoints }}"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">With History</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($equipmentWithReports) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-slate-900">
                        {{ number_format($equipmentWithReportsPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">of all equipment</span>
                </p>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">Open Reports</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($openReports) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-amber-600">
                        {{ number_format($openReportsPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">of all reports</span>
                </p>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">Total Equipment</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($totalEquipment) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-blue-600">
                        {{ number_format($totalEquipment) }}
                    </span>
                    <span class="text-slate-500">tracked items</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Equipment History</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Review past reports linked to each equipment item.
                </p>
            </div>

            <form
                method="GET"
                action="{{ url('/maintenance/equipment/history') }}"
                class="flex flex-wrap items-center gap-2"
            >
                <div class="relative">
                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search equipment, room, or category..."
                        class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 sm:w-72"
                    >
                </div>

                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>

                @if (request()->filled('search'))
                    <a
                        href="{{ url('/maintenance/equipment/history') }}"
                        class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Equipment
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Room
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Reports
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Last reported
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($equipment as $item)
                        <tr
                            class="transition hover:bg-slate-50/80"
                            x-data="{ open: false }"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                                        <i data-lucide="monitor" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-950">
                                            {{ $item->equipment_name }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            {{ $item->equipment_category_name ?? 'Uncategorized' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $item->room_name ?? '—' }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                    {{ $item->equipment_inventory_status }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex min-w-9 items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-semibold text-slate-700">
                                        {{ number_format($item->report_count) }}
                                    </span>
                                    <span class="text-sm text-slate-500">
                                        {{ (int) $item->report_count === 1 ? 'report' : 'reports' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $item->last_reported_at ? \Carbon\Carbon::parse($item->last_reported_at)->format('M d, Y') : '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="open = true; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                        data-tooltip="View History"
                                        aria-label="View history"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200 active:scale-95"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>

                                {{-- History modal --}}
                                <div
                                    x-show="open"
                                    x-cloak
                                    @keydown.escape.window="open = false"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
                                >
                                    <div
                                        @click.self="open = false"
                                        class="absolute inset-0"
                                    ></div>

                                    <div
                                        x-show="open"
                                        x-transition:enter="transition duration-200 ease-out"
                                        x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
                                        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                                        x-transition:leave="transition duration-150 ease-in"
                                        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                                        x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"
                                        class="relative z-10 flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                    >
                                        <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5">
                                            <div class="min-w-0">
                                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                                    <i data-lucide="history" class="h-4 w-4"></i>
                                                </div>
                                                <h2 class="truncate text-lg font-semibold tracking-tight text-slate-950">
                                                    {{ $item->equipment_name }}
                                                </h2>
                                                <p class="mt-1 truncate text-sm text-slate-500">
                                                    {{ $item->room_name ?? 'No room' }}
                                                    ·
                                                    {{ number_format($item->report_count) }}
                                                    {{ (int) $item->report_count === 1 ? 'report' : 'reports' }}
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                @click="open = false"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                aria-label="Close modal"
                                            >
                                                <i data-lucide="x" class="h-5 w-5"></i>
                                            </button>
                                        </div>

                                        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">
                                            @forelse ($item->history as $row)
                                                @php
                                                    $statusClass = $statusStyles[$row->report_current_status] ?? 'bg-slate-100 text-slate-600';
                                                    $urgencyClass = $urgencyStyles[$row->report_urgency_level] ?? 'bg-slate-100 text-slate-600';
                                                @endphp
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-slate-950">
                                                                #{{ $row->report_id }}
                                                                ·
                                                                {{ $row->report_suggested_issue ?: 'No issue named' }}
                                                            </p>
                                                            <p class="mt-1 text-xs text-slate-500">
                                                                {{ $row->reporter_full_name ?? 'Unknown reporter' }}
                                                            </p>
                                                        </div>
                                                        <span class="shrink-0 text-xs text-slate-500">
                                                            {{ \Carbon\Carbon::parse($row->report_submitted_at)->format('M d, Y') }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusClass }}">
                                                            {{ $row->report_current_status }}
                                                        </span>
                                                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $urgencyClass }}">
                                                            {{ $row->report_urgency_level }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="flex min-h-[220px] flex-col items-center justify-center text-center">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400">
                                                        <i data-lucide="inbox" class="h-5 w-5"></i>
                                                    </div>
                                                    <p class="mt-4 text-sm font-semibold text-slate-900">
                                                        No reports yet
                                                    </p>
                                                    <p class="mt-1 text-sm text-slate-500">
                                                        Reports for this equipment will appear here.
                                                    </p>
                                                </div>
                                            @endforelse
                                        </div>

                                        <div class="flex shrink-0 items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                                            <button
                                                type="button"
                                                @click="open = false"
                                                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400">
                                        <i data-lucide="inbox" class="h-5 w-5"></i>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-900">
                                        No equipment history found
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Try a different search, or wait for reports to be submitted.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipment->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $equipment->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
