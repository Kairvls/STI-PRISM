@extends('layouts.admin-layout')

@section('title', 'Procurement Monitor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'all' => 'All',
        'open' => 'Open',
        'awaiting_admin' => 'Pending review',
        'atp' => 'At ATP',
        'rfc' => 'At RFC/CA',
        'rr' => 'At RR',
        'liq' => 'At LIQ',
    ];

    $metricDots = [
        'ris' => 'bg-[#0025cc]',
        'atp' => 'bg-sky-400',
        'rfc' => 'bg-amber-400',
        'receiving' => 'bg-emerald-500',
        'liquidation' => 'bg-violet-400',
    ];

    $risStatusClasses = [
        'Draft' => 'border-gray-200 bg-gray-100 text-gray-700',
        'Submitted' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Under Review' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Resubmitted' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Accepted' => 'border-violet-200 bg-violet-50 text-violet-700',
        'Minor Revision' => 'border-yellow-300 bg-yellow-50 text-amber-600',
        'Forwarded to President' => 'border-blue-200 bg-blue-50 text-blue-700',
        'Directly Approved' => 'border-slate-200 bg-slate-50 text-slate-600',
        'Approved' => 'border-green-200 bg-green-50 text-green-700',
        'Approved by the President' => 'border-green-200 bg-green-50 text-green-700',
        'Rejected' => 'border-red-200 bg-red-50 text-red-700',
        'Rejected by the President' => 'border-slate-500 bg-slate-800 text-slate-100',
    ];

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();
@endphp

