@extends('layouts.receiving-layout')

@section('content')
<div
    x-data="{
        viewOpen: false,
        selectedRr: null,
        actionMenu: null,
        actionMenuPos: { top: 0, left: 0 },
        reviseOpen: false,
        returnOpen: false,
        remarksRr: null,
        signOpen: false,
        signRr: null,
        signDirty: false,
        closeSignConfirm: false,
        requestCloseSign() {
            if (!this.signDirty) {
                this.signOpen = false;
                return;
            }
            this.closeSignConfirm = true;
        },
        confirmCloseSign() {
            this.signDirty = false;
            this.closeSignConfirm = false;
            this.signOpen = false;
        },
        cancelCloseSign() {
            this.closeSignConfirm = false;
        },
        openView(id) { this.selectedRr = id; this.viewOpen = true; },
        openRevise(id) {
            this.remarksRr = id;
            this.reviseOpen = true;
            this.returnOpen = false;
            this.signOpen = false;
            this.$nextTick(() => {
                const el = document.getElementById('reviseRemarks');
                if (el) { el.value = ''; el.focus(); }
                if (window.resetRevisionImagePicker) window.resetRevisionImagePicker('rrReviseImages');
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            });
        },
        openReturn(id) {
            this.remarksRr = id;
            this.returnOpen = true;
            this.reviseOpen = false;
            this.signOpen = false;
            this.$nextTick(() => {
                const el = document.getElementById('returnRemarks');
                if (el) { el.value = ''; el.focus(); }
                if (window.resetRevisionImagePicker) window.resetRevisionImagePicker('rrReturnImages');
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            });
        },
        openSign(id) {
            this.signRr = id;
            this.signDirty = false;
            this.signOpen = true;
            this.viewOpen = false;
            this.reviseOpen = false;
            this.returnOpen = false;
            this.$nextTick(() => {
                if (window.receivingSignaturePanel && typeof window.receivingSignaturePanel.bind === 'function') {
                    window.receivingSignaturePanel.bind(id);
                }
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            });
        },
        printRr(id) {
            if (typeof window.receivingBrowserPrintRr === 'function') {
                window.receivingBrowserPrintRr(id);
                return;
            }
            const sheetId = 'rr-print-' + id;
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'rr-print-active');
                return;
            }
            document.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                sheet.classList.remove('rr-print-active');
            });
            const sheet = document.getElementById(sheetId);
            if (sheet) sheet.classList.add('rr-print-active');
            window.print();
        }
    }"
    
