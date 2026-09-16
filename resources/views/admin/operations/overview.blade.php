@extends('layouts.admin-layout')

@section('title', 'Operations Overview')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $primaryMetrics = [
        [
            'label' => 'Equipment',
            'value' => $stats['equipment_total'],
            'meta' => $stats['needs_maintenance'].' maintenance · '.$stats['for_replacement'].' replacement',
            'url' => route('admin.operations.equipment'),
        ],
        [
            'label' => 'Overdue schedules',
            'value' => $stats['overdue_schedules'],
            'meta' => 'Needs attention',
            'url' => route('admin.operations.schedules', ['filter' => 'overdue']),
            'alert' => (int) $stats['overdue_schedules'] > 0,
        ],
        [
            'label' => 'Open reports',
            'value' => $stats['open_reports'],
            'meta' => $stats['urgent_reports'].' urgent',
            'url' => route('admin.operations.reports'),
            'alert' => (int) $stats['urgent_reports'] > 0,
        ],
        [
            'label' => 'Open RIS',
            'value' => $stats['open_ris'],
            'meta' => '₱'.number_format((float) ($stats['open_ris_amount'] ?? 0), 0).' open · ₱'.number_format((float) ($stats['pending_admin_ris_amount'] ?? 0), 0).' pending review',
            'url' => route('admin.operations.procurement', ['filter' => 'open']),
        ],
    ];

    $previewPanels = [
        [
            'title' => 'Overdue schedules',
            'url' => route('admin.operations.schedules', ['filter' => 'overdue']),
            'total' => (int) $stats['overdue_schedules'],
            'empty' => 'No overdue schedules.',
            'items' => ($overdueSchedulesPreview ?? collect())->map(function ($row) {
                $due = ! empty($row->maintenance_schedule_next_date)
                    ? \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->startOfDay()
                    : null;
                $days = $due ? (int) $due->diffInDays(now()->startOfDay()) : null;

                return [
                    'title' => $row->equipment_name ?: ($row->maintenance_schedule_title ?: 'Schedule'),
                    'meta' => trim(($row->room_name ?: 'No room').($due ? ' · Due '.$due->format('M j') : '')),
                    'badge' => $days !== null ? $days.'d overdue' : 'Overdue',
                    'url' => route('admin.operations.schedules', ['filter' => 'overdue']),
                ];
            }),
        ],
        [
            'title' => 'Overdue borrows',
            'url' => route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']),
            'total' => (int) $stats['overdue_borrows'],
            'empty' => 'No overdue borrows.',
            'items' => ($overdueBorrowsPreview ?? collect())->map(function ($row) {
                $due = ! empty($row->borrowing_expected_return_date)
                    ? \Carbon\Carbon::parse($row->borrowing_expected_return_date)->startOfDay()
                    : null;
                $days = ($due && $due->lt(now()->startOfDay()))
                    ? (int) $due->diffInDays(now()->startOfDay())
                    : null;

                return [
                    'title' => $row->equipment_name ?: 'Equipment',
                    'meta' => trim(($row->borrowing_borrower_name ?: 'Unknown borrower').($due ? ' · Due '.$due->format('M j') : '')),
                    'badge' => $days !== null ? $days.'d overdue' : 'Overdue',
                    'url' => route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']),
                ];
            }),
        ],
        [
            'title' => 'Urgent reports',
            'url' => route('admin.operations.reports', ['filter' => 'urgent']),
            'total' => (int) $stats['urgent_reports'],
            'empty' => 'No urgent open reports.',
            'items' => ($urgentReportsPreview ?? collect())->map(function ($row) {
                $submitted = ! empty($row->report_submitted_at)
                    ? \Carbon\Carbon::parse($row->report_submitted_at)->startOfDay()
                    : null;
                $age = $submitted ? (int) $submitted->diffInDays(now()->startOfDay()) : null;
                $name = $row->equipment_name
                    ?: ($row->report_unlisted_equipment_name ?: ($row->report_suggested_issue ?: 'Report #'.$row->report_id));

                return [
                    'title' => $name,
                    'meta' => trim(($row->room_name ?: 'No room').' · '.($row->report_current_status ?: 'Open')),
                    'badge' => $age !== null ? ($age === 0 ? 'Today' : $age.'d open') : 'Urgent',
                    'url' => route('admin.operations.reports', ['filter' => 'urgent']),
                ];
            }),
        ],
        [
            'title' => 'Lifecycle horizon',
            'url' => route('admin.operations.equipment', ['filter' => 'lifecycle']),
            'total' => (int) $stats['lifecycle_alerts'],
            'empty' => 'No lifecycle alerts.',
            'items' => ($lifecyclePreview ?? collect())->map(function ($row) {
                $remaining = (int) ($row->years_remaining ?? 0);
                $badge = $remaining < 0
                    ? 'Overdue'
                    : ($remaining === 0 ? 'Replace this year' : '~'.$remaining.'y left');

                return [
                    'title' => $row->equipment_name ?: 'Equipment',
                    'meta' => ($row->room_name ?: 'No room').' · Age '.(int) ($row->age_years ?? 0).'y',
                    'badge' => $badge,
                    'url' => route('admin.operations.equipment.show', $row->equipment_id),
                ];
            }),
        ],
    ];

    $secondaryMetrics = [
        [
            'label' => 'Transfers · 30d',
            'value' => $stats['transfers_30d'],
            'url' => route('admin.operations.movements', ['tab' => 'transfers', 'filter' => 'recent']),
        ],
        [
            'label' => 'Overdue borrows',
            'value' => $stats['overdue_borrows'],
            'url' => route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']),
            'alert' => (int) $stats['overdue_borrows'] > 0,
        ],
        [
            'label' => 'Disposals',
            'value' => $stats['disposals_total'],
            'url' => route('admin.operations.movements', ['tab' => 'disposal']),
        ],
    ];

    $shortcuts = [
        [
            'label' => 'Lifecycle horizon',
            'url' => route('admin.operations.equipment', ['filter' => 'lifecycle']),
        ],
        [
            'label' => 'For replacement',
            'url' => route('admin.operations.equipment', ['filter' => 'replacement']),
        ],
        [
            'label' => 'Procurement pipeline',
            'url' => route('admin.operations.procurement'),
        ],
        [
            'label' => 'Review RIS',
            'url' => url('/admin/procurement-review'),
        ],
    ];