<style>
    .pm-pipe {
        display: flex;
        align-items: center;
        min-width: 168px;
        max-width: 200px;
    }

    .pm-pipe-step {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .pm-pipe-step:last-child {
        flex: 0;
    }

    .pm-pipe-dot {
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .pm-pipe-dot.is-done {
        background: #94a3b8;
    }

    .pm-pipe-dot.is-current {
        background: #0025cc;
        box-shadow: 0 0 0 3px rgba(0, 37, 204, 0.15);
    }

    .pm-pipe-line {
        height: 1px;
        flex: 1;
        min-width: 8px;
        margin: 0 2px;
        background: #e2e8f0;
    }

    .pm-pipe-line.is-done {
        background: #cbd5e1;
    }

    .pm-pipe-labels {
        display: flex;
        justify-content: space-between;
        max-width: 200px;
        margin-top: 0.35rem;
    }

    .pm-pipe-label {
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .pm-pipe-label.is-done {
        color: #64748b;
    }

    .pm-pipe-label.is-current {
        color: #0025cc;
    }

    .pm-modal-backdrop {
        background: rgba(15, 23, 42, 0.45);
    }

    .pm-modal,
    .pm-drawer {
        background: #fff;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.16);
    }

    .pm-modal {
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .pm-drawer {
        border-left: 1px solid #e2e8f0;
    }

    [x-cloak] { display: none !important; }
</style>

<div class="admin-page space-y-6" x-data="adminProcurementMonitor">
    {{-- Intro / actions --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <p class="max-w-2xl text-sm leading-relaxed text-gray-500">
            View-only track of RIS → ATP → RFC/CA → RR → Liquidation.
            To <span class="font-semibold text-gray-900">create and drive</span> documents, enable procurement on your Admin account and open the
            <span class="font-semibold text-gray-900">Purchaser</span> portal.
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @if(\App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::PURCHASER))
                <a href="{{ url('/purchaser/dashboard') }}" class="pur-btn-primary h-9 px-4 text-[13px]">Open Purchaser portal</a>
            @endif
            <a href="{{ url('/admin/procurement-review') }}" class="pur-btn-secondary h-9 px-4 text-[13px]">Procurement Requests</a>
        </div>
    </div>

    {{-- Stage metrics --}}
    <div class="pur-card">
        <div class="grid grid-cols-2 divide-gray-100 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x">
            @foreach([
                'ris' => 'RIS forms',
                'atp' => 'ATP',
                'rfc' => 'Request for Check',
                'receiving' => 'Receiving',
                'liquidation' => 'Liquidation',
            ] as $key => $label)
                <div class="px-5 py-5">
                    <div class="flex items-center gap-2">
                        <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $stageCounts[$key] ?? 0 }}</p>
                        <span class="h-1.5 w-1.5 rounded-full {{ $metricDots[$key] ?? 'bg-gray-300' }}"></span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-gray-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Procurement records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Monitor RIS forms and stage activity across the procurement pipeline.</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search RIS, purpose, submitter…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '' && $q !== null)
                        <a
                            href="{{ route('admin.operations.procurement', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ route('admin.operations.procurement', ['filter' => $key, 'q' => $q]) }}"
                        class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[1080px]">
                <thead>
                    <tr>
                        <th>RIS</th>
                        <th>Purpose</th>
                        <th>Pipeline</th>
                        <th>Submitted by</th>
                        <th>Amount</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $pipeline = $row->pipeline ?? null;
                            $stages = $pipeline['stages'] ?? [];
                            $stageKeys = ['ris', 'atp', 'rfc', 'rr', 'liq'];
                            $current = $pipeline['current_stage'] ?? 'ris';
                            $currentIndex = array_search($current, $stageKeys, true);
                            if ($currentIndex === false) {
                                $currentIndex = 0;
                            }
                            $status = (string) ($row->ris_status ?: '');
                            $statusClass = $risStatusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <p class="font-semibold text-gray-900">{{ \App\Support\RisWorkflow::formNumber($row) }}</p>
                                @if($status !== '')
                                    <span class="mt-1.5 inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium {{ $statusClass }}">{{ $status }}</span>
                                @else
                                    <p class="mt-1 text-xs text-gray-400">—</p>
                                @endif
                            </td>
                            <td>
                                <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($row->ris_purpose_description, 70) ?: '—' }}</p>
                                @if(!empty($row->report_id))
                                    <p class="mt-1 text-xs text-gray-400">From report #{{ $row->report_id }}</p>
                                @endif
                            </td>
                            <td>
                                <div class="pm-pipe" title="{{ $pipeline['current_hint'] ?? '' }}">
                                    @foreach($stageKeys as $i => $key)
                                        @php
                                            $stage = $stages[$key] ?? null;
                                            $exists = !empty($stage['exists']);
                                            $isCurrent = $key === $current;
                                            $dotClass = $isCurrent ? 'is-current' : ($exists ? 'is-done' : '');
                                        @endphp
                                        <div class="pm-pipe-step">
                                            <span
                                                class="pm-pipe-dot {{ $dotClass }}"
                                                title="{{ $exists ? ($stage['hint'] ?? $stage['label']) : (strtoupper($key).' not started') }}"
                                            ></span>
                                            @if(!$loop->last)
                                                <span class="pm-pipe-line {{ ($exists && $i < $currentIndex) || ($exists && $isCurrent) ? 'is-done' : '' }}"></span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="pm-pipe-labels">
                                    @foreach($stageKeys as $key)
                                        @php
                                            $exists = !empty(($stages[$key] ?? [])['exists']);
                                            $isCurrent = $key === $current;
                                            $labelClass = $isCurrent ? 'is-current' : ($exists ? 'is-done' : '');
                                        @endphp
                                        <span class="pm-pipe-label {{ $labelClass }}">{{ strtoupper($key) }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-xs text-gray-400">
                                    {{ $pipeline['current_hint'] ?? '—' }}
                                    @if(!empty($pipeline['payment_path_label']) && $pipeline['payment_path_label'] !== 'Not chosen')
                                        · {{ $pipeline['payment_path_label'] }}
                                    @endif
                                </p>
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->created_by_name ?: '—' }}</td>
                            <td class="whitespace-nowrap text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</td>
                            <td class="text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="openDoc('ris', {{ (int) $row->ris_id }}, '{{ e(\App\Support\RisWorkflow::formNumber($row)) }}')"
                                    >View RIS</button>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001fa8]"
                                        @click="openPipeline({{ (int) $row->ris_id }})"
                                    >Pipeline</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pur-empty">No procurement forms found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>

    {{-- Document preview modal (teleported) --}}
    <template x-teleport="body">
        <div
            x-show="docOpen"
            x-cloak
            class="pm-modal-backdrop fixed inset-0 z-[12000] flex items-center justify-center p-4"
            @keydown.escape.window="if (docOpen) closeDoc()"
            @click.self="closeDoc()"
        >
            <div class="pm-modal flex h-[90vh] w-full max-w-5xl flex-col" @click.stop>
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-950" x-text="docTitle">Document</p>
                        <p class="mt-0.5 text-xs text-gray-400">View only</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="pur-btn-secondary h-9 px-3 text-xs" @click="printDoc()">Print</button>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
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

    {{-- Pipeline drawer (teleported) --}}
    <template x-teleport="body">
        <div
            x-show="pipelineOpen"
            x-cloak
            class="pm-modal-backdrop fixed inset-0 z-[11900] flex items-stretch justify-end"
            @keydown.escape.window="if (pipelineOpen && !docOpen) closePipeline()"
            @click.self="closePipeline()"
        >
            <div class="pm-drawer flex h-full w-full max-w-md flex-col overflow-y-auto" @click.stop>
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

                    <div class="rounded-xl border border-gray-100 px-4 py-3" x-show="pipelineData?.funds">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Funds</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="pipelineData?.funds?.label || '—'"></p>
                        <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineData?.funds?.released_at || ''"></p>
                    </div>

                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Approval activity</p>
                        <template x-if="!(pipelineData?.logs || []).length">
                            <p class="text-sm text-gray-400">No approval logs yet.</p>
                        </template>
                        <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                            <template x-for="(log, idx) in (pipelineData?.logs || [])" :key="idx">
                                <div class="px-4 py-3 text-xs">
                                    <p class="font-semibold text-gray-800">
                                        <span x-text="log.type"></span>
                                        · <span x-text="log.status"></span>
                                    </p>
                                    <p class="mt-0.5 text-gray-400" x-text="(log.actor || 'System') + (log.at ? (' · ' + log.at) : '')"></p>
                                    <p class="mt-1 text-gray-600" x-show="log.remarks" x-text="log.remarks"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-12 text-center text-sm text-gray-400" x-show="pipelineLoading">Loading pipeline…</div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminProcurementMonitor', () => ({
        docOpen: false,
        docLoading: false,
        docTitle: '',
        pipelineOpen: false,
        pipelineLoading: false,
        pipelineData: null,
        pipelineSubtitle: '',

        openDoc(type, id, title) {
            if (!type || !id) return;
            this.docTitle = title || (String(type).toUpperCase() + ' #' + id);
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

        async openPipeline(risId) {
            this.pipelineOpen = true;
            this.pipelineLoading = true;
            this.pipelineData = null;
            this.pipelineSubtitle = (typeof window.risFormNumberLabel === 'function')
                ? window.risFormNumberLabel(risId)
                : ('RIS #' + risId);

            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });

            try {
                const res = await fetch(@json(url('/admin/operations/procurement')) + '/' + encodeURIComponent(risId) + '/pipeline', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Failed');
                this.pipelineData = await res.json();
                this.pipelineSubtitle = (this.pipelineData?.stages?.ris?.label) || this.pipelineSubtitle;
            } catch (e) {
                this.pipelineData = { stages: {}, logs: [], current_stage: '—', current_hint: 'Unable to load pipeline.' };
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
