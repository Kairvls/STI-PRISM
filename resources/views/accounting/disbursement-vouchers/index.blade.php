@extends('layouts.accounting-layout')

@section('title', 'Disbursement Vouchers')

@push('styles')
{{-- DV print uses window.accountingPrintForm (clone mount); no visibility hide needed. --}}
@endpush

@section('content')
@include('accounting.partials.flash')
@include('partials.purchaser-confirm-dialog')

@php
    $filters = [
        'incoming' => 'Ready for DV',
        'submitted' => 'With School Admin',
        'approved' => 'Send to President',
        'with_president' => 'With President',
        'ready_release' => 'Release check',
        'released' => 'Check released',
        'all' => 'All requests',
    ];
    $editDvId = (int) ($editDvId ?? 0);
    $viewDvId = (int) ($viewDvId ?? 0);
@endphp

<div
    class="acx space-y-6"
    x-data="{
        selectedDv: {{ $editDvId ?: ($viewDvId ?: 'null') }},
        editOpen: {{ $editDvId ? 'true' : 'false' }},
        viewOpen: {{ $viewDvId && ! $editDvId ? 'true' : 'false' }},
        emptyOpen: false,
        modalFullscreen: false,
        init() {
            if (this.editOpen && this.selectedDv) {
                this.bindDocSig('dv-' + this.selectedDv);
            }
        },
        openEdit(id) {
            this.selectedDv = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('dv-' + id);
        },
        openView(id) {
            this.selectedDv = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.modalFullscreen = false;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        bindDocSig(key) {
            this.$nextTick(() => {
                if (window.purchaserDocumentSignature) {
                    window.purchaserDocumentSignature.bind({
                        key: key,
                        hiddenId: 'purSigImage-' + key,
                        nameId: 'purSigName-' + key,
                        previewId: 'purSigOverlay-' + key,
                        slotId: 'purSigSlot-' + key,
                        title: 'School Accountant signature',
                        hint: 'Pick a saved signature, draw one, or upload. It overlays the accountant name on the voucher above.'
                    });
                }
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },
        closeModal() {
            this.editOpen = false;
            this.viewOpen = false;
            this.emptyOpen = false;
            this.selectedDv = null;
            this.modalFullscreen = false;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.reset === 'function') {
                window.purchaserDocumentSignature.reset();
            }
        },
        printDv(id) {
            if (window.accountingPrintForm) {
                window.accountingPrintForm({ sheetId: 'dv-print-' + id, page: 'portrait' });
                return;
            }
            window.print();
        },
        printEmptyDv() {
            if (window.accountingPrintForm) {
                window.accountingPrintForm({ sheetId: 'dv-print-blank', page: 'portrait' });
                return;
            }
            window.print();
        }
    }"
>
    @php
        $search = $search ?? '';
        $total = method_exists($packages, 'total') ? $packages->total() : $packages->count();
    @endphp

    <div class="flex flex-wrap items-center justify-end gap-3">
        <button
            type="button"
            @click="emptyOpen = true; modalFullscreen = false; $nextTick(() => window.lucide && window.lucide.createIcons())"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
            title="Blank paper only — create a DV from a request row below"
        >
            <i data-lucide="printer" class="h-4 w-4"></i>
            Print Empty DV
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-950">Requests</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                        {{ $total }}
                    </span>
                </div>

                <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if(! empty($focusRfc))
                        <input type="hidden" name="rfc" value="{{ $focusRfc }}">
                    @endif

                    <select
                        name="status"
                        aria-label="Filter by status"
                        onchange="this.form.submit()"
                        class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white sm:min-w-[11.5rem]"
                    >
                        @foreach($filters as $key => $label)
                            <option value="{{ $key }}" @selected($filter === $key)>
                                {{ $label }}@if(isset($counts[$key])) ({{ $counts[$key] }})@endif
                            </option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RFC, payee, DV..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-72"
                        >
                    </div>

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="search" class="h-4 w-4 shrink-0"></i>
                        Search
                    </button>

                    @if(filled($search) || $filter !== 'incoming')
                        <a
                            href="{{ route('accounting.dv.index', array_filter(['status' => 'incoming', 'rfc' => $focusRfc ?: null])) }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC / Request</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 max-md:hidden">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">DV status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($packages as $row)
                        @php
                            $rowDvId = !empty($row->dv_id) ? (int) $row->dv_id : null;
                            $rowEditable = $rowDvId && in_array((string) ($row->dv_status ?? ''), ['Draft', 'Revision'], true);
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="receipt" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $row->request_check_form_number ?: 'Request for Check' }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">
                                            Sent {{ $row->request_check_sent_to_accounting_at ? \Carbon\Carbon::parse($row->request_check_sent_to_accounting_at)->diffForHumans() : '—' }}
                                            @if($row->request_check_payee) · {{ $row->request_check_payee }}@endif
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                            <a href="{{ route('accounting.dv.package.rfc', $row->request_check_id) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">View RFC</a>
                                            @foreach(($row->atp_ids ?? []) as $atpId)
                                                <a href="{{ route('accounting.dv.package.atp', [$row->request_check_id, $atpId]) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">View ATP</a>
                                            @endforeach
                                            @foreach(($row->ris_ids ?? []) as $risId)
                                                <a href="{{ route('accounting.dv.package.ris', [$row->request_check_id, $risId]) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">View Canvas + RIS</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php $pkgRequestNo = $row->linked_dv_request_no ?? ($row->request_check_request_no ?? null); @endphp
                                @include('partials.procurement-request-no', ['requestNo' => $pkgRequestNo, 'inline' => true, 'showLabel' => false])
                                @if(blank($pkgRequestNo))
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600 max-md:hidden">{{ $row->atp_labels ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 tabular-nums text-gray-700">
                                {{ $row->request_check_amount_figures !== null ? '₱'.number_format((float) $row->request_check_amount_figures, 2) : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @if($rowDvId)
                                    <span class="inline-flex rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $row->dv_status }}{{ $row->dv_form_number ? ' · '.$row->dv_form_number : '' }}</span>
                                @else
                                    <span class="inline-flex rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">No DV yet</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if($rowDvId)
                                        @if($rowEditable)
                                            <button
                                                type="button"
                                                @click="openEdit({{ $rowDvId }})"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3]"
                                            >Edit DV</button>
                                        @else
                                            <button
                                                type="button"
                                                @click="openView({{ $rowDvId }})"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                            >Open DV</button>
                                        @endif
                                        @if(($row->dv_status ?? '') === 'Approved')
                                            <form method="POST" action="{{ route('accounting.dv.submit-president', $rowDvId) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-700">Submit to President</button>
                                            </form>
                                        @endif
                                        @if(($row->dv_status ?? '') === 'President Approved')
                                            <form method="POST" action="{{ route('accounting.dv.release-check', $rowDvId) }}" class="inline" onsubmit="return confirm('Mark this check as released and notify the Purchaser?');">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700">Release check</button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('accounting.dv.from-package') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="request_check_id" value="{{ $row->request_check_id }}">
                                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3]">Create DV draft</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="receipt" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No requests in this view</p>
                                <p class="mt-1 text-sm text-gray-400">When Purchaser submits an approved RFC request, it appears here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($packages->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $packages->links() }}</div>
        @endif
    </div>

    {{-- DV modals --}}
    @foreach($modalDvs ?? [] as $dv)
        @php
            $dvId = (int) $dv->dv_id;
            $canEdit = in_array((string) ($dv->dv_status ?? ''), ['Draft', 'Revision'], true);
            $pkg = $modalPackages[$dvId] ?? ['rfc' => null, 'atps' => collect(), 'risList' => collect()];
            $rfc = $pkg['rfc'] ?? null;
        @endphp

        @if($canEdit)
            <template x-teleport="body">
                <div
                    x-show="editOpen && selectedDv === {{ $dvId }}"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                    :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                    x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedDv === {{ $dvId }}, $el)"
                    @keydown.escape.window="if (editOpen && selectedDv === {{ $dvId }}) closeModal()"
                    role="dialog"
                    aria-modal="true"
                >
                    <div x-on:click.self="closeModal()" class="flex w-full justify-center" :class="modalFullscreen ? 'min-h-[100dvh] h-auto items-stretch' : 'min-h-full'">
                        <div
                            @click.stop
                            class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                            :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] h-auto max-w-none rounded-none shadow-none' : 'my-auto max-w-[900px] rounded-xl shadow-2xl'"
                        >
                            <div class="print-hidden flex items-start justify-between border-b border-gray-200 px-5 py-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">{{ $dv->dv_form_number ?: 'Draft DV' }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Status: {{ $dv->dv_status }}
                                        @if(filled(optional($rfc)->request_check_form_number))
                                            · {{ $rfc->request_check_form_number }}
                                        @endif
                                        @if(!empty($dv->dv_revision_reason))
                                            <span class="text-amber-700"> · {{ $dv->dv_revision_reason }}</span>
                                        @endif
                                    </p>
                                    @php
                                        $pkgAtps = collect($pkg['atps'] ?? []);
                                        $pkgRis = collect($pkg['risList'] ?? []);
                                        $pkgRfcId = (int) ($dv->dv_request_check_id ?? 0);
                                    @endphp
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                        <span class="text-slate-400">References:</span>
                                        <a href="{{ route('accounting.dv.package.rfc', $pkgRfcId) }}?edit_dv={{ $dvId }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">RFC</a>
                                        @foreach($pkgAtps as $pkgAtp)
                                            <a href="{{ route('accounting.dv.package.atp', [$pkgRfcId, $pkgAtp->authority_purchase_id]) }}?edit_dv={{ $dvId }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">{{ $pkgAtp->authority_purchase_form_number ?: 'ATP' }}</a>
                                        @endforeach
                                        @foreach($pkgRis as $pkgRisRow)
                                            <a href="{{ route('accounting.dv.package.ris', [$pkgRfcId, $pkgRisRow->ris_id]) }}?edit_dv={{ $dvId }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">Canvas + RIS</a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="modalFullscreen = !modalFullscreen" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" :aria-label="modalFullscreen ? 'Exit fullscreen' : 'Fullscreen'">
                                        <i :data-lucide="modalFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="printDv({{ $dvId }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Print">
                                        <i data-lucide="printer" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="closeModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('accounting.dv.update', $dvId) }}"
                                class="bg-white px-4 py-4 sm:px-6"
                                data-pur-confirm-title="Submit DV"
                                data-pur-confirm-ok="Submit"
                                data-dv-form="{{ $dvId }}"
                                x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                            >
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="save_action" value="draft" data-dv-save-action>
                                <input type="hidden" name="_dv_id" value="{{ $dvId }}">

                                @include('partials.disbursement-voucher-paper', [
                                    'dv' => $dv,
                                    'editable' => true,
                                    'printId' => 'dv-print-'.$dvId,
                                    'signKey' => 'dv-'.$dvId,
                                ])

                                <div id="purSigSlot-dv-{{ $dvId }}" class="print-hidden mt-4 w-full"></div>

                                <div class="print-hidden mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
                                    <button type="button" @click="closeModal()" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900">Close</button>
                                    <button
                                        type="submit"
                                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                        onclick="var f=this.form; f.querySelector('[data-dv-save-action]').value='draft'; f.removeAttribute('data-pur-confirm'); f.removeAttribute('data-pur-confirm-reviewer-role');"
                                    >Save draft</button>
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-[#001ea3]"
                                        onclick="var f=this.form; f.querySelector('[data-dv-save-action]').value='submit'; f.setAttribute('data-pur-confirm','Submit this Disbursement Voucher to the School Administrator?'); f.setAttribute('data-pur-confirm-reviewer-role','School Administrator');"
                                    >Submit to School Administrator</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        @else
            <template x-teleport="body">
                <div
                    x-show="viewOpen && selectedDv === {{ $dvId }}"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                    :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                    x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedDv === {{ $dvId }}, $el)"
                    @keydown.escape.window="if (viewOpen && selectedDv === {{ $dvId }}) closeModal()"
                    role="dialog"
                    aria-modal="true"
                >
                    <div x-on:click.self="closeModal()" class="flex w-full justify-center" :class="modalFullscreen ? 'min-h-[100dvh] h-auto items-stretch' : 'min-h-full'">
                        <div
                            @click.stop
                            class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                            :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] h-auto max-w-none rounded-none shadow-none' : 'my-auto max-w-[900px] rounded-xl shadow-2xl'"
                        >
                            <div class="print-hidden flex items-start justify-between border-b border-gray-200 px-5 py-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Status: {{ $dv->dv_status }}
                                        @if(filled(optional($rfc)->request_check_form_number))
                                            · {{ $rfc->request_check_form_number }}
                                        @endif
                                    </p>
                                    @php
                                        $pkgAtps = collect($pkg['atps'] ?? []);
                                        $pkgRis = collect($pkg['risList'] ?? []);
                                        $pkgRfcId = (int) ($dv->dv_request_check_id ?? 0);
                                    @endphp
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                        <span class="text-slate-400">References:</span>
                                        <a href="{{ route('accounting.dv.package.rfc', $pkgRfcId) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">RFC</a>
                                        @foreach($pkgAtps as $pkgAtp)
                                            <a href="{{ route('accounting.dv.package.atp', [$pkgRfcId, $pkgAtp->authority_purchase_id]) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">{{ $pkgAtp->authority_purchase_form_number ?: 'ATP' }}</a>
                                        @endforeach
                                        @foreach($pkgRis as $pkgRisRow)
                                            <a href="{{ route('accounting.dv.package.ris', [$pkgRfcId, $pkgRisRow->ris_id]) }}" target="_blank" class="font-medium text-[#0025cc] hover:underline">Canvas + RIS</a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="printDv({{ $dvId }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100" aria-label="Print">
                                        <i data-lucide="printer" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="closeModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100" aria-label="Close">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="px-4 py-4 sm:px-6">
                                @include('partials.disbursement-voucher-paper', [
                                    'dv' => $dv,
                                    'editable' => false,
                                    'printId' => 'dv-print-'.$dvId,
                                ])
                            </div>
                            <div class="print-hidden flex justify-end border-t border-gray-100 px-5 py-4">
                                <button type="button" @click="closeModal()" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endif
    @endforeach

    {{-- PRINT EMPTY DV (blank paper only — no create without request) --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="emptyOpen"
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
            @keydown.escape.window="if (emptyOpen) { emptyOpen = false; modalFullscreen = false; }"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dv-empty-title"
        >
            <div x-on:click.self="emptyOpen = false; modalFullscreen = false" class="flex w-full justify-center" :class="modalFullscreen ? 'min-h-[100dvh] h-auto items-stretch' : 'min-h-full'">
                <div
                    @click.stop
                    class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                    :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] h-auto max-w-none rounded-none shadow-none' : 'my-auto max-w-[900px] rounded-xl shadow-2xl'"
                >
                    <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <div>
                            <h3 id="dv-empty-title" class="text-lg font-semibold text-slate-900">Print Empty DV</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Blank Disbursement Voucher for manual/offline use. System DVs still require an approved request (Canvas + RIS + ATP + RFC).</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="modalFullscreen = !modalFullscreen" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" :aria-label="modalFullscreen ? 'Exit fullscreen' : 'Fullscreen'">
                                <i :data-lucide="modalFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                            </button>
                            <button type="button" @click="printEmptyDv()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Print">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                            <button type="button" @click="emptyOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-slate-100 px-4 py-4 sm:px-6">
                        @include('partials.disbursement-voucher-paper', [
                            'dv' => null,
                            'editable' => false,
                            'printId' => 'dv-print-blank',
                        ])
                    </div>
                    <div class="print-hidden flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" @click="emptyOpen = false; modalFullscreen = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900">Close</button>
                        <button type="button" @click="printEmptyDv()" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-[#001ea3]">Print</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div id="purDocSignatureDock" class="hidden">
        @include('purchaser.partials.document-signature-panel', [
            'savedSignatures' => $savedSignatures ?? collect(),
            'signatureStoreUrl' => route('accounting.saved-signatures.store'),
            'signatureDestroyBase' => url('/accounting/saved-signatures'),
        ])
    </div>
</div>
@endsection