@endphp

<style>
    .cc-alert {
        color: #b45309;
    }

    .cc-form-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.1rem;
        min-width: 5.25rem;
        padding: 0.5rem 0.7rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        background: #fff;
        text-align: left;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .cc-form-chip:hover:not(:disabled) {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .cc-form-chip.is-current {
        border-color: #0025cc;
        background: rgba(0, 37, 204, 0.04);
    }

    .cc-form-chip:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    [x-cloak] { display: none !important; }
</style>

<div class="admin-page space-y-6" x-data="adminCommandCenterProcurement">
    {{-- Primary status metrics (alert color preserved) --}}
    <div class="pur-card">
        <div class="grid grid-cols-2 divide-gray-100 lg:grid-cols-4 lg:divide-x">
            @foreach($primaryMetrics as $metric)
                <a href="{{ $metric['url'] }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight {{ !empty($metric['alert']) ? 'cc-alert' : 'text-gray-950' }}">
                        {{ $metric['value'] }}
                    </p>
                    <p class="mt-1.5 text-xs text-gray-500">{{ $metric['meta'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Money --}}
    <div class="pur-card">
        <div class="grid divide-gray-100 sm:grid-cols-2 sm:divide-x">
            <a href="{{ route('admin.operations.procurement', ['filter' => 'open']) }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Open RIS value</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">₱{{ number_format((float) ($stats['open_ris_amount'] ?? 0), 2) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ (int) $stats['open_ris'] }} open procurement record{{ (int) $stats['open_ris'] === 1 ? '' : 's' }}</p>
            </a>
            <a href="{{ url('/admin/procurement-review?filter=pending') }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Pending Admin review</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">₱{{ number_format((float) ($stats['pending_admin_ris_amount'] ?? 0), 2) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ (int) $stats['awaiting_admin_ris'] }} RIS awaiting accept</p>
            </a>
        </div>
    </div>

    {{-- Latest procurement --}}
    <div class="pur-card">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950">Latest procurement workflow</h2>
                <p class="mt-1 text-xs text-gray-400">Open any available form in the chain — RIS, ATP, RFC/CA, Receiving, or Liquidation.</p>
            </div>
            <a href="{{ route('admin.operations.procurement') }}" class="text-xs font-semibold text-[#0025cc] transition hover:text-[#001fa8]">Open monitor</a>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse(($latestProcurement ?? collect()) as $row)
                @php
                    $pipeline = $row->pipeline ?? [];
                    $stages = $pipeline['stages'] ?? [];
                    $current = $pipeline['current_stage'] ?? 'ris';
                    $stageKeys = [
                        'ris' => 'RIS',
                        'atp' => 'ATP',
                        'rfc' => 'RFC/CA',
                        'rr' => 'Receiving',
                        'liq' => 'Liquidation',
                    ];
                    $amount = (float) ($row->ris_calculated_total ?? 0);
                    $formNo = \App\Support\RisWorkflow::formNumber($row);
                    $hint = $pipeline['current_hint'] ?? (\App\Support\RisWorkflow::statusLabel($row));
                    $readyCount = collect($stages)->filter(fn ($stage) => ! empty($stage['exists']) && ! empty($stage['id']))->count();
                @endphp
                <div class="space-y-4 px-5 py-5">
                    <div>
                        <p class="text-sm font-semibold text-gray-950">{{ $formNo }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ \Illuminate\Support\Str::limit($row->ris_purpose_description ?: 'No purpose noted', 72) }}
                            · ₱{{ number_format($amount, 2) }}
                            @if(!empty($row->created_by_name))
                                · {{ $row->created_by_name }}
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            Current: {{ strtoupper((string) $current) }}
                            · {{ $hint }}
                            · {{ $readyCount }} form{{ $readyCount === 1 ? '' : 's' }} available
                        </p>
                    </div>

                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Workflow forms</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($stageKeys as $key => $label)
                                @php
                                    $stage = $stages[$key] ?? null;
                                    $exists = ! empty($stage['exists']) && ! empty($stage['id']);
                                    $stageTitle = $exists
                                        ? ($stage['label'] ?? $label)
                                        : $label;
                                @endphp
                                <button
                                    type="button"
                                    class="cc-form-chip {{ $exists ? 'is-ready' : '' }} {{ $current === $key ? 'is-current' : '' }}"
                                    title="{{ $exists ? 'Open '.$stageTitle : $label.' not started yet' }}"
                                    @if($exists)
                                        data-doc-type="{{ $key }}"
                                        data-doc-id="{{ (int) $stage['id'] }}"
                                        data-doc-title="{{ $stageTitle }}"
                                        @click="openDoc($event.currentTarget.dataset.docType, Number($event.currentTarget.dataset.docId), $event.currentTarget.dataset.docTitle)"
                                    @else
                                        disabled
                                    @endif
                                >
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-gray-900">{{ $label }}</span>
                                    <span class="text-[11px] {{ $exists ? 'font-semibold text-gray-600' : 'text-gray-400' }}">
                                        {{ $exists ? 'Open form' : 'Not started' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-gray-400">Click any ready form above to preview the file.</p>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                            data-ris-id="{{ (int) $row->ris_id }}"
                            data-ris-label="{{ $formNo }}"
                            @click.stop="openPipeline(Number($event.currentTarget.dataset.risId), $event.currentTarget.dataset.risLabel)"
                        >Full pipeline</button>
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-gray-400">No procurement workflows yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Secondary movement metrics --}}
    <div class="pur-card">
        <div class="grid grid-cols-1 divide-gray-100 sm:grid-cols-3 sm:divide-x">
            @foreach($secondaryMetrics as $metric)
                <a href="{{ $metric['url'] }}" class="flex items-baseline justify-between gap-3 px-5 py-4 transition hover:bg-gray-50/70">
                    <span class="text-sm text-gray-500">{{ $metric['label'] }}</span>
                    <span class="text-xl font-semibold tracking-tight {{ !empty($metric['alert']) ? 'cc-alert' : 'text-gray-950' }}">
                        {{ $metric['value'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Exceptions --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <h2 class="text-base font-semibold text-gray-950">Exceptions</h2>
            <p class="mt-1 text-xs text-gray-400">Items that usually need Admin visibility or action.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($exceptions as $item)
                @php
                    $count = (int) ($item['count'] ?? 0);
                    $tone = $item['tone'] ?? 'slate';
                    $isAlert = $count > 0 && in_array($tone, ['amber', 'rose'], true);
                @endphp
                <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                    <span class="text-sm font-medium text-gray-900">{{ $item['label'] }}</span>
                    <span class="text-base font-semibold tabular-nums {{ $isAlert ? 'cc-alert' : ($count > 0 ? 'text-gray-950' : 'text-gray-400') }}">
                        {{ $count }}
                    </span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-gray-400">No exceptions right now.</p>
            @endforelse
        </div>
    </div>

    {{-- Needs attention --}}
    <div>
        <div class="mb-3">
            <h2 class="text-base font-semibold text-gray-950">Needs attention</h2>
            <p class="mt-1 text-xs text-gray-400">Top items with names, location, and how long they’ve been waiting.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($previewPanels as $panel)
                <div class="pur-card">
                    <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-950">{{ $panel['title'] }}</h3>
                        <a href="{{ $panel['url'] }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">All {{ $panel['total'] }}</a>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse($panel['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $item['meta'] }}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-semibold cc-alert">{{ $item['badge'] }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-sm text-gray-400">{{ $panel['empty'] }}</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Shortcuts --}}
    <div class="pur-card px-5 py-5">
        <h2 class="text-base font-semibold text-gray-950">Shortcuts</h2>
        <p class="mt-1 text-xs text-gray-400">Jump to common operations views.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach($shortcuts as $shortcut)
                <a href="{{ $shortcut['url'] }}" class="pur-filter-chip">
                    {{ $shortcut['label'] }}
                    <i data-lucide="arrow-up-right" class="ml-1 h-3.5 w-3.5"></i>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Document preview (one teleport root) --}}
    <template x-teleport="body">
        <div
            x-show="docOpen"
            x-cloak
            class="fixed inset-0 z-[12000] flex items-center justify-center bg-slate-900/45 p-4"
            @keydown.escape.window="if (docOpen) closeDoc()"
            @click.self="closeDoc()"
        >
            <div class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-2xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-950" x-text="docTitle">Document</p>
                        <p class="mt-0.5 text-xs text-gray-400">View only</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="pur-btn-secondary h-9 px-3 text-xs" @click="printDoc()">Print</button>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                            @click="closeDoc()"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <div class="relative min-h-0 flex-1 bg-gray-50">
                    <div x-show="docLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 text-sm text-gray-500">Loading form…</div>
                    <iframe x-ref="docFrame" class="h-full w-full border-0 bg-white" title="Document preview"></iframe>
                </div>
            </div>
        </div>
    </template>

    {{-- Pipeline drawer (one teleport root) --}}
    <template x-teleport="body">
        <div
            x-show="pipelineOpen"
            x-cloak
            class="fixed inset-0 z-[11900] flex items-stretch justify-end bg-slate-900/45"
            @keydown.escape.window="if (pipelineOpen && !docOpen) closePipeline()"
            @click.self="closePipeline()"
        >
            <div class="flex h-full w-full max-w-md flex-col overflow-y-auto border-l border-gray-200 bg-white shadow-2xl" @click.stop>
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-950">Procurement pipeline</p>
                        <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineSubtitle"></p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                        @click="closePipeline()"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="space-y-5 px-5 py-5" x-show="!pipelineLoading && pipelineData">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Current stage</p>
                        <p class="mt-1 text-base font-semibold uppercase tracking-tight text-gray-950" x-text="(pipelineData?.current_stage || '—')"></p>
                        <p class="mt-1 text-sm text-gray-500" x-text="pipelineData?.current_hint || ''"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="pipelineData?.payment_path_label" x-text="'Payment: ' + (pipelineData?.payment_path_label || '')"></p>
                    </div>

                    <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                        <template x-for="key in ['ris','atp','rfc','rr','liq']" :key="key">
                            <div class="flex items-start justify-between gap-3 px-4 py-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="key"></p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900" x-text="pipelineData?.stages?.[key]?.exists ? (pipelineData.stages[key].label) : 'Not started'"></p>
                                    <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineData?.stages?.[key]?.hint || ''"></p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-30"
                                    :disabled="!pipelineData?.stages?.[key]?.exists"
                                    @click="openDoc(key, pipelineData.stages[key].id, pipelineData.stages[key].label)"
                                >View</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="px-5 py-12 text-center text-sm text-gray-400" x-show="pipelineLoading">Loading pipeline…</div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminCommandCenterProcurement', () => ({
        docOpen: false,
        docLoading: false,
        docTitle: 'Document',
        pipelineOpen: false,
        pipelineLoading: false,
        pipelineData: null,
        pipelineSubtitle: '',

        openDoc(type, id, title) {
            if (!type || !id) return;
            this.docTitle = title || String(type).toUpperCase();
            this.docOpen = true;
            this.docLoading = true;
            const url = @json(url('/admin/operations/documents')) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(id) + '?ts=' + Date.now();
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
                const frame = this.$refs.docFrame;
                if (!frame) return;
                frame.onload = () => { this.docLoading = false; };
                frame.src = url;
            });
        },

        closeDoc() {
            this.docOpen = false;
            this.docLoading = false;
            if (this.$refs.docFrame) this.$refs.docFrame.src = 'about:blank';
        },

        printDoc() {
            try {
                this.$refs.docFrame?.contentWindow?.print();
            } catch (e) {}
        },

        async openPipeline(risId, subtitle) {
            this.pipelineSubtitle = subtitle || ('RIS #' + risId);
            this.pipelineLoading = true;
            this.pipelineData = null;
            this.pipelineOpen = true;

            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });

            try {
                const res = await fetch(@json(url('/admin/operations/procurement')) + '/' + encodeURIComponent(risId) + '/pipeline', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('Pipeline request failed');
                this.pipelineData = await res.json();
                this.pipelineSubtitle = (this.pipelineData?.stages?.ris?.label) || this.pipelineSubtitle;
            } catch (e) {
                this.pipelineData = {
                    stages: {},
                    logs: [],
                    current_stage: '—',
                    current_hint: 'Unable to load pipeline.',
                };
            } finally {
                this.pipelineLoading = false;
            }
        },

        closePipeline() {
            this.pipelineOpen = false;
            this.pipelineData = null;
            this.pipelineLoading = false;
        },
    }));
});
</script>
@endsection