>
    <div class="space-y-6">
        @php
            $focusKey = $attentionFocus['key'] ?? null;
            $statusFilters = [
                'all' => 'All',
                'queue' => 'For Second Count',
            ];
            $activeStatus = $focusKey === 'leftover' || (int) request('rr') > 0 ? null : $filter;
        @endphp
        @if(!empty($dateFilter))
            <p class="inline-flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50/80 px-3.5 py-2 text-xs font-medium text-blue-800">
                Showing reports for {{ \Carbon\Carbon::parse($dateFilter)->format('M d, Y') }}
                <a href="{{ route('receiving.rr.index', ['status' => $filter]) }}" class="font-semibold underline decoration-blue-300 underline-offset-2 hover:text-blue-950">Clear date</a>
            </p>
        @endif

        @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $reports->total()])

        <form method="GET" action="{{ route('receiving.rr.index') }}" data-auto-filter data-list-panel="#roRrTable" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <input type="hidden" name="status" value="{{ $filter }}">
            @if($focusKey)
                <input type="hidden" name="focus" value="{{ $focusKey }}">
            @endif
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex shrink-0 items-center gap-2.5">
                        <h3 class="text-sm font-semibold text-slate-950">Receiving reports records</h3>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $reports->total() }} total
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roRrViewSwitcher',
                            'btnClass' => 'ro-rr-view-btn',
                        ])
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <nav
                        class="flex items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        role="tablist"
                        aria-label="Receiving report status filters"
                    >
                        @foreach ($statusFilters as $key => $label)
                            @php $tabCount = $key === 'queue' ? (int) ($counts['queue'] ?? 0) : null; @endphp
                            <a
                                href="{{ route('receiving.rr.index', array_filter(['status' => $key, 'date' => $dateFilter ?? null, 'search' => request('search') ?: null])) }}"
                                role="tab"
                                aria-selected="{{ $activeStatus === $key ? 'true' : 'false' }}"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm transition {{ $activeStatus === $key ? 'bg-slate-100 font-medium text-slate-950' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}"
                            >
                                <span>{{ $label }}</span>
                                @if($tabCount !== null && $tabCount > 0)
                                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[11px] font-bold tabular-nums" style="background:#eef2ff;color:#4338ca;">{{ $tabCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <div class="relative">
                            <button
                                type="button"
                                onclick="const el = document.getElementById('roRrDateInput'); if (el && typeof el.showPicker === 'function') { el.showPicker(); } else if (el) { el.focus(); el.click(); }"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border transition {{ !empty($dateFilter) ? 'border-blue-300 bg-blue-50 text-[#0025cc]' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                                data-tip="{{ !empty($dateFilter) ? 'Filtered: '.\Carbon\Carbon::parse($dateFilter)->format('M d, Y') : 'Filter by date' }}"
                                data-tip-place="above"
                                aria-label="Filter by date"
                            >
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                            </button>
                            <input
                                type="date"
                                id="roRrDateInput"
                                name="date"
                                value="{{ $dateFilter ?? '' }}"
                                onchange="this.form.submit()"
                                class="pointer-events-none absolute left-0 top-0 h-10 w-10 opacity-0"
                                tabindex="-1"
                                aria-hidden="true"
                            >
                        </div>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search RR, RFC, or received from"
                                class="h-10 w-64 rounded-xl border border-slate-200 bg-white pl-9 pr-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                            >
                        </div>
                        @if(request()->filled('search') || !empty($dateFilter))
                            <a href="{{ route('receiving.rr.index', array_filter(['status' => $filter, 'focus' => $focusKey])) }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div id="roRrTable" data-list-panel-root class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">RR No.</th>
                            <th class="px-5 py-3.5">Request No.</th>
                            <th class="px-5 py-3.5">RFC</th>
                            <th class="px-5 py-3.5">Received from</th>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reports as $rr)
                            @php
                                $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
                                $displayStatus = \App\Support\ProcurementStatus::label($rr->receiving_report_status, 'rr');
                                $requestNo = trim((string) ($rr->receiving_report_request_no ?? $rr->request_check_request_no ?? ''));
                            @endphp
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-950">{{ $rr->receiving_report_form_number }}</p>
                                        @include('partials.ris-urgency-badge', [
                                            'urgent' => \App\Support\DocumentUrgency::isUrgent('RR', $rr),
                                            'size' => 'sm',
                                            'title' => 'Inherited from linked RIS',
                                        ])
                                        @include('partials.payment-method-badge', [
                                            'path' => $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null),
                                            'size' => 'sm',
                                        ])
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($requestNo !== '')
                                        @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $rr->request_check_form_number ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $rr->receiving_report_received_from ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    @php
                                        $rrDateRaw = $rr->receiving_report_date ?? $rr->receiving_report_submitted_at ?? $rr->receiving_report_created_at ?? null;
                                    @endphp
                                    {{ $rrDateRaw ? \Carbon\Carbon::parse($rrDateRaw)->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $roRawStatus = trim((string) ($rr->receiving_report_status ?? ''));
                                        $roBadge = match (true) {
                                            in_array($roRawStatus, ['Pending', 'Submitted', 'Resubmitted', 'Under Review', 'In Review'], true) => [
                                                'pill' => 'Need Second Count',
                                                'sub' => 'Submitted by Purchaser',
                                                'bg' => '#eef2ff', 'fg' => '#4338ca', 'border' => '#c7d2fe', 'dot' => '#4f46e5',
                                            ],
                                            $roRawStatus === 'Incomplete' => [
                                                'pill' => 'Partial Receive',
                                                'sub' => 'Waiting for back orders',
                                                'bg' => '#fffbeb', 'fg' => '#b45309', 'border' => '#fde68a', 'dot' => '#d97706',
                                            ],
                                            in_array($roRawStatus, ['Completed', 'Accepted', 'Delivered'], true) => [
                                                'pill' => 'Delivered',
                                                'sub' => 'Verified by Receiving',
                                                'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                                            ],
                                            in_array($roRawStatus, ['Returned', 'Minor Revision', 'For revision'], true) => [
                                                'pill' => 'Returned',
                                                'sub' => 'Sent back to Purchaser',
                                                'bg' => '#fff7ed', 'fg' => '#c2410c', 'border' => '#fed7aa', 'dot' => '#ea580c',
                                            ],
                                            default => [
                                                'pill' => \App\Support\ProcurementStatus::label($roRawStatus, 'rr'),
                                                'sub' => '',
                                                'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                                            ],
                                        };
                                    @endphp
                                    <div class="inline-flex flex-col items-start">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                            style="background:{{ $roBadge['bg'] }};color:{{ $roBadge['fg'] }};border-color:{{ $roBadge['border'] }};"
                                        >
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $roBadge['dot'] }};"></span>
                                            <span>{{ $roBadge['pill'] }}</span>
                                        </span>
                                        @if($roBadge['sub'] !== '')
                                            <span class="mt-1 whitespace-nowrap text-[11px] font-medium leading-tight text-slate-500">
                                                {{ $roBadge['sub'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                        @if($reviewable)
                                            <button
                                                type="button"
                                                @click="openSign({{ $rr->receiving_report_id }})"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition hover:opacity-85"
                                                style="background:#eff6ff;color:#0025cc;"
                                                data-tip="Second Count"
                                                data-tip-place="above"
                                                aria-label="Second Count"
                                            >
                                                <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            @click="openView({{ $rr->receiving_report_id }}); fetch('{{ route('receiving.rr.start-review', $rr->receiving_report_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                                            data-tip="View"
                                            data-tip-place="above"
                                            aria-label="View"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>
                                        @include('partials.share-document-button', [
                                            'type' => 'rr',
                                            'id' => $rr->receiving_report_id,
                                            'label' => \App\Support\RequestExplorer::shareUiLabel('rr', $rr->receiving_report_form_number ?? null),
                                        ])
                                        <button
                                            type="button"
                                            @click="printRr({{ $rr->receiving_report_id }})"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                                            data-tip="Print"
                                            data-tip-place="above"
                                            aria-label="Print"
                                        >
                                            <i data-lucide="printer" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <p class="text-sm font-medium text-slate-700">No receiving reports in this queue</p>
                                    <p class="mt-1 text-xs text-slate-400">Try another filter or clear your search.</p>
                                </td>
                            </tr>
                        @endempty
                    </tbody>
                </table>
            </div>

            <div id="roRrCards" class="hidden space-y-3 px-5 py-5 sm:px-6">
                @forelse($reports as $rr)
                    @php
                        $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
                        $displayStatus = \App\Support\ProcurementStatus::label($rr->receiving_report_status, 'rr');
                        $requestNo = trim((string) ($rr->receiving_report_request_no ?? $rr->request_check_request_no ?? ''));
                        $actionsHtml = '<button type="button" @click="openView('.$rr->receiving_report_id.'); fetch(\''.route('receiving.rr.start-review', $rr->receiving_report_id).'\', {method:\'POST\', headers:{\'X-CSRF-TOKEN\':\''.csrf_token().'\',\'Accept\':\'application/json\'}})" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700">View</button>'
                            .'<button type="button" @click="printRr('.$rr->receiving_report_id.')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>';
                        if ($reviewable) {
                            $actionsHtml .= '<button type="button" @click="openSign('.$rr->receiving_report_id.')" class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold text-white" style="background:#0025cc;color:#ffffff;">Second Count</button>';
                        }
                    @endphp
                    @include('receiving-officer.partials.list-info-card', [
                        'title' => $rr->receiving_report_form_number,
                        'urgent' => \App\Support\DocumentUrgency::isUrgent('RR', $rr),
                        'subtitle' => 'Request No.: '.($requestNo !== '' ? $requestNo : '-').' · RFC: '.($rr->request_check_form_number ?? '-'),
                        'status' => $displayStatus,
                        'statusClass' => 'border-slate-200 bg-slate-50 text-slate-700',
                        'fields' => [
                            ['label' => 'From', 'value' => $rr->receiving_report_received_from ?? '-'],
                            ['label' => 'Status', 'value' => $displayStatus],
                        ],
                        'actionsHtml' => $actionsHtml,
                    ])
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 px-4 py-12 text-center">
                        <p class="text-sm font-medium text-slate-700">No receiving reports in this queue</p>
                        <p class="mt-1 text-xs text-slate-400">Try another filter or clear your search.</p>
                    </div>
                @endempty
            </div>
        </form>

        <template x-teleport="body">
            <div
                x-show="actionMenu !== null"
                x-cloak
                @click.outside="actionMenu = null"
                class="fixed z-[13000] min-w-[168px] -translate-x-full rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                :style="`top:${actionMenuPos.top}px;left:${actionMenuPos.left}px`"
            >
                <button type="button" @click="openSign(actionMenu); actionMenu = null" class="block w-full px-3 py-2 text-left text-xs font-semibold text-[#0025cc] hover:bg-slate-50">Second Count</button>
            </div>
        </template>

        @if ($reports->hasPages())
            <div class="pt-1">{{ $reports->links() }}</div>
        @endif
    </div>

    @foreach($reports as $rr)
        @php $rrItems = $items->get($rr->receiving_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-start justify-center p-4 sm:p-6" @click="viewOpen = false">
                <div @click.stop class="relative my-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between gap-3 border-b px-6 py-5">
                        <div>
                            @php
                                $roFundLabel = \App\Support\ProcurementPaymentPath::label(
                                    $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                );
                                $roIsCashAdvance = \App\Support\ProcurementPaymentPath::requiresLiquidation(
                                    $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                );
                            @endphp
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                                @include('partials.ris-urgency-badge', [
                                    'urgent' => \App\Support\DocumentUrgency::isUrgent('RR', $rr),
                                    'title' => 'Inherited from linked RIS',
                                ])
                                @if($roFundLabel && $roFundLabel !== 'Not chosen')
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $roIsCashAdvance ? 'bg-violet-50 text-violet-800 ring-violet-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $roFundLabel }} RR</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '-' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0025cc] text-white hover:bg-blue-800" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                            <button type="button" @click="viewOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" title="Close" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    @include('partials.document-lineage', [
                        'lineage' => \App\Support\DocumentLineage::forRr((int) $rr->receiving_report_id),
                        'currentType' => 'RR',
                        'statusHint' => \App\Support\DocumentLineage::reviewHint($rr->receiving_report_status ?? null, null, 'rr'),
                    ])
                    <div class="bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @foreach($reports as $rr)
        @php
            $rrItems = $items->get($rr->receiving_report_id, collect())->values();
            $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
        @endphp
        @if($reviewable)
            <div x-show="signOpen && signRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="if (signOpen && signRr === {{ $rr->receiving_report_id }}) { closeSignConfirm ? cancelCloseSign() : requestCloseSign(); }">
                <div class="fixed inset-0 bg-black/40" @click="requestCloseSign()"></div>
                <div class="relative flex min-h-full items-start justify-center p-4 sm:p-6" @click="requestCloseSign()">
                    <form
                        method="POST"
                        action="{{ route('receiving.rr.second-count', $rr->receiving_report_id) }}"
                        enctype="multipart/form-data"
                        @click.stop
                        @input="if ($event.isTrusted) signDirty = true"
                        @change="if ($event.isTrusted) signDirty = true"
                        @pointerup="if ($event.isTrusted && $event.target && $event.target.tagName === 'CANVAS') signDirty = true"
                        class="relative my-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                        data-second-count-form="{{ $rr->receiving_report_id }}"
                    >
                        @csrf
                        <input type="hidden" name="signature_image" id="scSigImage-{{ $rr->receiving_report_id }}" value="">

                        <div class="flex items-center justify-between gap-3 border-b px-6 py-5">
                            <div>
                                <h3 class="text-xl font-semibold">Confirm Second Count</h3>
                                <p class="text-sm text-gray-500">{{ $rr->receiving_report_form_number }} · Sign Second Count on the document below</p>
                            </div>
                            <button type="button" @click="requestCloseSign()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100" title="Close" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div class="bg-gray-100 p-6">
                            <div class="mx-auto mb-4 w-full max-w-[1095px]">
                                <div
                                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5"
                                    x-data="{
                                        maxPhotos: 3,
                                        photos: [],
                                        limitNotice: '',
                                        syncInput() {
                                            const dt = new DataTransfer();
                                            this.photos.forEach(p => dt.items.add(p.file));
                                            if (this.$refs.photoInput) {
                                                this.$refs.photoInput.files = dt.files;
                                            }
                                        },
                                        addFiles(event) {
                                            const picked = Array.from(event.target.files || []);
                                            this.limitNotice = '';
                                            let skipped = 0;
                                            for (const file of picked) {
                                                if (!file.type || !file.type.startsWith('image/')) continue;
                                                if (this.photos.length >= this.maxPhotos) {
                                                    skipped++;
                                                    continue;
                                                }
                                                this.photos.push({
                                                    id: Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                                                    file,
                                                    name: file.name,
                                                    url: URL.createObjectURL(file),
                                                });
                                            }
                                            if (skipped > 0) {
                                                this.limitNotice = 'Maximum of ' + this.maxPhotos + ' photos allowed.';
                                            }
                                            this.syncInput();
                                            if (event.target) event.target.value = '';
                                        },
                                        removePhoto(index) {
                                            const item = this.photos[index];
                                            if (item && item.url) URL.revokeObjectURL(item.url);
                                            this.photos.splice(index, 1);
                                            this.limitNotice = '';
                                            this.syncInput();
                                        }
                                    }"
                                >
                                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Product verification photos</label>
                                            <p class="text-xs text-gray-500">Optional. Upload up to 3 photos of the verified products.</p>
                                        </div>
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-slate-600" x-text="photos.length + ' / ' + maxPhotos"></span>
                                    </div>

                                    {{-- Real form input synced via DataTransfer --}}
                                    <input type="file" name="verification_photos[]" x-ref="photoInput" multiple accept="image/*" class="hidden">

                                    <div class="flex flex-wrap items-center gap-2">
                                        <label
                                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                            :class="photos.length >= maxPhotos ? 'pointer-events-none opacity-50' : ''"
                                        >
                                            <i data-lucide="image-plus" class="h-3.5 w-3.5"></i>
                                            <span x-text="photos.length >= maxPhotos ? 'Maximum reached (3)' : 'Choose Photos'"></span>
                                            <input type="file" accept="image/*" multiple class="hidden" :disabled="photos.length >= maxPhotos" @change="addFiles($event)">
                                        </label>
                                        <span x-show="limitNotice" x-text="limitNotice" class="text-xs font-medium text-amber-700" style="display:none;"></span>
                                    </div>

                                    <div x-show="photos.length > 0" class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-3" style="display:none;">
                                        <template x-for="(photo, idx) in photos" :key="photo.id">
                                            <div class="group relative flex items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50/70 p-2">
                                                <img :src="photo.url" :alt="photo.name" class="h-12 w-12 shrink-0 rounded-md border border-slate-200 object-cover bg-white">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-xs font-medium text-slate-700" :title="photo.name" x-text="photo.name"></p>
                                                    <p class="text-[11px] text-slate-400" x-text="'Photo ' + (idx + 1)"></p>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="removePhoto(idx)"
                                                    title="Remove photo"
                                                    aria-label="Remove photo"
                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                @php
                                    $inspectLines = $rrItems
                                        ->filter(fn ($line) => empty($line->receiving_report_item_verified) && filled($line->receiving_report_item_article ?? null))
                                        ->values();
                                    $inspectLabels = \App\Support\BackOrders::replacementLabels($inspectLines);
                                    $verifiedCount = $rrItems->filter(fn ($line) => !empty($line->receiving_report_item_verified))->count();
                                @endphp
                                @if($inspectLines->isNotEmpty())
                                    @php
                                        $inspectInitRows = $inspectLines->map(function ($line) {
                                            $ord = $line->receiving_report_item_ordered_qty !== null ? (int) $line->receiving_report_item_ordered_qty : null;
                                            $qty = (int) ($line->receiving_report_item_quantity ?? 0);
                                            $dmg = \App\Support\BackOrders::damagedQty($line);
                                            return [
                                                'id' => (int) $line->receiving_report_item_id,
                                                'ordered' => $ord,
                                                'delivered' => $qty,
                                                'damaged' => $dmg,
                                            ];
                                        })->values()->all();
                                    @endphp
                                    <div
                                        class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5"
                                        x-data="{
                                            rows: @js($inspectInitRows),
                                            goodOf(r) {
                                                const d = Math.max(0, Number(r.delivered) || 0);
                                                const b = Math.max(0, Math.min(d, Number(r.damaged) || 0));
                                                return Math.max(0, d - b);
                                            },
                                            missingOf(r) {
                                                if (r.ordered === null || r.ordered === '') return 0;
                                                const o = Math.max(0, Number(r.ordered) || 0);
                                                const d = Math.max(0, Number(r.delivered) || 0);
                                                return Math.max(0, o - d);
                                            },
                                            damagedOf(r) {
                                                const d = Math.max(0, Number(r.delivered) || 0);
                                                return Math.max(0, Math.min(d, Number(r.damaged) || 0));
                                            },
                                            get totalGood() { return this.rows.reduce((s, r) => s + this.goodOf(r), 0); },
                                            get totalMissing() { return this.rows.reduce((s, r) => s + this.missingOf(r), 0); },
                                            get totalDamaged() { return this.rows.reduce((s, r) => s + this.damagedOf(r), 0); },
                                            get totalBackOrder() { return this.totalMissing + this.totalDamaged; }
                                        }"
                                    >
                                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">Item inspection · second verification</p>
                                                @if($verifiedCount > 0)
                                                    <p class="text-xs text-gray-500">
                                                        {{ $verifiedCount }} row{{ $verifiedCount === 1 ? ' was' : 's were' }} already verified earlier and {{ $verifiedCount === 1 ? 'is' : 'are' }} not shown.
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="inline-flex items-center gap-1.5 text-xs">
                                                <span class="text-slate-500">Result:</span>
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                    :style="totalBackOrder > 0 ? 'background:#fffbeb;color:#b45309;border-color:#fde68a;' : 'background:#ecfdf5;color:#047857;border-color:#a7f3d0;'"
                                                >
                                                    <span class="h-1.5 w-1.5 rounded-full" :style="totalBackOrder > 0 ? 'background:#d97706;' : 'background:#059669;'"></span>
                                                    <span x-text="totalBackOrder > 0 ? 'Partial Receive' : 'Completed'"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500">
                                                        <th class="py-2 pr-3 font-medium">Article</th>
                                                        <th class="py-2 pr-3 text-center font-medium">Ordered</th>
                                                        <th class="py-2 pr-3 text-center font-medium">Purchaser count</th>
                                                        <th class="w-24 py-2 pr-3 text-center font-medium">
                                                            <span class="block">Delivered</span>
                                                            <span class="block text-[10px] font-normal normal-case text-gray-400">Total arrived</span>
                                                        </th>
                                                        <th class="w-24 py-2 pr-3 text-center font-medium">
                                                            <span class="block">Damaged</span>
                                                            <span class="block text-[10px] font-normal normal-case text-gray-400">Of delivered</span>
                                                        </th>
                                                        <th class="w-24 py-2 pr-3 text-center font-medium">
                                                            <span class="block">Good</span>
                                                            <span class="block text-[10px] font-normal normal-case text-gray-400">To inventory</span>
                                                        </th>
                                                        <th class="py-2 pr-3 text-center font-medium">
                                                            <span class="block">Back Order</span>
                                                            <span class="block text-[10px] font-normal normal-case text-gray-400">Missing + damaged</span>
                                                        </th>
                                                        <th class="py-2 font-medium">Damage remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($inspectLines as $idx => $line)
                                                        @php
                                                            $lineQty = (int) ($line->receiving_report_item_quantity ?? 0);
                                                            $lineOrdered = $line->receiving_report_item_ordered_qty;
                                                            $lineDamaged = \App\Support\BackOrders::damagedQty($line);
                                                            $lineLabel = $inspectLabels[(int) ($line->receiving_report_item_back_order_id ?? 0)] ?? null;
                                                        @endphp
                                                        <tr class="{{ $lineLabel ? 'bg-sky-50/50' : '' }}">
                                                            <td class="py-2 pr-3 text-gray-800">
                                                                {{ $line->receiving_report_item_article ?: 'Item' }}
                                                                @if($lineLabel)
                                                                    <span class="block text-[11px] font-semibold {{ $lineLabel['new'] ? 'text-violet-700' : 'text-sky-700' }}">{{ $lineLabel['label'] }}{{ filled($line->receiving_report_item_supplier_name ?? null) ? ' · '.$line->receiving_report_item_supplier_name : '' }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="py-2 pr-3 text-center font-medium text-gray-700">{{ $lineOrdered ?? '-' }}</td>
                                                            <td class="py-2 pr-3 text-center text-xs text-gray-500">{{ $lineQty }} delivered{{ $lineDamaged > 0 ? ' · '.$lineDamaged.' damaged' : '' }}</td>
                                                            <td class="py-2 pr-3">
                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    @if($lineOrdered !== null) max="{{ (int) $lineOrdered }}" @endif
                                                                    step="1"
                                                                    name="received[{{ $line->receiving_report_item_id }}]"
                                                                    x-model.number="rows[{{ $idx }}].delivered"
                                                                    value="{{ $lineQty }}"
                                                                    class="h-9 w-full rounded-lg border border-gray-300 px-2 text-center text-sm"
                                                                >
                                                            </td>
                                                            <td class="py-2 pr-3">
                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    :max="Math.max(0, Number(rows[{{ $idx }}].delivered) || 0)"
                                                                    step="1"
                                                                    name="damaged[{{ $line->receiving_report_item_id }}]"
                                                                    x-model.number="rows[{{ $idx }}].damaged"
                                                                    value="{{ $lineDamaged }}"
                                                                    class="h-9 w-full rounded-lg border border-gray-300 px-2 text-center text-sm"
                                                                >
                                                            </td>
                                                            <td class="py-2 pr-3 text-center">
                                                                <span
                                                                    class="inline-flex min-w-[2.25rem] items-center justify-center rounded-full border px-2 py-0.5 text-xs font-bold tabular-nums"
                                                                    :style="goodOf(rows[{{ $idx }}]) > 0 ? 'background:#ecfdf5;color:#047857;border-color:#a7f3d0;' : 'background:#f1f5f9;color:#64748b;border-color:#cbd5e1;'"
                                                                    x-text="goodOf(rows[{{ $idx }}])"
                                                                ></span>
                                                            </td>
                                                            <td class="py-2 pr-3 text-center">
                                                                <div class="inline-flex flex-wrap items-center justify-center gap-1">
                                                                    <template x-if="missingOf(rows[{{ $idx }}]) > 0">
                                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold" style="background:#fffbeb;color:#b45309;border-color:#fde68a;" x-text="missingOf(rows[{{ $idx }}]) + ' missing'"></span>
                                                                    </template>
                                                                    <template x-if="damagedOf(rows[{{ $idx }}]) > 0">
                                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;" x-text="damagedOf(rows[{{ $idx }}]) + ' damaged'"></span>
                                                                    </template>
                                                                    <template x-if="missingOf(rows[{{ $idx }}]) === 0 && damagedOf(rows[{{ $idx }}]) === 0">
                                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold" style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;">None</span>
                                                                    </template>
                                                                </div>
                                                            </td>
                                                            <td class="py-2">
                                                                <input
                                                                    type="text"
                                                                    maxlength="500"
                                                                    name="damaged_remarks[{{ $line->receiving_report_item_id }}]"
                                                                    value="{{ $line->receiving_report_item_damage_remarks ?? '' }}"
                                                                    placeholder="e.g. cracked casing"
                                                                    class="h-9 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                                >
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @include('partials.receiving-report-paper', [
                                'editable' => false,
                                'signSecondCount' => true,
                                'rr' => $rr,
                                'rows' => $rrItems,
                                'officerName' => auth()->user()->user_full_name ?? 'Receiving Officer',
                                'signSuffix' => (string) $rr->receiving_report_id,
                                'printId' => null,
                                'printClass' => '',
                            ])

                            <div id="roSigSlot-{{ $rr->receiving_report_id }}" class="mx-auto mt-4 w-full max-w-[1095px] min-w-0"></div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-white px-6 py-4">
                            <button type="button" @click="requestCloseSign()" class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:text-gray-900">Cancel</button>
                            <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800">Confirm Second Count</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Close confirmation dialog for Confirm Second Count modal --}}
    <div
        x-show="closeSignConfirm"
        x-cloak
        class="fixed inset-0 flex items-center justify-center p-4"
        style="z-index: 99999;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="scCloseConfirmTitle"
    >
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-[1px]" @click="cancelCloseSign()"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" @click.stop>
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#fffbeb;border-color:#fde68a;color:#b45309;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 id="scCloseConfirmTitle" class="text-base font-semibold text-slate-950">Close Second Count?</h4>
                    <p class="mt-1 text-sm text-slate-600">Any unsaved counts, damage remarks, or uploaded verification photos will not be saved.</p>
                </div>
            </div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="cancelCloseSign()"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Keep Editing
                </button>
                <button
                    type="button"
                    @click="confirmCloseSign()"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                    style="background:#dc2626;"
                >
                    Discard &amp; Close
                </button>
            </div>
        </div>
    </div>

    <div id="roSigPanelHome" class="hidden">
        @include('receiving-officer.partials.second-count-signature-panel', [
            'savedSignatures' => $savedSignatures ?? collect(),
        ])
    </div>

    <div
        x-show="reviseOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="reviseOpen = false"
    >
        <div class="absolute inset-0" @click="reviseOpen = false"></div>
        <form
            method="POST"
            :action="'/receiving/reports/' + remarksRr + '/revise'"
            enctype="multipart/form-data"
            @click.stop
            class="relative max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700">
                        <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900">Request revision</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Send this Receiving Report back to the Purchaser with clear notes on what must be corrected.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="reviseOpen = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100"
                        title="Close"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="reviseRemarks" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Revision remarks <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="reviseRemarks"
                        name="remarks"
                        required
                        rows="5"
                        maxlength="5000"
                        placeholder="Describe what needs to be fixed before this report can be counted again."
                        class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    ></textarea>
                </div>
                @include('admin.partials.revision-image-picker', ['pickerId' => 'rrReviseImages'])
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs leading-relaxed text-slate-600">
                        The Purchaser will see these remarks, update the report, and resubmit it for second count. Inventory is not updated until second count is confirmed.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-6 py-4">
                <button
                    type="button"
                    @click="reviseOpen = false"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800"
                >
                    Send back to Purchaser
                </button>
            </div>
        </form>
    </div>

    <div
        x-show="returnOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="returnOpen = false"
    >
        <div class="absolute inset-0" @click="returnOpen = false"></div>
        <form
            method="POST"
            :action="'/receiving/reports/' + remarksRr + '/return'"
            enctype="multipart/form-data"
            @click.stop
            class="relative max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700">
                        <i data-lucide="undo-2" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900">Return delivery</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Mark this delivery as returned when items did not arrive correctly.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="returnOpen = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100"
                        title="Close"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="returnRemarks" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Return reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="returnRemarks"
                        name="remarks"
                        required
                        rows="5"
                        maxlength="5000"
                        placeholder="Explain why the delivery is being returned to the Purchaser."
                        class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    ></textarea>
                </div>
                @include('admin.partials.revision-image-picker', ['pickerId' => 'rrReturnImages'])
                <div class="rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3">
                    <p class="text-xs leading-relaxed text-rose-800">
                        Items will not be accepted into inventory. The Purchaser is notified with your reason.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-6 py-4">
                <button
                    type="button"
                    @click="returnOpen = false"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-800"
                >
                    Return to Purchaser
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        @page { size: A4 portrait; margin: 8mm; }
        .rr-print-active { background: #fff !important; }
    }
</style>

@include('admin.partials.view-mode-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.bindAdminViewMode === 'function') {
        window.bindAdminViewMode({
            tableId: 'roRrTable',
            cardsId: 'roRrCards',
            buttonSelector: '.ro-rr-view-btn',
            storageKey: 'ro_rr_view',
        });
    }
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

    document.querySelectorAll('[data-second-count-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var id = form.getAttribute('data-second-count-form');
            var hidden = document.getElementById('scSigImage-' + id);
            var nameInput = document.getElementById('scName-' + id);
            var hasSig = hidden && String(hidden.value || '').indexOf('data:image/') === 0;
            if (!hasSig) {
                event.preventDefault();
                if (typeof window.showMpToast === 'function') {
                    showMpToast('Please add a signature (draw, upload, or pick a saved one) before confirming.', {
                        title: 'Signature required',
                        type: 'warning',
                        timer: 3600
                    });
                } else {
                    alert('Please add a signature before confirming Second Count.');
                }
                return;
            }
            if (nameInput && !String(nameInput.value || '').trim()) {
                nameInput.value = @json(auth()->user()->user_full_name ?? auth()->user()->user_username ?? 'Receiving Officer');
            }
        });
    });
});
</script>
@endsection
