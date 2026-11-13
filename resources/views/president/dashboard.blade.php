@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

@php
    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
@endphp

<div class="pres-dash-row -mt-4 sm:-mt-5">
    <aside class="pm-card min-w-0 flex-1 p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="presidentQueueTitle" class="text-sm font-bold text-gray-900">3 latest RIS waiting for approval</h2>
                <span id="presidentQueueCount" class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($pendingApprovalsCount ?? 0)) }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <div id="presidentQueueSwitch" class="inline-flex rounded-full bg-slate-100 p-0.5" role="group" aria-label="Waiting for approval">
                    <button type="button" class="queue-switch is-on" data-queue="ris" aria-pressed="true">RIS</button>
                    <button type="button" class="queue-switch" data-queue="dv" aria-pressed="false">DV</button>
                </div>
                <a id="presidentQueueViewAll" href="/president/approvals" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
        </div>
        <div id="presidentQueueRis" class="mt-4 space-y-2.5">
            @forelse ($recentRis as $ris)
                @php
                    $label = \App\Support\RisWorkflow::formNumber($ris);
                    $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                    $requester = \App\Support\RisWorkflow::requesterName($ris);
                    $amount = number_format((float) ($ris->total_amount ?? 0), 2);
                @endphp
                <div
                    class="cursor-pointer rounded-xl border border-blue-100 bg-white px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/40"
                    data-dashboard-preview
                    data-preview-url="{{ route('president.ris.view', $ris->ris_id) }}?preview=1"
                    data-continue-url="/president/approvals?approve={{ $ris->ris_id }}"
                    data-continue-label="Approve"
                    data-preview-title="{{ $label }}"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $requester }} · {{ $date }}</p>
                            <p class="mt-1 text-xs font-semibold text-blue-700">₱{{ $amount }}</p>
                            @php
                                $guide = $ris->guide ?? null;
                                $guideClass = match ($guide['tone'] ?? '') {
                                    'approve' => 'bg-emerald-50 text-emerald-700',
                                    'hold' => 'bg-red-50 text-red-700',
                                    default => 'bg-amber-50 text-amber-700',
                                };
                            @endphp
                            @if($guide)
                                <p class="mt-1.5">
                                    <span class="inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-semibold {{ $guideClass }}">{{ $guide['label'] }}</span>
                                </p>
                                <p class="mt-1 text-[11px] leading-4 text-slate-500">{{ $guide['reason'] }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                            <a
                                href="/president/approvals?approve={{ $ris->ris_id }}"
                                class="inline-flex h-9 items-center rounded-xl bg-blue-600 px-3 text-[11px] font-medium text-white transition hover:bg-blue-700"
                                data-tip="Open and approve this RIS"
                            >
                                Approve
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 px-3 py-8 text-center">
                    <p class="text-xs font-medium text-gray-500">No RIS waiting for approval</p>
                </div>
            @endforelse
            @if ($recentRis->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ (int) ($pendingApprovalsCount ?? 0) > $recentRis->count() ? $recentRis->count().' latest' : 'Showing '.$recentRis->count().' of '.(int) ($pendingApprovalsCount ?? 0) }}</p>
            @endif
        </div>
        <div id="presidentQueueDv" class="mt-4 hidden divide-y divide-slate-100">
            @forelse ($pendingDvs ?? [] as $dv)
                @php
                    $dvLabel = $dv->dv_form_number ?: 'Disbursement voucher';
                    $dvWho = $dv->dv_payee ?: ($dv->request_check_payee ?: '—');
                @endphp
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40"
                    data-dashboard-preview
                    data-preview-url="{{ route('president.dashboard.preview', ['type' => 'dv', 'id' => $dv->dv_id]) }}"
                    data-continue-url="{{ route('president.dv.show', $dv->dv_id) }}"
                    data-continue-label="Open"
                    data-preview-title="{{ $dvLabel }}"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-gray-900">{{ $dvLabel }}</span>
                        <span class="block truncate text-[11px] text-gray-500">{{ $dvWho }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-xs font-semibold text-gray-900">{{ $peso($dv->dv_amount ?? 0) }}</span>
                        <span class="text-[11px] font-semibold text-blue-600">Open</span>
                    </span>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">No disbursement voucher waiting for approval</p>
            @endforelse
            @if (collect($pendingDvs ?? [])->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ (int) ($pendingDvCount ?? 0) > collect($pendingDvs ?? [])->count() ? collect($pendingDvs ?? [])->count().' latest' : 'Showing '.collect($pendingDvs ?? [])->count().' of '.(int) ($pendingDvCount ?? 0) }}</p>
            @endif
        </div>
    </aside>

    <aside class="pm-card min-w-0 flex-1 p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">Send back to School Administrator</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($awaitingNotifyCount ?? 0)) }}</span>
            </div>
            <a href="/president/approvals?focus=awaiting-notify" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($toNotify ?? [] as $ris)
                @php $notifyLabel = \App\Support\RisWorkflow::formNumber($ris); @endphp
                <button
                    type="button"
                    class="block w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50/60"
                    data-dashboard-preview
                    data-preview-url="{{ route('president.ris.view', $ris->ris_id) }}?preview=1"
                    data-continue-url="/president/approvals?preview={{ $ris->ris_id }}"
                    data-continue-label="Open"
                    data-preview-title="{{ $notifyLabel }}"
                >
                    <p class="truncate text-xs font-semibold text-gray-900">{{ $notifyLabel }}</p>
                    <p class="text-[11px] text-gray-500">{{ $ris->ris_approved_by_date ? date('M d, Y', strtotime($ris->ris_approved_by_date)) : '—' }}</p>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
            @endforelse
        </div>
        @if (collect($toNotify ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($awaitingNotifyCount ?? 0) > collect($toNotify ?? [])->count() ? collect($toNotify ?? [])->count().' latest' : 'Showing '.collect($toNotify ?? [])->count().' of '.(int) ($awaitingNotifyCount ?? 0) }}</p>
        @endif
    </aside>

    <section class="pm-card min-w-0 p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">Compiled records</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($compiledRecordCount ?? 0)) }}</span>
            </div>
            <a href="{{ route('president.procurement-records.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
        </div>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($compiledRecords ?? [] as $record)
                @php
                    $recordDate = $record->package_forwarded_to_president_at
                        ? \Carbon\Carbon::parse($record->package_forwarded_to_president_at)->format('M d, Y')
                        : '—';
                    $recordPr = $record->authority_purchase_request_no ?? $record->ris_request_no ?? null;
                    $recordTitle = $recordPr ?: 'Compiled record';
                    $recordAtp = $record->authority_purchase_form_number ?: '—';
                    $recordAtpId = (int) ($record->package_authority_purchase_id ?? 0);
                @endphp
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40"
                    data-dashboard-preview
                    data-preview-url="{{ $recordPr ? route('president.requests.show', ['requestNo' => $recordPr]).'?preview=1' : '' }}"
                    data-continue-url="{{ route('president.procurement-records.show', $record->package_id) }}"
                    data-continue-label="Open"
                    data-preview-title="Compiled record"
                    data-preview-skeleton="pipeline"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-blue-700">{{ $recordTitle }}</span>
                        <span class="block truncate text-[11px] font-medium text-gray-900">{{ $recordAtp }}</span>
                        <span class="block truncate text-[11px] text-gray-500">{{ $record->ris_form_number ?: '—' }} · {{ \App\Support\ProcurementPaymentPath::label($record->package_payment_path ?? null) }} · {{ $recordDate }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-xs font-semibold text-blue-700">{{ $peso($record->package_amount ?? 0) }}</span>
                        <span class="text-[11px] font-semibold text-blue-600">Open</span>
                    </span>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">None yet</p>
            @endforelse
        </div>
        @if (collect($compiledRecords ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($compiledRecordCount ?? 0) > collect($compiledRecords ?? [])->count() ? collect($compiledRecords ?? [])->count().' latest' : 'Showing '.collect($compiledRecords ?? [])->count().' of '.(int) ($compiledRecordCount ?? 0) }}</p>
        @endif
    </section>
</div>

<div class="mt-4 grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
    <section class="pm-card p-5 lg:col-span-2">
        @php
            $budget = $approvedBudget ?? ['year' => now()->year, 'month' => 0, 'years' => [now()->year], 'months' => [], 'amount' => 0, 'count' => 0, 'rows' => collect()];
            $chartLabels = [];
            $chartValues = [];
            if ((int) $budget['month'] === 0) {
                $byMonth = array_fill(1, 12, 0.0);
                foreach ($budget['rows'] as $chartRow) {
                    $chartMonth = $chartRow->ris_approved_by_date ? (int) date('n', strtotime($chartRow->ris_approved_by_date)) : 0;
                    if ($chartMonth >= 1 && $chartMonth <= 12) {
                        $byMonth[$chartMonth] += (float) $chartRow->total_amount;
                    }
                }
                foreach ($budget['months'] as $chartNumber => $chartName) {
                    $chartLabels[] = $chartName;
                    $chartValues[] = round($byMonth[(int) $chartNumber], 2);
                }
            } else {
                foreach ($budget['rows'] as $chartRow) {
                    $chartLabels[] = \App\Support\RisWorkflow::formNumber($chartRow);
                    $chartValues[] = round((float) $chartRow->total_amount, 2);
                }
            }
        @endphp
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="presidentHistoryTitle" class="text-sm font-bold text-gray-900">Budget you approved</h2>
                <div id="presidentHistoryBudgetMeta">
                    <p class="mt-1 text-lg font-semibold tabular-nums text-blue-700">{{ $peso($budget['amount']) }}</p>
                    <p id="presidentBudgetCaption" class="text-[11px] text-slate-500">{{ (int) $budget['count'] > 6 ? '6 latest of '.(int) $budget['count'] : ((int) $budget['count'].' '.((int) $budget['count'] === 1 ? 'decision' : 'decisions')) }}</p>
                </div>
                <span id="presidentHistoryCount" class="mt-1 hidden text-[11px] text-slate-500">{{ number_format((int) ($approvedDecisionsCount ?? 0)) }} all decisions approved</span>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <form id="presidentBudgetFilters" method="GET" action="{{ route('president.dashboard') }}" class="flex items-center gap-2">
                    <select name="budget_month" class="h-7 rounded-md border border-slate-200 bg-white px-2 text-[11px] text-slate-700 outline-none focus:border-blue-600" aria-label="Month" onchange="this.form.submit()">
                        <option value="0" @selected((int) $budget['month'] === 0)>All months</option>
                        @foreach($budget['months'] as $number => $name)
                            <option value="{{ $number }}" @selected((int) $budget['month'] === (int) $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="budget_year" class="h-7 rounded-md border border-slate-200 bg-white px-2 text-[11px] text-slate-700 outline-none focus:border-blue-600" aria-label="Year" onchange="this.form.submit()">
                        @foreach($budget['years'] as $year)
                            <option value="{{ $year }}" @selected((int) $budget['year'] === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </form>
                <button type="button" id="presidentBudgetChartOpen" class="icon-btn" aria-label="Show chart" data-tip="Chart">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-3"/></svg>
                </button>
                <div id="presidentHistorySwitch" class="inline-flex rounded-full bg-slate-100 p-0.5" role="group" aria-label="Approved lists">
                    <button type="button" class="queue-switch is-on" data-history="budget" aria-pressed="true">Budget</button>
                    <button type="button" class="queue-switch" data-history="recent" aria-pressed="false">Recent</button>
                </div>
                <a id="presidentHistoryViewAll" href="/president/reports/approved" class="hidden text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
        </div>
        <div id="presidentHistoryBudget" class="mt-4 divide-y divide-slate-100">
            @forelse($budget['rows'] as $row)
                @php $budgetLabel = \App\Support\RisWorkflow::formNumber($row); @endphp
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40 {{ $loop->index >= 6 ? 'hidden' : '' }}"
                    data-budget-row="{{ $loop->index }}"
                    data-dashboard-preview
                    data-preview-url="{{ route('president.ris.view', $row->ris_id) }}?preview=1"
                    data-continue-url="/president/approvals?preview={{ $row->ris_id }}"
                    data-continue-label="Open"
                    data-preview-title="{{ $budgetLabel }}"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-gray-900">{{ $budgetLabel }}</span>
                        <span class="text-[11px] text-gray-500">{{ $row->ris_approved_by_date ? date('M d, Y', strtotime($row->ris_approved_by_date)) : '—' }}</span>
                    </span>
                    <span class="shrink-0 text-xs font-semibold text-blue-700">{{ $peso($row->total_amount) }}</span>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">No approved budget in this period</p>
            @endforelse
        </div>
        @if((int) $budget['count'] > 6)
            <div id="presidentBudgetTools" class="mt-3 flex items-center justify-between gap-3">
                <button type="button" id="presidentBudgetToggle" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800">Show all</button>
                <div id="presidentBudgetPager" class="hidden items-center gap-2 text-xs text-slate-500">
                    <button type="button" data-budget-page="prev" class="font-semibold text-blue-600 disabled:text-slate-300">Previous</button>
                    <span id="presidentBudgetPageLabel"></span>
                    <button type="button" data-budget-page="next" class="font-semibold text-blue-600 disabled:text-slate-300">Next</button>
                </div>
            </div>
        @endif
        <div id="presidentBudgetChart" class="relative mt-2 hidden h-64">
            <button type="button" id="presidentBudgetChartClose" class="absolute right-0 top-0 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close chart">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <canvas id="presidentBudgetChartCanvas" height="220"></canvas>
        </div>
        <div id="presidentHistoryRecent" class="mt-4 hidden space-y-1">
            @forelse ($recentlyApprovedRis ?? [] as $ris)
                @php
                    $label = \App\Support\RisWorkflow::formNumber($ris);
                    $date = $ris->ris_approved_by_date ? date('M d, Y', strtotime($ris->ris_approved_by_date)) : '—';
                @endphp
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50/60"
                        data-dashboard-preview
                        data-preview-url="{{ route('president.ris.view', $ris->ris_id) }}?preview=1"
                        data-continue-url="/president/approvals?preview={{ $ris->ris_id }}"
                        data-continue-label="Open"
                        data-preview-title="{{ $label }}"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $label }}</span>
                            <span class="text-[11px] text-gray-500">{{ $date }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-semibold text-blue-700">{{ $peso($ris->total_amount ?? 0) }}</span>
                        @if (!empty($ris->awaiting_notify))
                            <span class="inline-flex shrink-0 items-center rounded-xl bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">Send back</span>
                        @endif
                    </button>
                    <button type="button" class="icon-btn shrink-0" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-xs font-medium text-gray-500">None yet</p>
            @endforelse
            @if (collect($recentlyApprovedRis ?? [])->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ (int) ($approvedDecisionsCount ?? 0) > collect($recentlyApprovedRis ?? [])->count() ? collect($recentlyApprovedRis ?? [])->count().' latest' : 'Showing '.collect($recentlyApprovedRis ?? [])->count().' of '.(int) ($approvedDecisionsCount ?? 0) }}</p>
            @endif
        </div>
    </section>

    <aside class="pm-card p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">SA Direct Approvals</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($directApprovalCount ?? 0)) }}</span>
            </div>
            <a href="/president/direct-approvals" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($directApprovals ?? [] as $direct)
                <button
                    type="button"
                    class="block w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50/60"
                    data-dashboard-preview
                    data-preview-url="{{ route('president.ris.view', $direct->id) }}?preview=1"
                    data-continue-url="/president/direct-approvals"
                    data-continue-label="Open"
                    data-preview-title="{{ $direct->number }}"
                >
                    <span class="flex items-start justify-between gap-2">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $direct->number }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $direct->approver }} · {{ $direct->at?->format('M d, Y') ?? '—' }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-semibold text-blue-700">{{ $peso($direct->amount) }}</span>
                    </span>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
            @endforelse
        </div>
        @if (collect($directApprovals ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($directApprovalCount ?? 0) > collect($directApprovals ?? [])->count() ? collect($directApprovals ?? [])->count().' latest' : 'Showing '.collect($directApprovals ?? [])->count().' of '.(int) ($directApprovalCount ?? 0) }}</p>
        @endif
    </aside>
</div>

<style>
    .pres-dash-row,
    .pres-dash-split {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        width: 100%;
        min-width: 0;
    }
    .pres-dash-row > *,
    .pres-dash-split > * {
        min-width: 0;
        width: 100%;
    }
    @media (min-width: 1024px) {
        .pres-dash-row { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr); }
    }
    .queue-switch {
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        padding: 6px 10px;
        cursor: pointer;
    }
    .queue-switch.is-on {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }
    .pm-card:hover,
    .pm-card-hover:hover {
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        transform: none;
    }
    #presidentBudgetPager.is-on {
        display: flex;
    }
    #presidentBudgetTools.is-off,
    #presidentBudgetChartOpen.is-off,
    #presidentBudgetCaption.is-off {
        display: none;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes chartFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    .count-up { display: inline-block; }

    /* Maintenance-style analytics chart card */
    .pm-analytics-card {
        min-width: 0;
        overflow: hidden;
        padding: 22px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .pm-analytics-card .pm-stat-value {
        font-size: inherit;
    }
    .pm-analytics-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 12px;
    }
    .pm-analytics-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }
    .pm-analytics-subtitle {
        margin: 3px 0 0;
        color: #94a3b8;
        font-size: 10px;
    }
    .pm-chart-total {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .pm-chart-total.is-blue {
        color: #1d4ed8;
    }
    .pm-chart-total span {
        margin-left: 2px;
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
    }
    .pm-decision-chart-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 8px;
    }
    .pm-decision-chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
    }
    .pm-decision-chart-swatch {
        width: 10px;
        height: 3px;
        border-radius: 999px;
    }
    .pm-decision-chart-swatch.is-approved { background: #2563EB; }
    .pm-decision-chart-swatch.is-rejected { background: #94a3b8; }
    .pm-decision-chart {
        position: relative;
        width: 100%;
        height: 320px;
        animation: chartFadeIn 0.8s ease-out forwards;
    }
</style>

<div id="presidentDashboardPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="presidentDashboardPreviewTitle">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="presidentDashboardPreviewTitle" class="truncate text-sm font-semibold text-slate-950">Form</h2>
            <button type="button" data-preview-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div id="presidentDashboardPreviewStage" class="relative overflow-hidden bg-slate-100">
            <div id="presidentDashboardPreviewFit" class="mx-auto overflow-hidden" style="height:0">
                <iframe id="presidentDashboardPreviewFrame" title="Form preview" class="block border-0 bg-white" style="width:100%;height:0" src="about:blank"></iframe>
            </div>
            <div id="presidentDashboardPreviewSkeleton" class="bg-slate-100 py-6">
                <div data-preview-skeleton-document>
                    @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
                </div>
                <div data-preview-skeleton-pipeline class="hidden bg-white p-5" role="status" aria-live="polite" aria-busy="true">
                    <span class="sr-only">Loading compiled record…</span>
                    <div aria-hidden="true">
                        <div class="prism-skel" style="width:72px;height:9px"></div>
                        <div class="prism-skel mt-2" style="width:220px;height:22px"></div>
                        <div class="mt-5 rounded-xl border border-gray-200 p-5">
                            <div class="flex items-start justify-between gap-6">
                                <div>
                                    <div class="prism-skel" style="width:48px;height:9px"></div>
                                    <div class="prism-skel mt-2" style="width:168px;height:16px"></div>
                                    <div class="prism-skel mt-2" style="width:72px;height:10px"></div>
                                </div>
                                <div class="shrink-0">
                                    <div class="prism-skel ml-auto" style="width:148px;height:10px"></div>
                                    <div class="prism-skel ml-auto mt-2" style="width:112px;height:10px"></div>
                                </div>
                            </div>
                            <div class="mt-8 flex items-start">
                                @for ($step = 0; $step < 5; $step++)
                                    <div class="flex flex-1 flex-col items-center">
                                        <div class="flex w-full items-center">
                                            <div class="h-0.5 flex-1 {{ $step === 0 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                            <div class="prism-skel prism-skel-round shrink-0" style="width:36px;height:36px"></div>
                                            <div class="h-0.5 flex-1 {{ $step === 4 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                        </div>
                                        <div class="prism-skel mt-2" style="width:64px;height:9px"></div>
                                        <div class="prism-skel mt-1.5" style="width:92px;height:10px"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="presidentDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var switcher = document.getElementById('presidentQueueSwitch');
        if (!switcher) return;
        var title = document.getElementById('presidentQueueTitle');
        var count = document.getElementById('presidentQueueCount');
        var viewAll = document.getElementById('presidentQueueViewAll');
        var panels = {
            ris: document.getElementById('presidentQueueRis'),
            dv: document.getElementById('presidentQueueDv')
        };
        var modes = {
            ris: {
                title: '3 latest RIS waiting for approval',
                count: @json(number_format((int) ($pendingApprovalsCount ?? 0))),
                href: '/president/approvals'
            },
            dv: {
                title: '3 latest DV waiting for approval',
                count: @json(number_format((int) ($pendingDvCount ?? 0))),
                href: @json(route('president.dv.index'))
            }
        };
        switcher.addEventListener('click', function (event) {
            var button = event.target.closest('[data-queue]');
            if (!button || !modes[button.getAttribute('data-queue')]) return;
            var key = button.getAttribute('data-queue');
            Object.keys(panels).forEach(function (name) {
                if (panels[name]) panels[name].classList.toggle('hidden', name !== key);
            });
            switcher.querySelectorAll('[data-queue]').forEach(function (item) {
                var on = item === button;
                item.classList.toggle('is-on', on);
                item.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (title) title.textContent = modes[key].title;
            if (count) count.textContent = modes[key].count;
            if (viewAll) viewAll.href = modes[key].href;
        });
    })();

    (function () {
        var switcher = document.getElementById('presidentHistorySwitch');
        if (!switcher) return;
        var title = document.getElementById('presidentHistoryTitle');
        var meta = document.getElementById('presidentHistoryBudgetMeta');
        var count = document.getElementById('presidentHistoryCount');
        var filters = document.getElementById('presidentBudgetFilters');
        var viewAll = document.getElementById('presidentHistoryViewAll');
        var panels = {
            budget: document.getElementById('presidentHistoryBudget'),
            recent: document.getElementById('presidentHistoryRecent')
        };
        switcher.addEventListener('click', function (event) {
            var button = event.target.closest('[data-history]');
            if (!button || !panels[button.getAttribute('data-history')]) return;
            var key = button.getAttribute('data-history');
            var budgetOn = key === 'budget';
            Object.keys(panels).forEach(function (name) {
                if (panels[name]) panels[name].classList.toggle('hidden', name !== key);
            });
            switcher.querySelectorAll('[data-history]').forEach(function (item) {
                var on = item === button;
                item.classList.toggle('is-on', on);
                item.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (title) title.textContent = budgetOn ? 'Budget you approved' : @json(collect($recentlyApprovedRis ?? [])->count().' latest recently approved');
            if (meta) meta.classList.toggle('hidden', !budgetOn);
            if (count) count.classList.toggle('hidden', budgetOn);
            if (filters) filters.classList.toggle('hidden', !budgetOn);
            if (viewAll) viewAll.classList.toggle('hidden', budgetOn);
            var tools = document.getElementById('presidentBudgetTools');
            if (tools) tools.classList.toggle('is-off', !budgetOn);
            if (!budgetOn && window.closePresidentBudgetChart) window.closePresidentBudgetChart();
            var chartButton = document.getElementById('presidentBudgetChartOpen');
            if (chartButton) chartButton.classList.toggle('is-off', !budgetOn);
        });
    })();

    (function () {
        var openButton = document.getElementById('presidentBudgetChartOpen');
        var panel = document.getElementById('presidentBudgetChart');
        var closeButton = document.getElementById('presidentBudgetChartClose');
        var canvas = document.getElementById('presidentBudgetChartCanvas');
        var list = document.getElementById('presidentHistoryBudget');
        var tools = document.getElementById('presidentBudgetTools');
        if (!openButton || !panel || !canvas) return;

        var labels = @json($chartLabels ?? []);
        var values = @json($chartValues ?? []);
        var chart = null;

        function setListVisible(show) {
            if (list) list.classList.toggle('hidden', !show);
            if (tools) tools.classList.toggle('is-off', !show);
            openButton.classList.toggle('is-off', !show);
            var caption = document.getElementById('presidentBudgetCaption');
            if (caption) caption.classList.toggle('is-off', !show);
        }

        function closeChart() {
            panel.classList.add('hidden');
            var recentOn = document.querySelector('#presidentHistorySwitch [data-history="recent"].is-on');
            if (recentOn) return;
            setListVisible(true);
        }

        window.closePresidentBudgetChart = closeChart;

        openButton.addEventListener('click', function () {
            panel.classList.remove('hidden');
            setListVisible(false);
            if (chart || typeof Chart === 'undefined') return;
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: '#93c5fd',
                        hoverBackgroundColor: '#0025cc',
                        borderRadius: 6,
                        maxBarThickness: 28
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (item) {
                                    return '₱' + Number(item.parsed.y || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                    }
                }
            });
        });

        if (closeButton) closeButton.addEventListener('click', closeChart);
    })();

    (function () {
        var list = document.getElementById('presidentHistoryBudget');
        var toggle = document.getElementById('presidentBudgetToggle');
        var caption = document.getElementById('presidentBudgetCaption');
        var pager = document.getElementById('presidentBudgetPager');
        if (!list || !toggle) return;
        var rows = Array.prototype.slice.call(list.querySelectorAll('[data-budget-row]'));
        var total = rows.length;
        var preview = 6;
        var pageSize = 15;
        var open = false;
        var page = 0;
        var pages = Math.max(1, Math.ceil(total / pageSize));
        var label = document.getElementById('presidentBudgetPageLabel');
        var prev = pager ? pager.querySelector('[data-budget-page="prev"]') : null;
        var next = pager ? pager.querySelector('[data-budget-page="next"]') : null;

        function paint() {
            rows.forEach(function (row, index) {
                var show = !open
                    ? index < preview
                    : (total <= pageSize || (index >= page * pageSize && index < (page + 1) * pageSize));
                row.classList.toggle('hidden', !show);
            });
            toggle.textContent = open ? 'Show less' : 'Show all';
            var paging = open && total > pageSize;
            if (pager) pager.classList.toggle('is-on', paging);
            if (label && paging) {
                label.textContent = (page * pageSize + 1) + '–' + Math.min(total, (page + 1) * pageSize) + ' of ' + total;
            }
            if (prev) prev.disabled = page === 0;
            if (next) next.disabled = page >= pages - 1;
            if (!caption) return;
            if (!open && total > preview) {
                caption.textContent = Math.min(preview, total) + ' latest of ' + total;
            } else if (paging) {
                caption.textContent = 'Showing ' + (page * pageSize + 1) + '–' + Math.min(total, (page + 1) * pageSize) + ' of ' + total;
            } else {
                caption.textContent = total + (total === 1 ? ' decision' : ' decisions');
            }
        }

        toggle.addEventListener('click', function () {
            open = !open;
            page = 0;
            paint();
        });
        if (pager) {
            pager.addEventListener('click', function (event) {
                var button = event.target.closest('[data-budget-page]');
                if (!button || button.disabled) return;
                page += button.getAttribute('data-budget-page') === 'next' ? 1 : -1;
                page = Math.max(0, Math.min(pages - 1, page));
                paint();
            });
        }
    })();

    window.printRisDocument = function (risId) {
        if (!risId) return;
        var url = '/president/ris/' + encodeURIComponent(risId) + '/print';
        var iframe = document.getElementById('presidentRisPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'presidentRisPrintFrame';
            iframe.setAttribute('title', 'Print RIS');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
            document.body.appendChild(iframe);
        }

        var printed = false;
        var tryPrint = function () {
            if (printed) return;
            if (!iframe.contentWindow) return;
            printed = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) { /* ignore */ }
        };

        iframe.onload = function () {
            setTimeout(tryPrint, 300);
        };
        iframe.src = url;
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

        const counters = document.querySelectorAll('.count-up');
        counters.forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0', 10);
            if (target === 0) return;
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const interval = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(interval);
                }
                el.textContent = current;
            }, 30);
        });

    });

    (function () {
        const modal = document.getElementById('presidentDashboardPreview');
        const frame = document.getElementById('presidentDashboardPreviewFrame');
        const fit = document.getElementById('presidentDashboardPreviewFit');
        const stage = document.getElementById('presidentDashboardPreviewStage');
        const skeleton = document.getElementById('presidentDashboardPreviewSkeleton');
        const title = document.getElementById('presidentDashboardPreviewTitle');
        const continueLink = document.getElementById('presidentDashboardPreviewContinue');
        if (!modal || !frame || !fit || !stage || !skeleton || !title || !continueLink) return;
        document.body.appendChild(modal);

        function showSkeleton(kind) {
            var pipeline = skeleton.querySelector('[data-preview-skeleton-pipeline]');
            var documentSkeleton = skeleton.querySelector('[data-preview-skeleton-document]');
            var isPipeline = kind === 'pipeline';
            if (pipeline) pipeline.classList.toggle('hidden', !isPipeline);
            if (documentSkeleton) documentSkeleton.classList.toggle('hidden', isPipeline);
            skeleton.classList.toggle('bg-white', isPipeline);
            skeleton.classList.toggle('bg-slate-100', !isPipeline);
            skeleton.classList.toggle('py-6', !isPipeline);
            skeleton.classList.remove('hidden');
        }

        function hideSkeleton() {
            skeleton.classList.add('hidden');
        }

        function resetPreviewFrame() {
            frame.style.width = '100%';
            frame.style.height = '0px';
            frame.style.transform = '';
            frame.style.transformOrigin = '';
            fit.style.width = '';
            fit.style.height = '0px';
        }

        function flowHeight(root, view) {
            var total = 0;
            var children = root.children || [];
            for (var i = 0; i < children.length; i++) {
                var style = view.getComputedStyle(children[i]);
                if (style.display === 'none' || style.position === 'absolute' || style.position === 'fixed') continue;
                total += children[i].offsetHeight;
            }
            return total;
        }

        function fitPreviewFrame() {
            var doc;
            try { doc = frame.contentDocument; } catch (e) { return; }
            if (!doc || !doc.body) return;
            var view = doc.defaultView;
            frame.style.transform = 'none';
            frame.style.width = '1400px';
            frame.style.height = '40px';
            var sheet = doc.querySelector('.ris-document, .ris-print-sheet, .rfc-print-sheet, .atp-print-sheet, .dv-print-sheet, .rr-print-sheet');
            var root = sheet || doc.body;
            root.style.minHeight = '0';
            root.style.height = 'auto';
            var docW = Math.ceil(root.scrollWidth || root.getBoundingClientRect().width || 0);
            var docH = flowHeight(root, view);
            if (docH < 40) docH = Math.ceil(root.scrollHeight || 0);
            if (docW < 40 || docH < 40) return;
            var scale = Math.min(1, Math.max(280, stage.clientWidth - 24) / docW);
            frame.style.width = docW + 'px';
            frame.style.height = docH + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            frame.style.transformOrigin = 'top left';
            fit.style.width = Math.ceil(docW * scale) + 'px';
            fit.style.height = Math.ceil(docH * scale) + 'px';
        }

        var previewCache = Object.create(null);

        function revealPreview() {
            fitPreviewFrame();
            if (fit.style.height && fit.style.height !== '0px') hideSkeleton();
        }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            const inline = frame.srcdoc || '';
            if ((!src || src === 'about:blank') && !inline) return;
            revealPreview();
            setTimeout(revealPreview, 80);
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
            frame.srcdoc = '';
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        function openPreview(trigger) {
            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const continueUrl = trigger.getAttribute('data-continue-url') || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }
            title.textContent = trigger.getAttribute('data-preview-title') || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.getAttribute('data-continue-label') || 'Open';
            continueLink.classList.toggle('hidden', !continueUrl);
            var kind = trigger.getAttribute('data-preview-skeleton') || 'document';
            var cached = kind === 'pipeline' ? (previewCache[previewUrl] || '') : '';
            showSkeleton(kind);
            resetPreviewFrame();
            if (cached) {
                frame.removeAttribute('src');
                frame.srcdoc = cached;
                requestAnimationFrame(revealPreview);
            } else {
                frame.srcdoc = '';
                frame.src = previewUrl;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-preview]');
            if (!trigger) return;
            const inner = event.target.closest('a, button');
            if (inner && inner !== trigger) return;
            openPreview(trigger);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-preview-close]')) closePreview();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });

        function rememberPreview(url) {
            if (!url || Object.prototype.hasOwnProperty.call(previewCache, url)) return;
            previewCache[url] = '';
            fetch(url, { credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.text() : ''; })
                .then(function (html) { previewCache[url] = html || ''; })
                .catch(function () { delete previewCache[url]; });
        }

        var warmNodes = Array.prototype.slice.call(document.querySelectorAll('[data-preview-skeleton="pipeline"][data-preview-url]'));
        document.querySelectorAll('[data-preview-url]').forEach(function (el) {
            if (el.getAttribute('data-preview-skeleton') === 'pipeline') return;
            warmNodes.push(el);
        });
        warmNodes.slice(0, 12).forEach(function (el) {
            rememberPreview(el.getAttribute('data-preview-url'));
        });
    })();
</script>

@endsection
