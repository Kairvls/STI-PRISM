@extends('layouts.purchaser-layout')

@section('page-title', 'Liquidation Reports')
@section('page-subtitle', 'Liquidate cash advances from completed receiving reports.')

@section('content')
<script type="application/json" id="liq-rr-prefill">{!! json_encode($rrPrefill ?? []) !!}</script>
<div
    x-data="{
        createOpen: {{ ($errors->any() && old('liquidation_report_receiving_report_id')) || !empty($selectedRrId) ? 'true' : 'false' }},
        viewOpen: false,
        editOpen: false,
        selectedLiq: null,
        rrPrefill: JSON.parse(document.getElementById('liq-rr-prefill').textContent || '{}'),
        openView(id) { this.selectedLiq = id; this.viewOpen = true; this.editOpen = false; },
        openEdit(id) { this.selectedLiq = id; this.editOpen = true; this.viewOpen = false; },
        applyRrPrefill(rrId) {
            const data = this.rrPrefill[String(rrId)];
            const form = this.$refs.createForm;
            if (!form || !data) return;
            const purpose = form.querySelector('[name=liquidation_report_purpose]');
            const amount = form.querySelector('[name=liquidation_report_amount_advance]');
            if (purpose && data.purpose) purpose.value = data.purpose;
            if (amount && data.amount) amount.value = data.amount;
            const rows = data.items || [];
            for (let i = 0; i < 8; i++) {
                const item = rows[i] || {};
                const p = form.querySelector('[name=\'items[' + i + '][particulars]\']');
                const a = form.querySelector('[name=\'items[' + i + '][amount]\']');
                const aa = form.querySelector('[name=\'items[' + i + '][actual_amount]\']');
                const at = form.querySelector('[name=\'items[' + i + '][actual_total]\']');
                if (p) p.value = item.particulars ?? '';
                if (a) a.value = item.amount ?? '';
                if (aa) aa.value = item.actual_amount ?? '';
                if (at) at.value = item.actual_total ?? '';
            }
        },
        printLiq(id) {
            document.querySelectorAll('.liq-print-sheet').forEach(function (s) { s.classList.remove('liq-print-active'); });
            const sheet = document.getElementById('liq-print-' + id);
            if (sheet) sheet.classList.add('liq-print-active');
            window.print();
        }
    }"
    x-init="
        if (createOpen && '{{ $selectedRrId ?? '' }}') {
            $nextTick(() => applyRrPrefill('{{ $selectedRrId ?? '' }}'));
        }
    "
    class="space-y-6"
