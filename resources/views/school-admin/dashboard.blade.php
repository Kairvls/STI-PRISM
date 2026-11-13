@extends('layouts.admin-layout')

@section('title', 'School Administrator Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $pipelineStageNames = \App\Support\AdminPipeline::STAGE_NAMES;

    $pendingRisTotal = (int) ($stats['pending_ris'] ?? 0);
    $signRisTotal = (int) ($stats['awaiting_issued_by'] ?? 0);
    $combinedRisTotal = $pendingRisTotal + $signRisTotal;
    $combinedRisRows = collect($pendingRisList ?? [])
        ->map(function ($row) {
            $row->_ris_kind = 'accept';
            $row->_ris_date = $row->ris_requested_by_date ?? null;

            return $row;
        })
        ->concat(collect($awaitingSignList ?? [])->map(function ($row) {
            $row->_ris_kind = 'sign';
            $row->_ris_date = $row->ris_approved_by_date ?? null;

            return $row;
        }))
        ->sortBy(fn ($row) => $row->_ris_date ? \Carbon\Carbon::parse($row->_ris_date)->timestamp : PHP_INT_MAX)
        ->values()
        ->take(6);
    $combinedRisUrl = $pendingRisTotal > 0
        ? route('school-admin.procurement-review', ['filter' => 'pending', 'focus' => \App\Support\AdminAttentionSummary::FOCUS_PENDING_REVIEW])
        : route('school-admin.digital-signatures.sign-ris', ['filter' => 'for_cosign']);
@endphp

<style>
    .sa-alert { color: #b45309; }
</style>

<div class="admin-page space-y-6">
    <h2 class="text-base font-semibold text-gray-950">Your approvals</h2>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="pur-card">
            @include('school-admin.partials.dashboard-list-header', [
                'title' => 'RIS — Needs action',
                'total' => $combinedRisTotal,
                'rows' => $combinedRisRows,
                'url' => $combinedRisUrl,
                'hint' => $pendingRisTotal > 0 && $signRisTotal > 0
                    ? $pendingRisTotal.' pending accept · '.$signRisTotal.' ready to sign'
                    : ($signRisTotal > 0 ? 'Sign Issued by after President approval' : 'Accept RIS waiting for review'),
            ])
            <ul class="divide-y divide-gray-100">
                @forelse($combinedRisRows as $row)
                    @php
                        $kind = $row->_ris_kind ?? 'accept';
                        $dateValue = $row->_ris_date ?? null;
                        $since = ! empty($dateValue)
                            ? \Carbon\Carbon::parse($dateValue)->startOfDay()
                            : null;
                        $age = $since ? (int) $since->diffInDays(now()->startOfDay()) : null;
                        if ($kind === 'sign') {
                            $who = \App\Support\RoleHolderLabel::who(
                                \App\Support\WorkflowNotifier::ROLE_PRESIDENT,
                                $row->president_name ?? $row->ris_approved_by ?? null,
                                ''
                            );
                            $statusText = $who !== '' ? 'Approved by '.$who : 'Approved by the President';
                            $badge = 'Ready to sign';
                            $badgeClass = 'border-blue-200 bg-blue-50 text-blue-700';
                        } else {
                            $reviewerName = trim((string) ($row->assigned_reviewer_name ?? ''));
                            $currentName = trim((string) (auth()->user()->user_full_name ?? ''));
                            $who = \App\Support\RoleHolderLabel::who(
                                \App\Support\WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                                $reviewerName !== '' ? $reviewerName : null,
                                'the School Administrator'
                            );
                            $isYou = strcasecmp($who, 'the School Administrator') === 0
                                || ($currentName !== '' && strcasecmp($who, $currentName) === 0)
                                || ($reviewerName !== '' && $currentName !== '' && strcasecmp($reviewerName, $currentName) === 0);
                            $statusText = 'Under review by '.($isYou ? 'you' : ($who !== '' ? $who : 'the School Administrator'));
                            $badge = 'Pending accept';
                            $badgeClass = 'border-amber-200 bg-amber-50 text-amber-700';
                        }
                    @endphp
                    <li>
                        <div
                            role="button"
                            tabindex="0"
                            onclick="if (event.target.closest('a')) return; window.openDashboardRisPreview(this)"
                            onkeydown="if (event.key === 'Enter' && !event.target.closest('a')) { event.preventDefault(); window.openDashboardRisPreview(this) }"
                            data-ris-id="{{ (int) $row->ris_id }}"
                            data-ris-number="{{ $row->ris_form_number ?: \App\Support\RisWorkflow::formNumber($row) }}"
                            data-kind="{{ $kind }}"
                            title="Preview this RIS"
                            class="flex w-full cursor-pointer items-start justify-between gap-3 px-5 py-3.5 text-left transition hover:bg-gray-50/70"
                        >
                            <div class="min-w-0">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ \App\Support\RisWorkflow::formNumber($row) }}</p>
                                    <span class="inline-flex items-center rounded-md border px-1.5 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">{{ $badge }}</span>
                                </div>
                                @if(!blank($row->ris_request_no ?? null))
                                    <p class="mt-0.5">
                                        @include('partials.procurement-request-no', ['requestNo' => $row->ris_request_no, 'inline' => true, 'showLabel' => false])
                                    </p>
                                @endif
                                <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</p>
                            </div>
                            <div class="max-w-[10.5rem] shrink-0 text-right">
                                <p class="text-[11px] font-semibold leading-4 text-gray-700">{{ $statusText }}</p>
                                @if($age !== null)
                                    <p class="mt-0.5 text-[11px] {{ $age >= 3 ? 'sa-alert font-semibold' : 'text-gray-400' }}">
                                        @if($kind === 'sign')
                                            {{ $age === 0 ? 'Arrived today' : 'Waiting '.$age.' '.\Illuminate\Support\Str::plural('day', $age) }}
                                        @else
                                            {{ $age === 0 ? 'Today' : $age.'d waiting' }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-gray-400">No RIS waiting for accept or Issued by signature.</li>
                @endforelse
            </ul>
        </div>

        @foreach($approvalQueues as $queue)
            <div class="pur-card">
                @include('school-admin.partials.dashboard-list-header', [
                    'title' => $queue['title'],
                    'total' => $queue['total'],
                    'rows' => $queue['rows'],
                    'url' => $queue['url'],
                ])
                <ul class="divide-y divide-gray-100">
                    @forelse($queue['rows'] as $row)
                        @php
                            $since = ! empty($row->date) ? \Carbon\Carbon::parse($row->date)->startOfDay() : null;
                            $age = $since ? (int) $since->diffInDays(now()->startOfDay()) : null;
                            $docType = (string) ($row->type ?? '');
                            $docId = (int) ($row->id ?? 0);
                            $canPreview = $docType !== '' && $docId > 0;
                        @endphp
                        <li>
                            <div
                                @if($canPreview)
                                    role="button"
                                    tabindex="0"
                                    onclick="if (event.target.closest('a')) return; window.dispatchEvent(new CustomEvent('open-procurement-document', { detail: { type: this.dataset.docType, id: Number(this.dataset.docId), label: this.dataset.docLabel, continueUrl: this.dataset.continueUrl, continueLabel: this.dataset.continueLabel } }))"
                                    onkeydown="if (event.key === 'Enter' && !event.target.closest('a')) { event.preventDefault(); this.click(); }"
                                    data-doc-type="{{ $docType }}"
                                    data-doc-id="{{ $docId }}"
                                    data-doc-label="{{ $row->label }}"
                                    data-continue-url="{{ $row->url }}"
                                    data-continue-label="{{ $row->continue_label ?? 'Open Pending Approval' }}"
                                    title="Preview this form"
                                    class="flex w-full cursor-pointer items-start justify-between gap-3 px-5 py-3.5 text-left transition hover:bg-gray-50/70"
                                @else
                                    class="flex w-full items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70"
                                @endif
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $row->label }}</p>
                                    @if(!blank($row->request_no))
                                        <p class="mt-0.5">
                                            @include('partials.procurement-request-no', ['requestNo' => $row->request_no, 'inline' => true, 'showLabel' => false])
                                        </p>
                                    @endif
                                    <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) $row->amount, 2) }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] font-semibold text-gray-700">{{ $row->status }}</p>
                                    @if($age !== null)
                                        <p class="mt-0.5 text-[11px] {{ $age >= 3 ? 'sa-alert font-semibold' : 'text-gray-400' }}">
                                            {{ $age === 0 ? 'Today' : $age.'d waiting' }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-sm text-gray-400">{{ $queue['empty'] }}</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    <div>
        <h2 class="text-base font-semibold text-gray-950 -mb-4">Operations overview</h2>
        <!--<p class="mt-1 text-xs text-gray-400">Equipment, procurement, schedules, and inspections across the campus.</p>-->
    </div>

    <div class="pur-card">
        @include('school-admin.partials.dashboard-list-header', [
            'title' => 'Latest requests',
            'total' => $stats['open_ris'],
            'rows' => $latestProcurements,
            'url' => route('school-admin.operations.procurement', ['filter' => 'open']),
        ])
        <ul class="divide-y divide-gray-100">
            @forelse($latestProcurements as $row)
                @php
                    $pipeline = $row->pipeline ?? [];
                    $stages = $pipeline['stages'] ?? [];
                    $current = $pipeline['current_stage'] ?? 'ris';
                    $stageKeys = \App\Support\AdminPipeline::STAGE_ORDER;
                    $currentIndex = array_search($current, $stageKeys, true) ?: 0;
                    $formNumber = \App\Support\RisWorkflow::formNumber($row);
                    $requestNo = trim((string) ($row->ris_request_no ?? ($pipeline['request_no'] ?? '')));
                    $whereNow = ($pipeline['current_hint'] ?? null) ?: \App\Support\RisWorkflow::statusLabel($row);
                    $displayLabel = $requestNo !== '' ? $requestNo : $formNumber;
                @endphp
                <li>
                    <div
                        role="button"
                        tabindex="0"
                        data-ris-id="{{ (int) $row->ris_id }}"
                        data-ris-label="{{ $displayLabel }}"
                        onclick="window.dispatchEvent(new CustomEvent('open-procurement-pipeline', { detail: { id: Number(this.dataset.risId), label: this.dataset.risLabel } }))"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"
                        title="Show where this request is in the procurement process"
                        class="grid w-full cursor-pointer gap-3 px-5 py-4 text-left transition hover:bg-gray-50/70 md:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] md:items-center md:gap-6"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $displayLabel ?: 'Request' }}</p>
                            @if($requestNo !== '' && $formNumber !== '')
                                <p class="mt-0.5 text-[11px] text-gray-400">{{ $formNumber }}</p>
                            @endif
                            <p class="mt-1 text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</p>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center">
                                @foreach($stageKeys as $i => $key)
                                    @php
                                        $stage = $stages[$key] ?? [];
                                        $exists = !empty($stage['exists']) && !empty($stage['id']);
                                        $isCurrent = $key === $current;
                                        $stageTitle = $pipelineStageNames[$key] ?? strtoupper($key);
                                        $docLabel = $exists ? ($stage['label'] ?? $stageTitle) : $stageTitle;
                                    @endphp
                                    @if($exists)
                                        <button
                                            type="button"
                                            class="flex flex-col items-center rounded-md px-0.5 transition hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                            title="Open {{ $docLabel }}"
                                            data-doc-type="{{ $key }}"
                                            data-doc-id="{{ (int) $stage['id'] }}"
                                            data-doc-label="{{ $docLabel }}"
                                            onclick="event.stopPropagation(); window.dispatchEvent(new CustomEvent('open-procurement-document', { detail: { type: this.dataset.docType, id: Number(this.dataset.docId), label: this.dataset.docLabel } }))"
                                        >
                                            <span class="h-2.5 w-2.5 rounded-full {{ $isCurrent ? 'bg-[#0025cc] ring-4 ring-blue-100' : 'bg-slate-400' }}"></span>
                                            <span class="mt-1.5 text-[10px] font-semibold underline-offset-2 hover:underline {{ $isCurrent ? 'text-[#0025cc]' : 'text-gray-500' }}">{{ strtoupper($key) }}</span>
                                        </button>
                                    @else
                                        <div class="flex flex-col items-center" title="{{ $stageTitle }} (not started)">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $isCurrent ? 'bg-[#0025cc] ring-4 ring-blue-100' : 'bg-gray-200' }}"></span>
                                            <span class="mt-1.5 text-[10px] font-semibold {{ $isCurrent ? 'text-[#0025cc]' : 'text-gray-300' }}">{{ strtoupper($key) }}</span>
                                        </div>
                                    @endif
                                    @if(!$loop->last)
                                        <span class="mx-1 mb-4 h-px flex-1 {{ $i < $currentIndex ? 'bg-slate-400' : 'bg-gray-200' }}"></span>
                                    @endif
                                @endforeach
                            </div>
                            <p class="mt-1 truncate text-xs text-gray-500">
                                Now at <span class="font-semibold text-gray-700">{{ $pipelineStageNames[$current] ?? strtoupper($current) }}</span>@if($whereNow) · {{ $whereNow }}@endif
                            </p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-sm text-gray-400">No procurement in progress right now.</li>
            @endforelse
        </ul>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @php
            $overdueBorrowsUrl = route('school-admin.operations.borrowing', ['filter' => 'Overdue']);
        @endphp
        <div class="pur-card">
            @include('school-admin.partials.dashboard-list-header', [
                'title' => 'Overdue equipment borrows',
                'total' => $stats['overdue_borrows'],
                'rows' => $overdueBorrows,
                'url' => $overdueBorrowsUrl,
            ])
            <ul class="divide-y divide-gray-100">
                @forelse($overdueBorrows as $row)
                    @php
                        $expected = \Carbon\Carbon::parse($row->borrowing_expected_return_date)->startOfDay();
                        $days = (int) $expected->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ route('school-admin.operations.borrowing', ['filter' => 'Overdue', 'record' => $row->borrowing_record_id]) }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $row->equipment_name ?: 'Equipment' }}
                                    <span class="ml-1 text-xs font-normal text-gray-400">{{ $row->equipment_asset_tag ?: 'No asset tag' }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->borrowing_borrower_name ?: 'Unknown borrower').' · Due '.$expected->format('M j, Y') }}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold sa-alert">{{ $days }}d overdue</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-gray-400">No overdue borrows.</li>
                @endforelse
            </ul>
        </div>

        <div class="pur-card">
            @include('school-admin.partials.dashboard-list-header', [
                'title' => 'Overdue maintenance schedules',
                'total' => $stats['overdue_schedules'],
                'rows' => $overdueSchedules,
                'url' => route('school-admin.operations.schedules', ['filter' => 'overdue']),
            ])
            <ul class="divide-y divide-gray-100">
                @forelse($overdueSchedules as $row)
                    @php
                        $due = \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->startOfDay();
                        $days = (int) $due->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ route('school-admin.operations.schedules', ['filter' => 'overdue', 'record' => $row->maintenance_schedule_id]) }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $row->equipment_name ?: ($row->maintenance_schedule_title ?: 'Schedule') }}
                                    @if($row->equipment_name)
                                        <span class="ml-1 text-xs font-normal text-gray-400">{{ $row->equipment_asset_tag ?: 'No asset tag' }}</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->room_name ?: 'No room').' · Due '.$due->format('M j, Y') }}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold sa-alert">{{ $days }}d overdue</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-gray-400">No overdue schedules.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@include('admin.partials.ris-preview-modal', [
    'modalId' => 'dashboardRisPreviewModal',
    'iframeId' => 'dashboardRisPreviewIframe',
    'closeFn' => 'closeDashboardRisPreview',
    'printFn' => 'printDashboardRisPreview',
    'zIndex' => '11000',
    'footerView' => 'school-admin.partials.dashboard-ris-preview-footer',
])

