@extends('layouts.purchaser-layout')

@section('page-title', 'Request for Check')
@section('page-subtitle', 'Create, submit, print, and archive Request for Check records.')

@section('content')

<script type="application/json" id="rfc-atp-prefill">{!! json_encode($atpPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('request_check_authority_purchase_id')) || !empty($selectedAtpId) ? 'true' : 'false' }},
        viewOpen: false,
        editOpen: false,
        selectedRfc: null,
        atpPrefill: JSON.parse(document.getElementById('rfc-atp-prefill').textContent || '{}'),

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
            if (payee && data.payee) payee.value = data.payee;
            if (amount && data.amount) amount.value = data.amount;
            if (purpose && data.purpose) purpose.value = data.purpose;
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
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Request for Check</h2>
            <p class="text-sm text-slate-600">Linked to approved ATP. After Admin signs, Accounting releases funds so you can collect the check and create a Receiving Report.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchaser.rfc.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Active</a>
            <a href="{{ route('purchaser.rfc.index', ['view' => 'archive']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Archive</a>
            @unless($archiveView)
                <button type="button" @click="createOpen = true" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Create</button>
                <button type="button" @click="printRfc('blank')" class="h-10 rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Print blank</button>
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
            <a href="{{ $card[3] }}" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $card[0] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $card[1] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 lg:grid-cols-5">
        @if($archiveView)
            <input type="hidden" name="view" value="archive">
        @endif
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search RFC, ATP, payee, or purpose" class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2">
        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All statuses</option>
            @foreach(['Draft','Submitted','Minor Revision','Approved','Rejected'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
        <div class="flex gap-2">
            <button type="submit" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Search</button>
            <a href="{{ $archiveView ? route('purchaser.rfc.index', ['view' => 'archive']) : route('purchaser.rfc.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Funds released</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Waiting for funds</span>
                                    @endif
                                @elseif($rfc->request_check_status === 'Rejected')
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Rejected</span>
                                @elseif($rfc->request_check_status === 'Draft')
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Draft</span>
                                @elseif($rfc->request_check_status === 'Minor Revision')
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Minor Revision</span>
                                @elseif($rfc->request_check_status === 'Pending Admin Approval')
                                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">For Admin</span>
                                @else
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $rfc->request_check_status }}</span>
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
                                            <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white">Submit</button>
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
    </div>

    <div>{{ $rfcs->links() }}</div>

    {{-- CREATE --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/40" @click="createOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">Create Request for Check</h3>
                        <p class="mt-1 text-sm text-gray-500">Select an approved ATP. Submit sends this to Accounting.</p>
                    </div>
                    <button type="button" @click="createOpen = false" class="rounded-lg p-2 text-gray-400">✕</button>
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
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white">Save & Submit</button>
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

        <div x-show="viewOpen && selectedRfc === {{ $rfc->request_check_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">{{ $rfc->request_check_form_number }}</h3>
                            <p class="mt-1 text-sm text-gray-500">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}
                                @if(!empty($rfc->receiving_report_form_number))
                                    · RR: {{ $rfc->receiving_report_form_number }} ({{ $rfc->receiving_report_status }})
                                @endif
                            </p>
                            @if($rfc->request_check_revision_notes)
                                <p class="mt-2 text-sm text-amber-700">Revision notes: {{ $rfc->request_check_revision_notes }}</p>
                            @endif
                            @if($rfc->request_check_rejection_reason)
                                <p class="mt-2 text-sm text-red-700">Rejection: {{ $rfc->request_check_rejection_reason }}</p>
                            @endif
                        </div>
                        <button type="button" @click="viewOpen = false" class="rounded-lg p-2 text-gray-400">✕</button>
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
                        <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="h-10 rounded-lg bg-gray-900 px-5 text-sm text-white">Print</button>
                        <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>

        @if($canEdit)
            <div x-show="editOpen && selectedRfc === {{ $rfc->request_check_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <h3 class="text-xl font-semibold text-slate-900">Edit {{ $rfc->request_check_form_number }}</h3>
                            <button type="button" @click="editOpen = false" class="rounded-lg p-2 text-gray-400">✕</button>
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
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

    <div class="hidden">
        @include('partials.request-check-paper', ['editable' => false, 'rfc' => null, 'printId' => 'rfc-print-blank'])
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