>
    @if(session('success'))<div class="pur-alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="pur-alert-error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="pur-alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="pur-page-kicker">Purchasing Workflow</p>
            <h2 class="pur-page-title">Liquidation Reports</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchaser.liq.index') }}" class="pur-btn-secondary">Active</a>
            <a href="{{ route('purchaser.liq.index', ['view' => 'archive']) }}" class="pur-btn-secondary">Archive</a>
            @unless($archiveView)
                <button type="button" @click="createOpen = true" class="pur-btn-primary">Create</button>
            @endunless
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        @foreach([['Total',$summary['total'],route('purchaser.liq.index')],['Draft',$summary['draft'],route('purchaser.liq.index',['status'=>'Draft'])],['In Review',$summary['submitted'],route('purchaser.liq.index',['status'=>'Submitted'])],['Approved',$summary['approved'],route('purchaser.liq.index',['status'=>'Approved'])],['Rejected',$summary['rejected'],route('purchaser.liq.index',['status'=>'Rejected'])],['Archived',$summary['archived'],route('purchaser.liq.index',['view'=>'archive'])]] as $card)
            <a href="{{ $card[2] }}" class="pur-stat-card"><p class="text-sm text-gray-500">{{ $card[0] }}</p><p class="mt-3 text-3xl font-semibold">{{ $card[1] }}</p></a>
        @endforeach
    </div>

    <form method="GET" class="pur-card grid gap-3 p-4 lg:grid-cols-5">
        @if($archiveView)<input type="hidden" name="view" value="archive">@endif
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search liquidation, RR, employee" class="h-10 rounded-lg border px-3 text-sm lg:col-span-2">
        <select name="status" class="h-10 rounded-lg border px-3 text-sm">
            <option value="">All statuses</option>
            @foreach(['Draft','Submitted','Minor Revision','Approved','Rejected'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ $s }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="h-10 rounded-lg border px-3 text-sm">
        <button class="pur-btn-primary">Search</button>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white">
        <table class="w-full min-w-[1000px] text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">No.</th><th class="px-4 py-3">RR</th><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($reports as $liq)
                    @php $editable = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView; @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $liq->liquidation_report_form_number }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $liq->receiving_report_form_number ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $liq->liquidation_report_employee_name ?: '—' }}</td>
                        <td class="px-4 py-4">{{ $liq->liquidation_report_amount_advance !== null ? '₱'.number_format((float)$liq->liquidation_report_amount_advance,2) : '—' }}</td>
                        <td class="px-4 py-4">@include('accounting.partials.status-badge', ['status' => $liq->liquidation_report_status])</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openView({{ $liq->liquidation_report_id }})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                <button type="button" @click="printLiq({{ $liq->liquidation_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Print</button>
                                <a href="{{ route('purchaser.liq.export-xlsx', $liq->liquidation_report_id) }}" class="rounded-lg border px-3 py-2 text-xs">Excel</a>
                                <a href="{{ route('purchaser.liq.export-docx', $liq->liquidation_report_id) }}" class="rounded-lg border px-3 py-2 text-xs">Word</a>
                                @if($editable)
                                    <button type="button" @click="openEdit({{ $liq->liquidation_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Edit</button>
                                    <form method="POST" action="{{ route('purchaser.liq.submit', $liq->liquidation_report_id) }}">@csrf<button class="pur-btn-primary !px-3 !py-2 !text-xs">Submit</button></form>
                                @endif
                                @if($archiveView)
                                    <form method="POST" action="{{ route('purchaser.liq.restore', $liq->liquidation_report_id) }}">@csrf<button class="rounded-lg border px-3 py-2 text-xs">Restore</button></form>
                                @elseif(in_array($liq->liquidation_report_status, ['Approved','Rejected'], true))
                                    <form method="POST" action="{{ route('purchaser.liq.archive', $liq->liquidation_report_id) }}" onsubmit="return confirm('Archive this liquidation?')">@csrf<button class="rounded-lg bg-gray-100 px-3 py-2 text-xs">Archive</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No liquidation reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $reports->links() }}</div>

    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/40" @click="createOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                <div class="flex justify-between border-b px-6 py-5"><h3 class="text-xl font-semibold">Create Liquidation Report</h3><button type="button" @click="createOpen=false">✕</button></div>
                @if($eligibleRrs->isEmpty())
                    <div class="p-6 text-sm text-gray-600">No completed Receiving Report is available.</div>
                @else
                    <form method="POST" action="{{ route('purchaser.liq.store') }}" enctype="multipart/form-data" x-ref="createForm">
                        @csrf
                        <input type="hidden" name="save_action" value="draft">
                        <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                            <div class="mx-auto mb-4 w-[297mm] max-w-full">
                                <label class="text-xs text-gray-500">Completed Receiving Report</label>
                                <select name="liquidation_report_receiving_report_id" x-on:change="applyRrPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select RR</option>
                                    @foreach($eligibleRrs as $rr)
                                        <option value="{{ $rr->receiving_report_id }}" {{ old('liquidation_report_receiving_report_id', $selectedRrId ?? '') == $rr->receiving_report_id ? 'selected' : '' }}>
                                            {{ $rr->receiving_report_form_number }} @if($rr->request_check_form_number)· {{ $rr->request_check_form_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- #region agent log --}}
                            @php
                                file_put_contents(base_path('debug-fcd40d.log'), json_encode(['sessionId' => 'fcd40d', 'runId' => 'pre-fix', 'hypothesisId' => 'A', 'location' => 'purchaser/liquidation-reports/index.blade.php:create', 'message' => 'including create paper with null liq', 'data' => ['eligibleRrCount' => $eligibleRrs->count(), 'explicitLiqNull' => true, 'createOpenHint' => ($errors->any() && old('liquidation_report_receiving_report_id')) || !empty($selectedRrId)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
                            @endphp
                            {{-- #endregion --}}
                            @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => null, 'rows' => collect()])
                            <div class="mx-auto mt-3 w-[297mm] max-w-full rounded bg-white p-3 text-sm">
                                <label>Supporting documents (PDF, JPG, PNG · 5MB)</label>
                                <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 border-t px-6 py-4">
                            <button type="submit" class="rounded-lg border px-4 py-2 text-sm">Save Draft</button>
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @foreach($reports as $liq)
        @php
            $liqItems = $items->get($liq->liquidation_report_id, collect())->values();
            $liqFiles = $attachments->get($liq->liquidation_report_id, collect());
            $canEdit = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
            // #region agent log
            file_put_contents(base_path('debug-fcd40d.log'), json_encode(['sessionId' => 'fcd40d', 'runId' => 'pre-fix', 'hypothesisId' => 'B', 'location' => 'purchaser/liquidation-reports/index.blade.php:foreach', 'message' => 'existing report include context', 'data' => ['liqIsNull' => $liq === null, 'liqId' => is_object($liq) ? ($liq->liquidation_report_id ?? null) : null, 'status' => is_object($liq) ? ($liq->liquidation_report_status ?? null) : null, 'canEdit' => $canEdit], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
            // #endregion
        @endphp
        <div x-show="viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen=false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $liq->liquidation_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RR: {{ $liq->receiving_report_form_number ?? '—' }}</p>
                        </div>
                        <button type="button" @click="viewOpen=false">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.liquidation-report-paper', ['editable' => false, 'liq' => $liq, 'rows' => $liqItems, 'printId' => 'liq-print-'.$liq->liquidation_report_id])
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div x-show="editOpen && selectedLiq === {{ $liq->liquidation_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="editOpen=false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                        <form method="POST" action="{{ route('purchaser.liq.update', $liq->liquidation_report_id) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="liquidation_report_receiving_report_id" value="{{ $liq->liquidation_report_receiving_report_id }}">
                            <div class="flex justify-between border-b px-6 py-5"><h3 class="text-xl font-semibold">Edit {{ $liq->liquidation_report_form_number }}</h3><button type="button" @click="editOpen=false">✕</button></div>
                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                                @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => $liq, 'rows' => $liqItems])
                                @foreach($liqFiles as $file)
                                    <label class="mx-auto mt-1 flex w-[297mm] max-w-full items-center gap-2 text-sm"><input type="checkbox" name="delete_attachments[]" value="{{ $file->liquidation_attachment_id }}"> Remove {{ $file->liquidation_attachment_original_name }}</label>
                                @endforeach
                                <input type="file" name="attachments[]" multiple class="mx-auto mt-2 block w-[297mm] max-w-full text-sm">
                            </div>
                            <div class="flex justify-end gap-2 border-t px-6 py-4">
                                <button type="submit" class="rounded-lg border px-4 py-2 text-sm">Update Draft</button>
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
<style>
[x-cloak]{display:none!important}
@media print {
    body * { visibility: hidden !important; }
    .liq-print-active, .liq-print-active * { visibility: visible !important; }
    .liq-print-active { position:absolute!important; left:0; top:0; width:297mm!important; box-shadow:none!important; }
    @page { size: A4 landscape; margin: 10mm; }
}
</style>
@endsection