@include('admin.operations.partials.pipeline-drawer')

<script>
(function () {
    var risBaseUrl = @js(\App\Support\AdminPortal::url('procurement-review/ris'));
    var procurementUrl = @js(route('school-admin.procurement-review'));
    var signRisUrl = @js(route('school-admin.digital-signatures.sign-ris'));
    var canActOnRis = @json(\App\Support\AdminPortal::canActOnRis());
    var modalId = 'dashboardRisPreviewModal';
    var iframeId = 'dashboardRisPreviewIframe';

    function rescale() {
        if (typeof window.scaleRisPreviewIframe === 'function') {
            window.scaleRisPreviewIframe(iframeId);
        }
    }

    function configureFooter(modal, risId, risNumber, kind) {
        var moduleLink = modal.querySelector('[data-dashboard-ris-module]');
        var acceptForm = modal.querySelector('[data-dashboard-ris-accept-form]');
        var isAccept = kind === 'accept';
        var params = new URLSearchParams();

        if (moduleLink) {
            if (isAccept) {
                if (risNumber) params.set('search', risNumber);
                moduleLink.href = procurementUrl + (params.toString() ? '?' + params.toString() : '');
                moduleLink.textContent = 'Open Pending Accept';
                moduleLink.className = 'rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50';
            } else {
                params.set('filter', 'for_cosign');
                if (risNumber) params.set('search', risNumber);
                moduleLink.href = signRisUrl + '?' + params.toString();
                moduleLink.textContent = 'Open Ready to Sign';
                moduleLink.className = 'rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800';
            }
        }

        if (acceptForm) {
            var showAccept = isAccept && canActOnRis;
            acceptForm.classList.toggle('hidden', !showAccept);
            acceptForm.action = showAccept ? risBaseUrl + '/' + encodeURIComponent(risId) + '/accept' : '';
            var submitBtn = acceptForm.querySelector('button[type=submit]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Accept & continue';
            }
        }
    }

    window.openDashboardRisPreview = function (trigger) {
        var modal = document.getElementById(modalId);
        var iframe = document.getElementById(iframeId);
        if (!modal || !iframe || !trigger) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        var risId = trigger.getAttribute('data-ris-id');
        var risNumber = trigger.getAttribute('data-ris-number') || '';
        var kind = trigger.getAttribute('data-kind') || 'accept';

        configureFooter(modal, risId, risNumber, kind);

        iframe.onload = function () {
            rescale();
            setTimeout(rescale, 60);
            setTimeout(rescale, 250);
        };
        iframe.src = risBaseUrl + '/' + encodeURIComponent(risId) + '/print';

        if (typeof window.fillRisPreviewAttachments === 'function') {
            window.fillRisPreviewAttachments(risId, modalId);
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (kind === 'accept' && canActOnRis && csrfToken) {
            fetch(risBaseUrl + '/' + encodeURIComponent(risId) + '/review', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(function () {});
        }

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeDashboardRisPreview = function () {
        var modal = document.getElementById(modalId);
        var iframe = document.getElementById(iframeId);

        if (typeof window.exitRisPreviewFullscreen === 'function') {
            window.exitRisPreviewFullscreen(modalId, iframeId);
        }
        if (iframe) iframe.src = 'about:blank';
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = '';
        }
        document.body.style.overflow = '';
    };

    window.printDashboardRisPreview = function () {
        var iframe = document.getElementById(iframeId);
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || event.defaultPrevented) return;
        var modal = document.getElementById(modalId);
        if (!modal || modal.classList.contains('hidden')) return;
        window.closeDashboardRisPreview();
    });
})();
</script>
@endsection
