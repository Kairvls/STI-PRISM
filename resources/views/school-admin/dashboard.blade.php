@extends('layouts.admin-layout')

@section('title', 'School Administrator Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $actionMetrics = [
        [
            'label' => 'Accepted, Needs Your Next Step',
            'hint' => '₱'.number_format((float) $stats['ready_to_forward_amount'], 2).' total',
            'value' => number_format($stats['ready_to_forward']),
            'href' => route('school-admin.digital-signatures.sign-ris', ['filter' => 'for_decision']),
            'title' => 'You accepted these RIS. Send them to the President or approve them yourself.',
        ],
        [
            'label' => 'Waiting for the President',
            'hint' => '₱'.number_format((float) $stats['with_president_amount'], 2).' total',
            'value' => number_format($stats['with_president']),
            'href' => route('school-admin.operations.procurement', ['filter' => \App\Support\RisWorkflow::FORWARDED]),
            'title' => 'You sent these RIS to the President. Nothing to do until the President approves or rejects them.',
        ],
        [
            'label' => 'Sent Back or Rejected',
            'hint' => '₱'.number_format((float) $stats['amend_ris_amount'], 2).' total',
            'value' => number_format($stats['amend_ris']),
            'href' => route('school-admin.procurement-review', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_AMENDMENTS]),
            'title' => 'RIS you returned to the Purchaser for corrections, or rejected.',
        ],
    ];

    $pipelineStageNames = [
        'ris' => 'Requisition and Issue Slip',
        'atp' => 'Authority to Purchase',
        'rfc' => 'Request for Check',
        'rr' => 'Receiving Report',
        'liq' => 'Liquidation',
    ];

    $risPanels = [
        [
            'title' => 'RIS Requests - Waiting for your acceptance',
            'url' => route('school-admin.procurement-review', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_PENDING_REVIEW]),
            'total' => $stats['pending_ris'],
            'empty' => 'No RIS waiting for acceptance.',
            'rows' => $pendingRisList,
            'kind' => 'accept',
            'hint' => null,
            'dateField' => 'ris_requested_by_date',
            'agePrefix' => null,
            'statusText' => null,
        ],
        [
            'title' => 'RIS - Approved by the President: ready for your signature',
            
            'url' => route('school-admin.digital-signatures.sign-ris', ['filter' => 'for_cosign']),
            'total' => $stats['awaiting_issued_by'],
            'empty' => 'Nothing to sign right now. RIS approved by the President will appear here.',
            'rows' => $awaitingSignList,
            'kind' => 'sign',
            'dateField' => 'ris_approved_by_date',
            'agePrefix' => 'Waiting',
            'statusText' => 'Approved by President',
        ],
    ];
@endphp

