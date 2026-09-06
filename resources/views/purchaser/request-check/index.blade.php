@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Funding Requests')
@section('page-subtitle', 'Request for Check and Cash Advance — create, submit, print, and archive funding requests.')

@section('content')

<script type="application/json" id="rfc-atp-prefill">{!! json_encode($atpPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('request_check_authority_purchase_id')) || !empty($selectedAtpId) || !empty($openCreate) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewRfcId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        modalFullscreen: false,
        selectedRfc: {{ !empty($viewRfcId) ? (int) $viewRfcId : 'null' }},
        atpPrefill: JSON.parse(document.getElementById('rfc-atp-prefill').textContent || '{}'),
        today: '{{ now()->toDateString() }}',

        openView(id) {
            this.selectedRfc = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.modalFullscreen = false;
        },

        openEdit(id) {
            this.selectedRfc = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('rfc-' + id, 'Requested by signature');
        },

        openCreate() {
            this.createOpen = true;
            this.modalFullscreen = false;
            this.bindDocSig('rfc-create', 'Requested by signature');
        },

        bindDocSig(key, title) {
            this.$nextTick(() => {
                if (window.purchaserDocumentSignature) {
                    window.purchaserDocumentSignature.bind({
                        key: key,
                        hiddenId: 'purSigImage-' + key,
                        nameId: 'purSigName-' + key,
                        previewId: 'purSigOverlay-' + key,
                        slotId: 'purSigSlot-' + key,
                        title: title || 'Purchaser signature',
                        hint: 'Pick a saved signature, draw one, or upload. It overlays your printed name on the form above.'
                    });
                }
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.emptyOpen = false;
            this.modalFullscreen = false;
            this.selectedRfc = null;
        },

        applyAtpPrefill(atpId) {
            const data = this.atpPrefill[String(atpId)];
            const form = this.$refs.createForm;
            if (!form || !data) {
                return;
            }
            const payee = form.querySelector('[name=request_check_payee]');
            const amount = form.querySelector('[name=request_check_amount_figures]');
            const purpose = form.querySelector('[name=request_check_particulars_purpose]');
            const date = form.querySelector('[name=request_check_date]');
            if (payee && data.payee) payee.value = data.payee;
            if (amount && data.amount) amount.value = data.amount;
            if (purpose && data.purpose) purpose.value = data.purpose;
            if (date && !date.value) date.value = this.today;
        },

        printRfc(id) {
            const sheetId = 'rfc-print-' + id;
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'rfc-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.rfc-print-sheet').forEach(function (el) {
                    el.classList.remove('rfc-print-active');
                });
                sheet.classList.add('rfc-print-active');
            }
            window.print();
        },
        recordsLoading: false,
        filterError: null,
        searchTimer: null,
        async refreshRfcRecords(url = null) {
            const form = this.$refs.rfcFilterForm;
            if (!form) return;

            const targetUrl = url || form.action;
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');

            let requestUrl = targetUrl;

            if (!url) {
                const query = params.toString();
                requestUrl = query ? `${form.action}?${query}` : form.action;
            }

            this.recordsLoading = true;
            this.filterError = null;

            try {
                const response = await fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh RFC records.');
                }

                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');

                const nextRecords = parsed.querySelector('#rfc-records-section');
                const currentRecords = document.querySelector('#rfc-records-section');

                if (!nextRecords || !currentRecords) {
                    throw new Error('RFC records section was not found.');
                }

                currentRecords.innerHTML = nextRecords.innerHTML;
                if (window.Alpine) {
                    Alpine.initTree(currentRecords);
                }

                const nextUrl = new URL(requestUrl, window.location.origin);
                window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
            } catch (error) {
                console.error(error);
                this.filterError = 'Could not refresh records. Please try again.';
            } finally {
                this.recordsLoading = false;
            }
        }
    }"
    x-init="
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('rfc-create', 'Requested by signature');
                if ('{{ $selectedAtpId ?? '' }}') applyAtpPrefill('{{ $selectedAtpId ?? '' }}');
            });
        }
    "
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    <div x-show="filterError" x-cloak class="pur-alert-error" x-text="filterError"></div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="RFC list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.rfc.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['view' => 'archive']) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

        @unless($archiveView)
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="emptyOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print Empty RFC
                </button>
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create {{ ($selectedFundingType ?? 'request_for_check') === 'cash_advance' ? 'Cash Advance' : 'RFC' }}
                </button>
            </div>
        @endunless
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['status' => 'Draft']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Draft</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($rfcSummary['draft']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Incomplete drafts awaiting submit</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['status' => 'Submitted']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">In Review</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($rfcSummary['submitted']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <i data-lucide="send" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Waiting for accounting review</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['status' => 'Approved']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($rfcSummary['approved']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Approved for receiving workflow</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['status' => 'Rejected']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($rfcSummary['rejected']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <i data-lucide="circle-x" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Returned or declined RFC</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    @php
        $rfcHasFilters = request()->filled('search')
            || request()->filled('status')
            || request()->filled('date_from')
            || request()->filled('date_to')
            || request()->filled('date');
        $rfcClearUrl = $archiveView
            ? route(($pp ?? 'purchaser').'.rfc.index', ['view' => 'archive'])
            : route(($pp ?? 'purchaser').'.rfc.index');
    @endphp

    <div id="rfc-records-section" class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived RFC' : 'RFC Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $rfcs->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Stored Request for Check records.' : 'Search and manage Request for Check records.' }}
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route(($pp ?? 'purchaser').'.rfc.index') }}"
                    x-ref="rfcFilterForm"
                    x-on:submit.prevent="refreshRfcRecords()"
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                >
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            x-on:input="
                                clearTimeout(searchTimer);
                                searchTimer = setTimeout(() => refreshRfcRecords(), 350);
                            "
                            placeholder="Search RFC, ATP, payee..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="status" x-on:change="refreshRfcRecords()" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Minor Revision','Approved','Rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status === 'Submitted' ? 'In Review' : $status }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date_from" value="{{ request('date_from', request('date')) }}" x-on:change="refreshRfcRecords()" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" x-on:change="refreshRfcRecords()" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        x-bind:disabled="recordsLoading"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        <span x-cloak x-show="!recordsLoading">Apply</span>
                        <span x-cloak x-show="recordsLoading">Loading...</span>
                    </button>

                    @if($rfcHasFilters)
                        <button
                            type="button"
                            x-on:click="
                                $refs.rfcFilterForm.reset();
                                refreshRfcRecords('{{ $rfcClearUrl }}');
                            "
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    @endif
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($rfcs as $rfc)
                        @php
                            $editable = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $rfc->request_check_form_number ?? 'RFC-'.$rfc->request_check_id }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $rfc->request_check_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $rfc->authority_purchase_form_number ?? '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $rfc->request_check_payee ?: '—' }}</td>
                            <td class="px-5 py-4 font-medium text-gray-700">{{ $rfc->request_check_amount_figures !== null ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2) : '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $rfc->request_check_date ? \Carbon\Carbon::parse($rfc->request_check_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4">
                                @if($rfc->request_check_status === 'Approved')
                                    @if($rfc->funds_released)
                                        @include('accounting.partials.status-badge', ['status' => 'Funds released'])
                                    @else
                                        @include('accounting.partials.status-badge', ['status' => 'Waiting for funds'])
                                    @endif
                                @else
                                    @include('accounting.partials.status-badge', ['status' => $rfc->request_check_status])
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" @click="openView({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.submit', $rfc->request_check_id) }}" onsubmit="return confirm('Submit this Request for Check to Accounting?')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]" title="Submit" aria-label="Submit"><i data-lucide="send" class="h-4 w-4"></i></button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rfc->request_check_status === 'Approved')
                                        @if(!$rfc->has_rr && $rfc->funds_released)
                                            <a href="{{ route(($pp ?? 'purchaser').'.rr.index', ['selected_rfc' => $rfc->request_check_id]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]" title="Create RR" aria-label="Create RR"><i data-lucide="file-plus-2" class="h-4 w-4"></i></a>
                                        @elseif(!$rfc->has_rr)
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700" title="Waiting for funds" aria-label="Waiting for funds"><i data-lucide="hourglass" class="h-4 w-4"></i></span>
                                        @else
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="RR Created" aria-label="RR Created"><i data-lucide="circle-check" class="h-4 w-4"></i></span>
                                        @endif
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.restore', $rfc->request_check_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Restore" aria-label="Restore"><i data-lucide="archive-restore" class="h-4 w-4"></i></button>
                                        </form>
                                    @elseif(in_array($rfc->request_check_status, ['Approved', 'Rejected'], true))
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.archive', $rfc->request_check_id) }}" onsubmit="return confirm('Archive this Request for Check?')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100" title="Archive" aria-label="Archive"><i data-lucide="archive" class="h-4 w-4"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="receipt-text" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No Request for Check records found</p>
                                <p class="mt-1 text-sm text-gray-400">Create an RFC or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="border-t border-gray-100 px-5 py-4"
            x-on:click="
                const link = $event.target.closest('a');
                if (!link) return;
                $event.preventDefault();
                refreshRfcRecords(link.href);
            "
        >
            {{ $rfcs->links() }}
        </div>
    </div>

    <template x-teleport="body">
    <div
        x-show="createOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(createOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfc-create-title"
    >
        <div
            x-on:click.self="createOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                @click.stop
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <i data-lucide="receipt-text" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="rfc-create-title" class="text-lg font-semibold tracking-tight text-slate-900">
                                Create {{ ($selectedFundingType ?? 'request_for_check') === 'cash_advance' ? 'Cash Advance' : 'Request for Check' }}
                            </h3>
                            <p class="mt-0.5 text-sm text-gray-500">Select an approved ATP with matching payment path. Submit sends this to Accounting.</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="createOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.store') }}" enctype="multipart/form-data" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">
                    <input type="hidden" name="request_check_funding_type" value="{{ $selectedFundingType ?? 'request_for_check' }}">
                    <div class="bg-slate-100 p-3 md:p-5">
                        @if($eligibleAtps->isEmpty())
                            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                No approved ATP is currently available. You can still fill out and save this {{ ($selectedFundingType ?? 'request_for_check') === 'cash_advance' ? 'Cash Advance' : 'Request for Check' }} as a draft, then link an approved ATP later before submitting.
                            </div>
                        @else
                            <div class="mb-4">
                                <label class="text-xs font-medium text-gray-500">Approved ATP <span class="font-normal text-gray-400">(optional for draft)</span></label>
                                <select name="request_check_authority_purchase_id" x-on:change="applyAtpPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm">
                                    <option value="">Select approved ATP</option>
                                    @foreach($eligibleAtps as $atp)
                                        <option value="{{ $atp->authority_purchase_id }}" {{ old('request_check_authority_purchase_id', $selectedAtpId ?? '') == $atp->authority_purchase_id ? 'selected' : '' }}>
                                            {{ $atp->authority_purchase_form_number ?? 'ATP-'.$atp->authority_purchase_id }}
                                            @if($atp->ris_form_number) · {{ $atp->ris_form_number }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @include('partials.request-check-paper', ['editable' => true, 'rfc' => null, 'signKey' => 'rfc-create'])
                        <div id="purSigSlot-rfc-create" class="mt-4 w-full"></div>
                        <div class="mt-4 rounded-lg bg-white p-4">
                            <label class="text-xs font-medium text-gray-500">Supporting documents (PDF, JPG, PNG · max 5MB each)</label>
                            <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block w-full text-sm">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="createOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50">
                            Save Draft
                        </button>
                        <button type="submit" onclick="
                            this.form.save_action.value='submit';
                            if (window.purchaserDocumentSignature && !window.purchaserDocumentSignature.hasSignature()) {
                                event.preventDefault();
                                if (typeof window.showMpToast === 'function') showMpToast('Draw or upload your signature before submitting.', { title: 'Signature required', type: 'warning' });
                                else alert('Draw or upload your signature before submitting.');
                            }
                        " class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800" @if($eligibleAtps->isEmpty()) disabled title="Link an approved ATP before submitting" @endif>
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save & Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    @foreach($rfcs as $rfc)
        @php
            $rfcFiles = $attachments->get($rfc->request_check_id, collect());
            $canEdit = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
        @endphp

        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedRfc === {{ $rfc->request_check_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rfc-view-title-{{ $rfc->request_check_id }}"
        >
            <div
                x-on:click.self="viewOpen = false; modalFullscreen = false"
                class="flex min-h-full w-full justify-center"
            >
                <div
                    @click.stop
                    class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                    :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                >
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                        <div>
                            <h3 id="rfc-view-title-{{ $rfc->request_check_id }}" class="text-xl font-semibold text-slate-900">{{ $rfc->request_check_form_number }}</h3>
                            <p class="mt-1 text-sm text-gray-500">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}
                                @if(!empty($rfc->receiving_report_form_number))
                                    · RR: {{ $rfc->receiving_report_form_number }} ({{ $rfc->receiving_report_status }})
                                @endif
                            </p>
                            @php
                                $rfcLineage = \App\Support\DocumentLineage::forRfc((int) $rfc->request_check_id);
                                $rfcHint = \App\Support\DocumentLineage::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc');
                            @endphp
                            <div class="mt-3">
                                @include('partials.document-lineage', [
                                    'lineage' => $rfcLineage,
                                    'currentType' => 'RFC',
                                    'statusHint' => $rfcHint,
                                ])
                            </div>
                            @if($rfc->request_check_revision_notes)
                                <p class="mt-2 text-sm text-amber-700">Revision notes: {{ $rfc->request_check_revision_notes }}</p>
                            @endif
                            @if($rfc->request_check_rejection_reason)
                                <p class="mt-2 text-sm text-red-700">Rejection: {{ $rfc->request_check_rejection_reason }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @include('purchaser.partials.modal-fullscreen-button')
                            <button type="button" @click="viewOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc, 'printId' => 'rfc-print-'.$rfc->request_check_id])
                        @if($rfcFiles->isNotEmpty())
                            <div class="mt-4 rounded-lg bg-white p-4 text-sm">
                                <p class="font-medium text-gray-700">Attachments</p>
                                <ul class="mt-2 space-y-1">
                                    @foreach($rfcFiles as $file)
                                        <li>
                                            <a class="text-blue-700 underline" href="{{ route(($pp ?? 'purchaser').'.rfc.attachment', [$rfc->request_check_id, $file->request_check_attachment_id]) }}" target="_blank">
                                                {{ $file->request_check_attachment_original_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                        @if(!$archiveView && $rfc->request_check_status === 'Approved')
                            @if(!$rfc->has_rr && $rfc->funds_released)
                                <a href="{{ route(($pp ?? 'purchaser').'.rr.index', ['selected_rfc' => $rfc->request_check_id]) }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Create RR</a>
                            @elseif(!$rfc->has_rr)
                                <span class="inline-flex h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-medium text-amber-700">Waiting for funds</span>
                            @else
                                <span class="inline-flex h-10 items-center rounded-lg border border-green-200 bg-green-50 px-4 text-sm font-medium text-green-700">RR Created</span>
                            @endif
                        @endif
                        <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="pur-btn-primary">Print</button>
                        <a href="{{ route(($pp ?? 'purchaser').'.rfc.export-xlsx', $rfc->request_check_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">Excel</a>
                        <a href="{{ route(($pp ?? 'purchaser').'.rfc.export-docx', $rfc->request_check_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">Word</a>
                        <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>
        </template>

        @if($canEdit)
            <template x-teleport="body">
            <div
                x-show="editOpen && selectedRfc === {{ $rfc->request_check_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rfc-edit-title-{{ $rfc->request_check_id }}"
            >
                <div
                    x-on:click.self="editOpen = false; modalFullscreen = false"
                    class="flex min-h-full w-full justify-center"
                >
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                    >
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <h3 id="rfc-edit-title-{{ $rfc->request_check_id }}" class="text-xl font-semibold text-slate-900">Edit {{ $rfc->request_check_form_number }}</h3>
                            <div class="flex shrink-0 items-center gap-1">
                                @include('purchaser.partials.modal-fullscreen-button')
                                <button type="button" @click="editOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.update', $rfc->request_check_id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="request_check_authority_purchase_id" value="{{ $rfc->request_check_authority_purchase_id }}">
                            <div class="bg-slate-100 p-3 md:p-5">
                                <p class="mb-3 text-sm text-gray-600">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}</p>
                                @include('partials.request-check-paper', ['editable' => true, 'rfc' => $rfc, 'signKey' => 'rfc-'.$rfc->request_check_id])
                                <div id="purSigSlot-rfc-{{ $rfc->request_check_id }}" class="mt-4 w-full"></div>
                                <div class="mt-4 rounded-lg bg-white p-4 text-sm">
                                    @foreach($rfcFiles as $file)
                                        <label class="mt-1 flex items-center gap-2">
                                            <input type="checkbox" name="delete_attachments[]" value="{{ $file->request_check_attachment_id }}">
                                            <span>Remove {{ $file->request_check_attachment_original_name }}</span>
                                        </label>
                                    @endforeach
                                    <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-3 block w-full">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Update Draft</button>
                                <button type="submit" onclick="
                                    this.form.save_action.value='submit';
                                    if (window.purchaserDocumentSignature && !window.purchaserDocumentSignature.hasSignature()) {
                                        event.preventDefault();
                                        if (typeof window.showMpToast === 'function') showMpToast('Draw or upload your signature before submitting.', { title: 'Signature required', type: 'warning' });
                                        else alert('Draw or upload your signature before submitting.');
                                    }
                                " class="pur-btn-primary">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </template>
        @endif
    @endforeach

    {{-- PRINT EMPTY RFC MODAL --}}
    <template x-teleport="body">
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfc-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="rfc-empty-title" class="text-lg font-semibold text-gray-900">Print Empty RFC</h3>
                        <p class="mt-1 text-sm text-gray-500">Original blank Request for Check format.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button
                            type="button"
                            x-on:click="emptyOpen = false; modalFullscreen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-100 p-3 md:p-5">
                    @include('partials.request-check-paper', ['editable' => false, 'rfc' => null, 'printId' => 'rfc-print-blank'])
                </div>

                <div class="print-hidden flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.rfc.export-blank-xlsx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.rfc.export-blank-docx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Word
                    </a>
                    <button
                        type="button"
                        @click="printRfc('blank')"
                        class="pur-btn-primary"
                    >
                        Print Empty RFC
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <div id="purDocSignatureDock" class="hidden" aria-hidden="true">
        @include('purchaser.partials.document-signature-panel', ['savedSignatures' => $savedSignatures ?? collect()])
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        .rfc-print-active { background: #fff !important; }
    }
</style>
@endsection
