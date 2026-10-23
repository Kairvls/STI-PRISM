@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

@php
    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
@endphp

<div class="-mt-4 grid grid-cols-1 gap-4 sm:-mt-5 lg:grid-cols-3">
    <section class="pm-card p-5 lg:col-span-2">
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
                    $recordTitle = $record->authority_purchase_form_number ?: 'Compiled record';
                    $recordAtpId = (int) ($record->package_authority_purchase_id ?? 0);
                @endphp
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40"
                    data-dashboard-preview
                    data-preview-url="{{ $recordAtpId ? route('president.dashboard.preview', ['type' => 'atp', 'id' => $recordAtpId]) : '' }}"
                    data-continue-url="{{ route('president.procurement-records.show', $record->package_id) }}"
                    data-continue-label="Open"
                    data-preview-title="{{ $recordTitle }}"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-gray-900">{{ $recordTitle }}</span>
                        <span class="block truncate text-[11px] text-gray-500">{{ $record->ris_form_number ?: '—' }} · {{ \App\Support\ProcurementPaymentPath::label($record->package_payment_path ?? null) }} · {{ $recordDate }}</span>
                    </span>
                    <span class="shrink-0 text-[11px] font-semibold text-blue-600">Open</span>
                </button>
            @empty
                <p class="py-8 text-center text-xs font-medium text-gray-500">None yet</p>
            @endforelse
        </div>
        @if (collect($compiledRecords ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($compiledRecordCount ?? 0) > collect($compiledRecords ?? [])->count() ? collect($compiledRecords ?? [])->count().' latest' : 'Showing '.collect($compiledRecords ?? [])->count().' of '.(int) ($compiledRecordCount ?? 0) }}</p>
        @endif
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">To approve</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($pendingApprovalsCount ?? 0)) }}</span>
            </div>
            <a href="/president/approvals" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="Open approval queue">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-2.5">
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
                    <p class="text-xs font-medium text-gray-500">Nothing to approve</p>
                </div>
            @endforelse
        </div>
        @if ($recentRis->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($pendingApprovalsCount ?? 0) > $recentRis->count() ? $recentRis->count().' latest' : 'Showing '.$recentRis->count().' of '.(int) ($pendingApprovalsCount ?? 0) }}</p>
        @endif
    </aside>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="pm-card p-5 lg:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">Disbursement vouchers</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($pendingDvCount ?? 0)) }}</span>
            </div>
            <a href="{{ route('president.dv.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
        </div>
        <div class="mt-4 divide-y divide-slate-100">
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
                <p class="py-8 text-center text-xs font-medium text-gray-500">Nothing to approve</p>
            @endforelse
        </div>
        @if (collect($pendingDvs ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($pendingDvCount ?? 0) > collect($pendingDvs ?? [])->count() ? collect($pendingDvs ?? [])->count().' latest' : 'Showing '.collect($pendingDvs ?? [])->count().' of '.(int) ($pendingDvCount ?? 0) }}</p>
        @endif
    </section>

    <aside class="pm-card p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">To notify</h2>
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
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="pm-card p-5 lg:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">Recently approved</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format((int) ($approvedDecisionsCount ?? 0)) }}</span>
            </div>
            <a href="/president/reports/approved" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
        </div>
        <div class="mt-4 space-y-1">
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
                        @if (!empty($ris->awaiting_notify))
                            <span class="inline-flex shrink-0 items-center rounded-xl bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">To notify</span>
                        @endif
                    </button>
                    <button type="button" class="icon-btn shrink-0" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-xs font-medium text-gray-500">None yet</p>
            @endforelse
        </div>
        @if (collect($recentlyApprovedRis ?? [])->isNotEmpty())
            <p class="mt-3 text-xs text-slate-500">{{ (int) ($approvedDecisionsCount ?? 0) > collect($recentlyApprovedRis ?? [])->count() ? collect($recentlyApprovedRis ?? [])->count().' latest' : 'Showing '.collect($recentlyApprovedRis ?? [])->count().' of '.(int) ($approvedDecisionsCount ?? 0) }}</p>
        @endif
    </section>

    <aside class="pm-card p-5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900">Approved without you</h2>
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
        <div class="relative h-[70vh] bg-slate-100">
            <iframe id="presidentDashboardPreviewFrame" title="Form preview" class="h-full w-full border-0 bg-slate-100" src="about:blank"></iframe>
            <div id="presidentDashboardPreviewSkeleton" class="absolute inset-0 z-10 hidden overflow-auto bg-slate-100 py-6">
                @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="presidentDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script>
    window.printRisDocument = function (risId) {
        if (!risId) return;
        var url = '/president/ris/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();
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
        const skeleton = document.getElementById('presidentDashboardPreviewSkeleton');
        const title = document.getElementById('presidentDashboardPreviewTitle');
        const continueLink = document.getElementById('presidentDashboardPreviewContinue');
        if (!modal || !frame || !skeleton || !title || !continueLink) return;
        document.body.appendChild(modal);

        function showSkeleton() {
            skeleton.classList.remove('hidden');
        }

        function hideSkeleton() {
            skeleton.classList.add('hidden');
        }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            if (!src || src === 'about:blank') return;
            hideSkeleton();
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
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
            showSkeleton();
            const joiner = previewUrl.indexOf('?') === -1 ? '?' : '&';
            frame.src = previewUrl + joiner + 'ts=' + Date.now();
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
    })();
</script>

@endsection