<style>
    .sa-alert { color: #b45309; }
</style>

<div class="admin-page space-y-6">
    <div>
        <h2 class="text-base font-semibold text-gray-950 -mb-4">Your approvals</h2>
        <!--<p class="mt-1 text-xs text-gray-400">RIS submitted by the Purchaser that need the School Administrator.</p>-->
    </div>

    @include('layouts.partials.maintenance-stat-cards', ['cards' => $actionMetrics])

    <div class="grid gap-4 md:grid-cols-2">
        @foreach($risPanels as $panel)
            <div class="pur-card">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-950">{{ $panel['title'] }}</h3>
                        @if(!empty($panel['hint']))
                            <p class="mt-0.5 text-xs text-gray-400">{{ $panel['hint'] }}</p>
                        @endif
                    </div>
                    <a href="{{ $panel['url'] }}" class="shrink-0 text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">
                        {{ $panel['agePrefix'] ? 'View all ('.$panel['total'].')' : 'View all ('.$panel['total'].')' }}
                    </a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse($panel['rows'] as $row)
                        @php
                            $dateValue = $row->{$panel['dateField']} ?? null;
                            $since = ! empty($dateValue)
                                ? \Carbon\Carbon::parse($dateValue)->startOfDay()
                                : null;
                            $age = $since ? (int) $since->diffInDays(now()->startOfDay()) : null;
                        @endphp
                        <li>
                            <button
                                type="button"
                                onclick="window.openDashboardRisPreview(this)"
                                data-ris-id="{{ (int) $row->ris_id }}"
                                data-ris-number="{{ $row->ris_form_number ?: \App\Support\RisWorkflow::formNumber($row) }}"
                                data-kind="{{ $panel['kind'] }}"
                                title="Preview this RIS"
                                class="flex w-full items-start justify-between gap-3 px-5 py-3.5 text-left transition hover:bg-gray-50/70"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ \App\Support\RisWorkflow::formNumber($row) }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($row->ris_purpose_description ?: 'No purpose noted', 60) }}
                                        · ₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] font-semibold text-gray-700">{{ $panel['statusText'] ?? \App\Support\RisWorkflow::statusLabel($row) }}</p>
                                    @if($age !== null)
                                        <p class="mt-0.5 text-[11px] {{ $age >= 3 ? 'sa-alert font-semibold' : 'text-gray-400' }}">
                                            @if($panel['agePrefix'])
                                                {{ $age === 0 ? 'Arrived today' : $panel['agePrefix'].' '.$age.' '.\Illuminate\Support\Str::plural('day', $age) }}
                                            @else
                                                {{ $age === 0 ? 'Today' : $age.'d waiting' }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </button>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-sm text-gray-400">{{ $panel['empty'] }}</li>
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
        <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-950">3 Latest procurement workflow</h3>
                <!--<p class="mt-0.5 text-xs text-gray-400">Where the most recent RIS are in the purchasing process.</p>-->
            </div>
            <a href="{{ route('school-admin.operations.procurement', ['filter' => 'open']) }}" class="shrink-0 text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">
                View all ({{ $stats['open_ris'] }} open)
            </a>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse($latestProcurements as $row)
                @php
                    $pipeline = $row->pipeline ?? [];
                    $stages = $pipeline['stages'] ?? [];
                    $current = $pipeline['current_stage'] ?? 'ris';
                    $stageKeys = \App\Support\AdminPipeline::STAGE_ORDER;
                    $currentIndex = array_search($current, $stageKeys, true) ?: 0;
                    $formNumber = \App\Support\RisWorkflow::formNumber($row);
                    $whereNow = ($pipeline['current_hint'] ?? null) ?: \App\Support\RisWorkflow::statusLabel($row);
                @endphp
                <li>
                    <a
                        href="{{ route('school-admin.operations.procurement', ['q' => $row->ris_form_number ?: $formNumber]) }}"
                        class="grid gap-3 px-5 py-4 transition hover:bg-gray-50/70 md:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] md:items-center md:gap-6"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $formNumber }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-400">
                                {{ \Illuminate\Support\Str::limit($row->ris_purpose_description ?: 'No purpose noted', 70) }}
                                · ₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center">
                                @foreach($stageKeys as $i => $key)
                                    @php
                                        $exists = !empty(($stages[$key] ?? [])['exists']);
                                        $isCurrent = $key === $current;
                                    @endphp
                                    <div class="flex flex-col items-center" title="{{ $pipelineStageNames[$key] }}{{ $exists ? '' : ' (not started)' }}">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $isCurrent ? 'bg-[#0025cc] ring-4 ring-blue-100' : ($exists ? 'bg-slate-400' : 'bg-gray-200') }}"></span>
                                        <span class="mt-1.5 text-[10px] font-semibold {{ $isCurrent ? 'text-[#0025cc]' : ($exists ? 'text-gray-500' : 'text-gray-300') }}">{{ strtoupper($key) }}</span>
                                    </div>
                                    @if(!$loop->last)
                                        <span class="mx-1 mb-4 h-px flex-1 {{ $i < $currentIndex ? 'bg-slate-400' : 'bg-gray-200' }}"></span>
                                    @endif
                                @endforeach
                            </div>
                            <p class="mt-1 truncate text-xs text-gray-500">
                                Now at <span class="font-semibold text-gray-700">{{ $pipelineStageNames[$current] ?? strtoupper($current) }}</span>@if($whereNow) · {{ $whereNow }}@endif
                            </p>
                        </div>
                    </a>
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
            <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-950">4 Latest overdue equipment borrows</h3>
                <a href="{{ $overdueBorrowsUrl }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">View all ({{ $stats['overdue_borrows'] }})</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($overdueBorrows as $row)
                    @php
                        $expected = \Carbon\Carbon::parse($row->borrowing_expected_return_date)->startOfDay();
                        $days = (int) $expected->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ $overdueBorrowsUrl }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $row->equipment_name ?: 'Equipment' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->borrowing_borrower_name ?: 'Unknown borrower').' · Due '.$expected->format('M j') }}</p>
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
            <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-950">4 Latest overdue maintenance schedules</h3>
                <a href="{{ route('school-admin.operations.schedules', ['filter' => 'overdue']) }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">View all ({{ $stats['overdue_schedules'] }})</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($overdueSchedules as $row)
                    @php
                        $due = \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->startOfDay();
                        $days = (int) $due->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ route('school-admin.operations.schedules', ['filter' => 'overdue']) }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $row->equipment_name ?: ($row->maintenance_schedule_title ?: 'Schedule') }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->room_name ?: 'No room').' · Due '.$due->format('M j') }}</p>
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
                moduleLink.textContent = 'Open in Procurement Requests';
                moduleLink.className = 'rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50';
            } else {
                params.set('filter', 'for_cosign');
                if (risNumber) params.set('search', risNumber);
                moduleLink.href = signRisUrl + '?' + params.toString();
                moduleLink.textContent = 'Go to Sign RIS';
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
        iframe.src = risBaseUrl + '/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();

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
