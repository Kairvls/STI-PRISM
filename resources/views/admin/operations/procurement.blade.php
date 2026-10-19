@extends('layouts.admin-layout')

@section('title', 'Procurement Monitor')
@section('page-subtitle', 'Track each Request No. (PR) through Canvas + RIS → ATP → RFC → RR → Liquidation.')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'all' => 'All requests',
        'open' => 'Still in progress',
        'awaiting_admin' => 'Waiting on Administrator',
        'atp' => 'At Authority to Purchase',
        'rfc' => 'At Request for Check',
        'rr' => 'At Receiving Report',
        'liq' => 'At Liquidation Report',
    ];

    $filterCounts = $filterCounts ?? [];
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
    <!--<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <p class="max-w-2xl text-sm leading-relaxed text-gray-500">
            View-only track of RIS → ATP → RFC/CA → RR → Liquidation.
            @if (! \App\Support\AdminPortal::isSchoolAdmin())
                To <span class="font-semibold text-gray-900">create and drive</span> documents, enable procurement on your Administrator account and open the
                <span class="font-semibold text-gray-900">Purchaser</span> portal.
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @if(! \App\Support\AdminPortal::isSchoolAdmin() && \App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::PURCHASER))
                <a href="{{ url('/purchaser/dashboard') }}" class="pur-btn-primary h-9 px-4 text-[13px]">Open Purchaser portal</a>
            @endif
            <a href="{{ \App\Support\AdminPortal::route('procurement-review') }}" class="pur-btn-secondary h-9 px-4 text-[13px]">Procurement Requests</a>
        </div>
    </div>-->

    {{-- Stage metrics --}}

    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Requests</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Each row is one Request No. (PR) and its document pipeline.</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <select
                        name="filter"
                        aria-label="Filter by pipeline stop"
                        onchange="this.form.submit()"
                        class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white sm:min-w-[16.5rem]"
                    >
                        @foreach($filters as $key => $label)
                            <option value="{{ $key }}" @selected($filter === $key)>
                                {{ $label }}@if(isset($filterCounts[$key])) ({{ number_format($filterCounts[$key]) }})@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search Request No., form no., submitter…"
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium leading-none text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if(($q !== '' && $q !== null) || $filter !== 'all')
                        <a
                            href="{{ \App\Support\AdminPortal::route('operations.procurement') }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[1080px]">
                <thead>
                    <tr>
                        <th>Request No.</th>
                        <th>Procurement Pipeline</th>
                        <th>Latest stop</th>
                        <th>Submitted by</th>
                        <th>Amount</th>
                        <th class="text-center">Action</th>
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
                            $requestNo = trim((string) ($row->ris_request_no ?? ($pipeline['request_no'] ?? '')));
                            $risLabel = \App\Support\RisWorkflow::formNumber($row);
                            $latestStage = $stages[$current] ?? null;
                            $latestFormNo = trim((string) ($latestStage['label'] ?? ''));
                            if ($latestFormNo === '' && $current === 'ris') {
                                $latestFormNo = $risLabel;
                            }
                            // Full status wording for the current stop (not the short pipeline chip).
                            if ($current === 'ris') {
                                $latestStatus = trim((string) (\App\Support\DocumentLineage::reviewHintForRis($row) ?? ''));
                                if ($latestStatus === '') {
                                    $latestStatus = \App\Support\RisWorkflow::statusLabel($row);
                                }
                            } else {
                                $latestStatus = trim((string) ($latestStage['hint'] ?? ($pipeline['current_hint'] ?? '')));
                            }
                            $latestStageName = \App\Support\AdminPipeline::stageName($current);
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                @if($requestNo !== '')
                                    @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'size' => 'md', 'showLabel' => false])
                                @else
                                    <p class="text-xs text-gray-400">No Request No. yet</p>
                                @endif
                            </td>
                            <td>
                                <div class="pm-pipe" title="{{ $latestStatus }}">
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
                                                title="{{ \App\Support\AdminPipeline::stageName($key) }}{{ $exists ? ': '.($stage['hint'] ?? $stage['label']) : ' — not started' }}"
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
                                @if(!empty($pipeline['payment_path_label']) && $pipeline['payment_path_label'] !== 'Not chosen')
                                    <p class="mt-1.5 text-xs text-gray-400">Payment: {{ $pipeline['payment_path_label'] }}</p>
                                @endif
                            </td>
                            <td>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $latestStageName }}</p>
                                <p class="mt-0.5 text-sm font-semibold tabular-nums text-gray-900">{{ $latestFormNo !== '' ? $latestFormNo : '—' }}</p>
                                <p class="mt-1 text-xs leading-4 text-gray-500">{{ $latestStatus !== '' ? $latestStatus : '—' }}</p>
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->created_by_name ?: '—' }}</td>
                            <td class="whitespace-nowrap text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</td>
                            <td class="text-center">
                                <div class="inline-flex items-center justify-center gap-2 whitespace-nowrap">
                                    @if($requestNo !== '' && \Illuminate\Support\Facades\Route::has('requests.show'))
                                        <a
                                            href="{{ route('requests.show', ['requestNo' => $requestNo]) }}"
                                            class="inline-flex h-9 shrink-0 items-center whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        >Open</a>
                                    @endif
                                    <button
                                        type="button"
                                        class="inline-flex h-9 shrink-0 items-center whitespace-nowrap rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001fa8]"
                                        @click="openPipeline({{ (int) $row->ris_id }}, @js($requestNo !== '' ? $requestNo : $risLabel))"
                                    >Pipeline</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pur-empty">No requests found.</td>
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
                        <button type="button" class="pur-btn-secondary inline-flex h-9 items-center gap-1.5 px-3 text-xs" @click="printDoc()">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Print
                        </button>
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
                    <div x-show="docLoading" class="absolute inset-0 z-10 overflow-hidden bg-gray-50 py-6">
                        @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
                    </div>
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
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Request No.</p>
                        <p class="mt-1 text-base font-semibold tracking-tight text-indigo-800" x-text="pipelineData?.request_no || pipelineSubtitle || '—'"></p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Current stage</p>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-tight text-gray-950" x-text="(pipelineData?.current_stage || '—')"></p>
                        <p class="mt-1 text-sm text-gray-500" x-text="pipelineData?.current_hint || ''"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="pipelineData?.payment_path_label" x-text="'Payment: ' + (pipelineData?.payment_path_label || '')"></p>
                    </div>

                    <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                        <template x-for="key in ['ris','atp','rfc','rr','liq']" :key="key">
                            <div class="flex items-start justify-between gap-3 px-4 py-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="key === 'ris' ? 'Canvas + RIS' : key"></p>
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

                <div x-show="pipelineLoading">
                    @include('partials.skeleton', ['skeletonType' => 'detail', 'skeletonRows' => 5, 'skeletonLabel' => 'Loading pipeline'])
                </div>
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
            this.docTitle = title || String(type).toUpperCase();
            this.docOpen = true;
            this.docLoading = true;
            const url = @json(\App\Support\AdminPortal::url('operations/documents')) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(id) + '?ts=' + Date.now();
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

        async openPipeline(risId, label) {
            this.pipelineOpen = true;
            this.pipelineLoading = true;
            this.pipelineData = null;
            this.pipelineSubtitle = label || 'Request pipeline';

            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });

            try {
                const res = await fetch(@json(\App\Support\AdminPortal::url('operations/procurement')) + '/' + encodeURIComponent(risId) + '/pipeline', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Failed');
                this.pipelineData = await res.json();
                this.pipelineSubtitle = this.pipelineData?.request_no
                    || this.pipelineData?.stages?.ris?.label
                    || this.pipelineSubtitle;
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
