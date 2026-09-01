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

    $selectedTypeValues = collect($selectedTypes ?? [])->map(fn ($type) => (string) $type)->all();
    $hasActiveFilters = request()->filled('search')
        || request()->filled('date_from')
        || request()->filled('date_to')
        || ! empty($selectedTypeValues);
@endphp

<style>[x-cloak]{display:none!important}</style>

<div
    class="min-h-full"
    x-data="equipmentHistoryPage()"
    x-init="init()"
>
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
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Equipment History</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Track full equipment lifecycle — purchases, transfers, maintenance, reports, and disposal.
                    </p>
                </div>
            </div>

            <form
                method="GET"
                action="{{ url('/maintenance/equipment/history') }}"
                class="mt-5 space-y-4"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="relative min-w-[220px] flex-1">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <div class="relative">
                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            ></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Equipment, room, tag, serial..."
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Apply
                    </button>

                    @if ($hasActiveFilters)
                        <a
                            href="{{ url('/maintenance/equipment/history') }}"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                        >
                            Clear
                        </a>
                    @endif
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Event types</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($eventTypes as $typeKey => $typeLabel)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100">
                                <input
                                    type="checkbox"
                                    name="types[]"
                                    value="{{ $typeKey }}"
                                    @checked(in_array($typeKey, $selectedTypeValues, true))
                                    class="rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                >
                                {{ $typeLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Equipment</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Room</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Activity</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Last activity</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($equipment as $item)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                                        <i data-lucide="monitor" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $item->equipment_name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            {{ $item->equipment_category_name ?? 'Uncategorized' }}
                                            @if ($item->equipment_asset_tag)
                                                · {{ $item->equipment_asset_tag }}
                                            @endif
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
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-orange-50 px-2 py-1 font-medium text-orange-700">
                                        <i data-lucide="file-text" class="h-3 w-3"></i>
                                        {{ number_format($item->report_count) }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-2 py-1 font-medium text-sky-700">
                                        <i data-lucide="arrow-right-left" class="h-3 w-3"></i>
                                        {{ number_format($item->transfer_count ?? 0) }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 font-medium text-amber-700">
                                        <i data-lucide="wrench" class="h-3 w-3"></i>
                                        {{ number_format($item->maintenance_count ?? 0) }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $item->last_activity_at ? \Carbon\Carbon::parse($item->last_activity_at)->format('M d, Y') : '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openTimeline({{ (int) $item->equipment_id }}, @js($item->equipment_name), @js($item->room_name))"
                                        data-tooltip="View full history"
                                        aria-label="View full history"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200 active:scale-95"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>
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
                                    <p class="mt-4 text-sm font-semibold text-slate-900">No equipment history found</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Try a different search or date range.
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

    {{-- Full history modal --}}
    <div
        x-show="modalOpen"
        x-cloak
        @keydown.escape.window="closeModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeModal()" class="absolute inset-0"></div>

        <div
            x-show="modalOpen"
            x-transition
            class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5">
                <div class="min-w-0 pr-4">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </div>
                    <h2 class="truncate text-lg font-semibold tracking-tight text-slate-950" x-text="modalTitle"></h2>
                    <p class="mt-1 text-sm text-slate-500" x-text="modalSubtitle"></p>
                </div>
                <button
                    type="button"
                    @click="closeModal()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div x-show="loading" class="flex min-h-[240px] items-center justify-center text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i>
                        Loading equipment history...
                    </span>
                </div>

                <template x-if="!loading && profile">
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Equipment profile</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <template x-for="field in profileFields" :key="field.label">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="field.label"></p>
                                        <p class="mt-1 text-sm font-medium text-slate-900" x-text="field.value || '—'"></p>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-4" x-show="profile?.view_url">
                                <a
                                    :href="profile?.view_url"
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0025cc] hover:underline"
                                >
                                    Open full equipment profile
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                </a>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Timeline filters</p>
                                    <p class="mt-1 text-sm text-slate-500">Refine events shown below.</p>
                                </div>
                                <div class="flex flex-wrap items-end gap-2">
                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">From</label>
                                        <input type="date" x-model="modalDateFrom" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">To</label>
                                        <input type="date" x-model="modalDateTo" class="h-9 rounded-lg border border-slate-200 px-3 text-sm">
                                    </div>
                                    <button
                                        type="button"
                                        @click="reloadTimeline()"
                                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-blue-800"
                                    >
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <template x-for="(label, type) in eventTypeLabels" :key="type">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700">
                                        <input
                                            type="checkbox"
                                            :value="type"
                                            x-model="modalTypes"
                                            class="rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                        >
                                        <span x-text="label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">Activity timeline</p>
                                <p class="text-xs text-slate-500" x-text="`${events.length} event${events.length === 1 ? '' : 's'}`"></p>
                            </div>

                            <div x-show="events.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-900">No events in this range</p>
                                <p class="mt-1 text-sm text-slate-500">Try clearing the date or event filters.</p>
                            </div>

                            <div class="space-y-3" x-show="events.length > 0">
                                <template x-for="(event, index) in events" :key="`${event.type}-${event.occurred_at}-${index}`">
                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span
                                                        class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold"
                                                        :class="eventTypeClass(event.type)"
                                                        x-text="event.type_label"
                                                    ></span>
                                                    <p class="text-sm font-semibold text-slate-950" x-text="event.title"></p>
                                                </div>
                                                <p class="mt-2 text-sm text-slate-600" x-text="event.description"></p>
                                            </div>
                                            <span class="shrink-0 text-xs text-slate-500" x-text="formatDate(event.occurred_at)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="!loading && error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="error"></div>
            </div>

            <div class="flex shrink-0 items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button
                    type="button"
                    @click="closeModal()"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function equipmentHistoryPage() {
        return {
            modalOpen: false,
            loading: false,
            error: '',
            equipmentId: null,
            modalTitle: '',
            modalSubtitle: '',
            profile: null,
            profileFields: [],
            events: [],
            modalDateFrom: @js(request('date_from')),
            modalDateTo: @js(request('date_to')),
            modalTypes: @js($selectedTypeValues ?: array_keys($eventTypes)),
            eventTypeLabels: @js($eventTypes),
            pageDateFrom: @js(request('date_from')),
            pageDateTo: @js(request('date_to')),
            pageTypes: @js($selectedTypeValues),

            init() {
                if (window.lucide) window.lucide.createIcons();
            },

            eventTypeClass(type) {
                const map = {
                    acquisition: 'bg-emerald-50 text-emerald-700',
                    transfer: 'bg-sky-50 text-sky-700',
                    maintenance: 'bg-amber-50 text-amber-700',
                    report: 'bg-orange-50 text-orange-700',
                    disposal: 'bg-rose-50 text-rose-700',
                    created: 'bg-slate-100 text-slate-700',
                };

                return map[type] || 'bg-slate-100 text-slate-700';
            },

            formatDate(value) {
                if (!value) return '—';
                const date = new Date(value.replace(' ', 'T'));
                if (Number.isNaN(date.getTime())) return value;

                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            },

            formatMoney(value) {
                if (value === null || value === undefined || value === '') return null;
                return '₱' + Number(value).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },

            buildProfileFields(profile) {
                return [
                    { label: 'Category', value: profile.category },
                    { label: 'Room', value: profile.room_name },
                    { label: 'Inventory status', value: profile.inventory_status },
                    { label: 'Condition', value: profile.condition_status },
                    { label: 'Asset tag', value: profile.asset_tag },
                    { label: 'Serial number', value: profile.serial_number },
                    { label: 'Brand / model', value: [profile.brand, profile.model].filter(Boolean).join(' · ') || null },
                    { label: 'Location / zone', value: [profile.location, profile.placement_zone].filter(Boolean).join(' · ') || null },
                    { label: 'Purchase date', value: this.formatDate(profile.purchase_date) },
                    { label: 'Purchase cost', value: this.formatMoney(profile.purchase_cost) },
                    { label: 'Acquired date', value: this.formatDate(profile.acquired_date) },
                    { label: 'Warranty expiration', value: this.formatDate(profile.warranty_expiration) },
                    { label: 'Supplier', value: profile.supplier_name },
                    { label: 'Receiving report', value: profile.receiving_report_number ? `#${profile.receiving_report_number}` : null },
                    { label: 'Record created', value: this.formatDate(profile.created_at) },
                ];
            },

            async openTimeline(id, name, room) {
                this.equipmentId = id;
                this.modalTitle = name || 'Equipment';
                this.modalSubtitle = room ? `${room} · Full lifecycle history` : 'Full lifecycle history';
                this.modalDateFrom = this.pageDateFrom || '';
                this.modalDateTo = this.pageDateTo || '';
                this.modalTypes = this.pageTypes.length ? [...this.pageTypes] : Object.keys(this.eventTypeLabels);
                this.modalOpen = true;
                document.body.style.overflow = 'hidden';
                await this.reloadTimeline();
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            closeModal() {
                this.modalOpen = false;
                document.body.style.overflow = '';
            },

            async reloadTimeline() {
                if (!this.equipmentId) return;

                this.loading = true;
                this.error = '';

                const params = new URLSearchParams();
                if (this.modalDateFrom) params.set('from', this.modalDateFrom);
                if (this.modalDateTo) params.set('to', this.modalDateTo);
                this.modalTypes.forEach((type) => params.append('types[]', type));

                try {
                    const response = await fetch(
                        `/maintenance/equipment/timeline/${this.equipmentId}?${params.toString()}`,
                        { headers: { Accept: 'application/json' } }
                    );

                    if (!response.ok) {
                        throw new Error('Failed to load equipment history.');
                    }

                    const data = await response.json();
                    this.profile = data.equipment || null;
                    this.events = data.events || [];
                    this.profileFields = this.profile ? this.buildProfileFields(this.profile) : [];
                } catch (error) {
                    this.profile = null;
                    this.events = [];
                    this.profileFields = [];
                    this.error = error.message || 'Failed to load equipment history.';
                } finally {
                    this.loading = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
