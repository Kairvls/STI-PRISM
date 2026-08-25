@extends('layouts.purchaser-layout')

@section('page-title', 'Request for Check')
@section('page-subtitle', 'Create, submit, print, and archive Request for Check records.')

@section('content')

<script type="application/json" id="rfc-atp-prefill">{!! json_encode($atpPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('request_check_authority_purchase_id')) || !empty($selectedAtpId) || !empty($openCreate) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewRfcId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        selectedRfc: {{ !empty($viewRfcId) ? (int) $viewRfcId : 'null' }},
        atpPrefill: JSON.parse(document.getElementById('rfc-atp-prefill').textContent || '{}'),
        today: '{{ now()->toDateString() }}',

        openView(id) {
            this.selectedRfc = id;
            this.viewOpen = true;
            this.editOpen = false;
        },

        openEdit(id) {
            this.selectedRfc = id;
            this.editOpen = true;
            this.viewOpen = false;
        },

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.emptyOpen = false;
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
            document.querySelectorAll('.rfc-print-sheet').forEach(function (sheet) {
                sheet.classList.remove('rfc-print-active');
            });
            const sheet = document.getElementById('rfc-print-' + id);
            if (sheet) {
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
        if (createOpen && '{{ $selectedAtpId ?? '' }}') {
            $nextTick(() => applyAtpPrefill('{{ $selectedAtpId ?? '' }}'));
        }
    "
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif
    <div x-show="filterError" x-cloak class="pur-alert-error" x-text="filterError"></div>
    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="pur-alert-error">
            <p class="mb-1 font-medium">Please fix the following:</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="pur-page-kicker">Purchasing Workflow</p>
            <h2 class="pur-page-title">Request for Check</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('purchaser.partials.archive-tabs', ['archiveView' => $archiveView, 'activeRoute' => 'purchaser.rfc.index', 'activeLabel' => 'Active'])
            @unless($archiveView)
                <button
                    type="button"
                    @click="emptyOpen = true"
                    class="pur-btn-secondary"
                >
                    Print Empty RFC
                </button>
                <button type="button" @click="createOpen = true" class="pur-btn-primary">Create RFC</button>
            @endunless
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        @foreach([
            ['Total RFC', $rfcSummary['total'], 'files', route('purchaser.rfc.index')],
            ['Draft', $rfcSummary['draft'], 'file-pen-line', route('purchaser.rfc.index', ['status' => 'Draft'])],
            ['In Review', $rfcSummary['submitted'], 'send', route('purchaser.rfc.index', ['status' => 'Submitted'])],
            ['Approved', $rfcSummary['approved'], 'circle-check-big', route('purchaser.rfc.index', ['status' => 'Approved'])],
            ['Rejected', $rfcSummary['rejected'], 'circle-x', route('purchaser.rfc.index', ['status' => 'Rejected'])],
            ['Archived', $rfcSummary['archived'], 'archive', route('purchaser.rfc.index', ['view' => 'archive'])],
        ] as $card)
            <a href="{{ $card[3] }}" class="pur-stat-card group">
                <p class="text-sm font-medium text-gray-500">{{ $card[0] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $card[1] }}</p>
            </a>
        @endforeach
    </div>

    @php
        $rfcHasFilters = request()->filled('search')
            || request()->filled('status')
            || request()->filled('date_from')
            || request()->filled('date_to')
            || request()->filled('date');
        $rfcClearUrl = $archiveView
            ? route('purchaser.rfc.index', ['view' => 'archive'])
            : route('purchaser.rfc.index');
    @endphp

    <div id="rfc-records-section" class="space-y-6">
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-950">RFC Records</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                        {{ $rfcs->total() }}
                    </span>
                </div>

                <form
                    method="GET"
                    action="{{ route('purchaser.rfc.index') }}"
                    x-ref="rfcFilterForm"
                    x-on:submit.prevent="refreshRfcRecords()"
                    class="flex flex-col gap-2 sm:flex-row"
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
                            placeholder="Search RFC, ATP, payee, or purpose"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="status" x-on:change="refreshRfcRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Minor Revision','Approved','Rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date_from" value="{{ request('date_from', request('date')) }}" x-on:change="refreshRfcRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" x-on:change="refreshRfcRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        x-bind:disabled="recordsLoading"
                        class="pur-btn-primary disabled:cursor-wait disabled:opacity-60"
                    >
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
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    @endif
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">RFC No.</th>
                        <th class="px-4 py-3">ATP</th>
                        <th class="px-4 py-3">Payee</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rfcs as $rfc)
                        @php
                            $editable = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 font-medium text-slate-900">{{ $rfc->request_check_form_number ?? 'RFC-'.$rfc->request_check_id }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rfc->authority_purchase_form_number ?? '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_payee ?: '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_amount_figures !== null ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2) : '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_date ? \Carbon\Carbon::parse($rfc->request_check_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-4">
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
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="openView({{ $rfc->request_check_id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">View</button>
                                    <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Print</button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rfc->request_check_id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Edit</button>
                                        <form method="POST" action="{{ route('purchaser.rfc.submit', $rfc->request_check_id) }}" onsubmit="return confirm('Submit this Request for Check to Accounting?')">
                                            @csrf
                                            <button type="submit" class="pur-btn-primary !px-3 !py-2 !text-xs">Submit</button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rfc->request_check_status === 'Approved')
                                        @if(!$rfc->has_rr && $rfc->funds_released)
                                            <a href="{{ route('purchaser.rr.index', ['selected_rfc' => $rfc->request_check_id]) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">Create RR</a>
                                        @elseif(!$rfc->has_rr)
                                            <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">Waiting for funds</span>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">RR Created</span>
                                        @endif
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route('purchaser.rfc.restore', $rfc->request_check_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Restore</button>
                                        </form>
                                    @elseif(in_array($rfc->request_check_status, ['Approved', 'Rejected'], true))
                                        <form method="POST" action="{{ route('purchaser.rfc.archive', $rfc->request_check_id) }}" onsubmit="return confirm('Archive this Request for Check?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No Request for Check records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="border-t border-gray-100 px-6 py-4"
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
    </div>

    <div
        x-show="createOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        x-effect="window.purDialog && window.purDialog.sync(createOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
    >
        <div class="fixed inset-0 bg-black/40" @click="createOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div
                @click.stop
                class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rfc-create-title"
            >
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 id="rfc-create-title" class="text-xl font-semibold text-slate-900">Create Request for Check</h3>
                        <p class="mt-1 text-sm text-gray-500">Select an approved ATP. Submit sends this to Accounting.</p>
                    </div>
                    <button type="button" @click="createOpen = false" class="rounded-lg p-2 text-gray-400" aria-label="Close">✕</button>
                </div>
                @if($eligibleAtps->isEmpty())
                    <div class="p-6 text-sm text-gray-600">No approved ATP is currently available for Request for Check creation.</div>
                @else
                    <form method="POST" action="{{ route('purchaser.rfc.store') }}" enctype="multipart/form-data" x-ref="createForm">
                        @csrf
                        <input type="hidden" name="save_action" value="draft">
                        <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                            <div class="mx-auto mb-4 w-[297mm] max-w-full">
                                <label class="text-xs font-medium text-gray-500">Approved ATP</label>
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
                            @include('partials.request-check-paper', ['editable' => true, 'rfc' => null])
                            <div class="mx-auto mt-4 w-[297mm] max-w-full rounded-lg bg-white p-4">
                                <label class="text-xs font-medium text-gray-500">Supporting documents (PDF, JPG, PNG · max 5MB each)</label>
                                <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block w-full text-sm">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Save Draft</button>
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @foreach($rfcs as $rfc)
        @php
            $rfcFiles = $attachments->get($rfc->request_check_id, collect());
            $canEdit = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
        @endphp

        <div
            x-show="viewOpen && selectedRfc === {{ $rfc->request_check_id }}"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        >
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="rfc-view-title-{{ $rfc->request_check_id }}">
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
                        <button type="button" @click="viewOpen = false" class="rounded-lg p-2 text-gray-400" aria-label="Close">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc, 'printId' => 'rfc-print-'.$rfc->request_check_id])
                        @if($rfcFiles->isNotEmpty())
                            <div class="mx-auto mt-4 w-[297mm] max-w-full rounded-lg bg-white p-4 text-sm">
                                <p class="font-medium text-gray-700">Attachments</p>
                                <ul class="mt-2 space-y-1">
                                    @foreach($rfcFiles as $file)
                                        <li>
                                            <a class="text-blue-700 underline" href="{{ route('purchaser.rfc.attachment', [$rfc->request_check_id, $file->request_check_attachment_id]) }}" target="_blank">
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
                                <a href="{{ route('purchaser.rr.index', ['selected_rfc' => $rfc->request_check_id]) }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Create RR</a>
                            @elseif(!$rfc->has_rr)
                                <span class="inline-flex h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-medium text-amber-700">Waiting for funds</span>
                            @else
                                <span class="inline-flex h-10 items-center rounded-lg border border-green-200 bg-green-50 px-4 text-sm font-medium text-green-700">RR Created</span>
                            @endif
                        @endif
                        <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="pur-btn-primary">Print</button>
                        <a href="{{ route('purchaser.rfc.export-xlsx', $rfc->request_check_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">Excel</a>
                        <a href="{{ route('purchaser.rfc.export-docx', $rfc->request_check_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">Word</a>
                        <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>

        @if($canEdit)
            <div
                x-show="editOpen && selectedRfc === {{ $rfc->request_check_id }}"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            >
                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div
                        @click.stop
                        class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="rfc-edit-title-{{ $rfc->request_check_id }}"
                    >
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <h3 id="rfc-edit-title-{{ $rfc->request_check_id }}" class="text-xl font-semibold text-slate-900">Edit {{ $rfc->request_check_form_number }}</h3>
                            <button type="button" @click="editOpen = false" class="rounded-lg p-2 text-gray-400" aria-label="Close">✕</button>
                        </div>
                        <form method="POST" action="{{ route('purchaser.rfc.update', $rfc->request_check_id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="request_check_authority_purchase_id" value="{{ $rfc->request_check_authority_purchase_id }}">
                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                                <p class="mx-auto mb-3 w-[297mm] max-w-full text-sm text-gray-600">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}</p>
                                @include('partials.request-check-paper', ['editable' => true, 'rfc' => $rfc])
                                <div class="mx-auto mt-4 w-[297mm] max-w-full rounded-lg bg-white p-4 text-sm">
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
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- PRINT EMPTY RFC MODAL --}}
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfc-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto w-full max-w-6xl rounded-xl bg-white shadow-2xl">
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="rfc-empty-title" class="text-lg font-semibold text-gray-900">Print Empty RFC</h3>
                        <p class="mt-1 text-sm text-gray-500">Original blank Request for Check format.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        aria-label="Close"
                    >
                        Close
                    </button>
                </div>

                <div class="overflow-x-auto bg-gray-100 p-5 md:p-8">
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
                        href="{{ route('purchaser.rfc.export-blank-xlsx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route('purchaser.rfc.export-blank-docx') }}"
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
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        body * { visibility: hidden !important; }
        .rfc-print-active, .rfc-print-active * { visibility: visible !important; }
        .rfc-print-active {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 297mm !important;
            min-height: 210mm !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        @page { size: A4 landscape; margin: 10mm; }
    }
</style>
@endsection
