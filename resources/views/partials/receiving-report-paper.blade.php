@php
    $editable = $editable ?? false;
    $allowMultiSupplier = $allowMultiSupplier ?? false;
    $suppliers = $suppliers ?? collect();
    $signSecondCount = $signSecondCount ?? false;
    $showOrCard = $showOrCard ?? true;
    $showItemPhotos = $showItemPhotos ?? true;
    $rr = $rr ?? null;
    $rows = $rows ?? collect();
    $todayDate = now()->format('Y-m-d');
    $dateValue = old(
        'receiving_report_date',
        filled($rr?->receiving_report_date) ? $rr->receiving_report_date : ($editable ? $todayDate : '')
    );
    $fromValue = old('receiving_report_received_from', $rr?->receiving_report_received_from ?? '');
    $addressValue = old('receiving_report_supplier_address_override', $rr?->receiving_report_supplier_address_override ?? '');
    $invoiceValue = old('receiving_report_invoice_no', $rr?->receiving_report_invoice_no ?? '');
    $orNumberValue = old('receiving_report_or_number', $rr?->receiving_report_or_number ?? '');
    $orAmountValue = old('receiving_report_or_amount', $rr?->receiving_report_or_amount ?? '');
    $checkAmountValue = old('receiving_report_check_amount', $rr?->receiving_report_check_amount ?? '');
    $changeAmountValue = old('receiving_report_change_amount', $rr?->receiving_report_change_amount ?? '');
    $orAttachmentName = $rr?->receiving_report_or_attachment_name ?? null;
    $orAttachmentPath = $rr?->receiving_report_or_attachment_path ?? null;
    $drValue = old('receiving_report_dr_no', $rr?->receiving_report_dr_no ?? '');
    $deliveryValue = old(
        'receiving_report_delivery_date',
        filled($rr?->receiving_report_delivery_date) ? $rr->receiving_report_delivery_date : ($editable ? $todayDate : '')
    );
    $legacyReceivedSig = (string) ($rr?->receiving_report_received_by_signature ?? '');
    $storedReceivedName = trim((string) ($rr?->receiving_report_received_by_name ?? ''));
    if ($storedReceivedName === '' && !\App\Support\RisWorkflow::isDrawnSignature($legacyReceivedSig)) {
        $storedReceivedName = trim($legacyReceivedSig);
    }
    $receivedByNameDefault = $storedReceivedName;
    if ($editable && $receivedByNameDefault === '') {
        $receivedByNameDefault = (string) (auth()->user()->user_full_name ?? '');
    }
    $receivedByName = old('receiving_report_received_by_name', $receivedByNameDefault);
    $receivedBySignature = old(
        'receiving_report_received_by_signature',
        \App\Support\RisWorkflow::isDrawnSignature($legacyReceivedSig) ? $legacyReceivedSig : ''
    );
    $receivedBy = $receivedByName;
    $signKey = $signKey ?? ($rr?->receiving_report_id ? 'rr-'.$rr->receiving_report_id : 'rr-create');
    $secondCount = $rr?->receiving_report_second_count_signature ?? $rr?->receiving_report_second_count_by ?? '';
    $officerName = $officerName ?? (auth()->user()->user_full_name ?? 'Receiving Officer');
    $signSuffix = $signSuffix ?? (string) ($rr?->receiving_report_id ?? 'sc');
    $suggestedRrFormNumber = $suggestedRrFormNumber ?? \App\Support\RrFormNumber::next();
    if (old('receiving_report_form_number') !== null) {
        $formNo = (string) old('receiving_report_form_number');
    } elseif ($rr) {
        $existingNo = trim((string) ($rr->receiving_report_form_number ?? ''));
        $formNo = \App\Support\RrFormNumber::isValid($existingNo) ? $existingNo : '';
    } else {
        $formNo = '';
    }
    $oldItems = $editable && is_array(old('items')) ? array_values(old('items')) : [];
    $rrDefaultRows = 9;
    $rrMaxRows = 50;
    $rrRowCount = max($rrDefaultRows, count($rows));
    if ($editable) {
        $rrRowCount = min($rrMaxRows, $oldItems !== [] ? count($oldItems) : $rrRowCount);
    }
    $verifiedItemIds = collect($rows)
        ->filter(fn ($row) => is_object($row) && !empty($row->receiving_report_item_verified))
        ->map(fn ($row) => (int) $row->receiving_report_item_id)
        ->all();
    $replacementLabels = \App\Support\BackOrders::replacementLabels($rows);
    $uomNames = $editable
        ? collect($uomNames ?? once(fn () => \Illuminate\Support\Facades\Schema::hasTable('uom_table')
            ? \Illuminate\Support\Facades\DB::table('uom_table')->orderBy('uom_name')->pluck('uom_name')->map(fn ($name) => (string) $name)->all()
            : []))->values()
        : collect();
    $rrQtyAttrs = 'min="0" max="9999999" step="1" inputmode="numeric"'
        .' onkeydown="if ([\'e\', \'E\', \'+\', \'-\', \'.\'].includes(event.key)) event.preventDefault();"'
        .' oninput="if (this.value.length > 7) this.value = this.value.replace(/\D/g, \'\').slice(0, 7);"';
@endphp

<div
    @if(!empty($printId)) id="{{ $printId }}" @endif
    class="rr-print-sheet mx-auto w-full max-w-[1095px] bg-white px-10 pb-[36px] pt-[42px] text-[13px] text-black shadow {{ $printClass ?? '' }}"
    style="min-height: 0; height: auto;"
>
    <div class="relative">
        <div class="absolute left-0 top-0 flex items-end gap-1 text-sm font-semibold text-red-700">
            <span>№</span>
            @if($editable)
                <input
                    type="text"
                    name="receiving_report_form_number"
                    value="{{ $formNo }}"
                    maxlength="17"
                    pattern="RR-\d{6}-\d{7}"
                    title="Assigned on submit (RR-YYYYMMDD-0000001)"
                    placeholder="{{ $suggestedRrFormNumber }}"
                    class="w-44 border-0 bg-transparent px-0 font-semibold text-red-700 outline-none"
                >
            @else
                <span>{{ $formNo ?: '______' }}</span>
            @endif
        </div>
        <div class="text-center">
            <div class="text-lg font-bold">STI-College - ORMOC, INC.</div>
            <div class="text-sm">Ormoc City</div>
            <div class="mt-2 text-xl font-bold underline">RECEIVING REPORT</div>
        </div>
        <div class="absolute right-0 top-0 flex items-end gap-1 text-sm">
            <span>Date: @if($editable)<span class="text-red-500">*</span>@endif</span>
            @if($editable)
                <input type="date" name="receiving_report_date" value="{{ $dateValue }}" class="h-7 w-36 border-0 border-b border-black bg-transparent px-1 outline-none">
            @else
                <span class="inline-block min-w-[8rem] border-b border-black px-1">{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d/m/Y') : '' }}</span>
            @endif
        </div>
    </div>

    <div class="mt-10 grid grid-cols-2 gap-8">
        <div class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="shrink-0">Received from: @if($editable)<span class="text-red-500">*</span>@endif</span>
                @if($editable)
                    <input type="text" name="receiving_report_received_from" value="{{ $fromValue }}" maxlength="{{ \App\Support\InputLimits::NAME }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="h-7 min-w-0 flex-1 overflow-hidden whitespace-nowrap border-b border-black leading-7">{{ $fromValue }}</span>
                @endif
            </div>
            <div class="flex items-start gap-2">
                <span class="shrink-0 leading-[1.75rem]">Address:</span>
                <div class="min-w-0 flex-1">
                    @if($editable)
                        <textarea
                            name="receiving_report_supplier_address_override"
                            rows="2"
                            maxlength="2000"
                            class="rr-address-textarea w-full resize-none border-0 bg-transparent px-0 text-[13px] outline-none"
                        >{{ $addressValue }}</textarea>
                    @else
                        <div class="rr-address-textarea w-full whitespace-pre-wrap break-words">{{ $addressValue }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="shrink-0">Refer Invoice No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_invoice_no" value="{{ $invoiceValue }}" maxlength="{{ \App\Support\InputLimits::DOC_REF_NO }}" pattern="[A-Za-z0-9\/\-]*" title="Letters, numbers, - and / only · max {{ \App\Support\InputLimits::DOC_REF_NO }}" oninput="this.value = this.value.replace(/[^A-Za-z0-9\/\-]/g, '').slice(0, {{ \App\Support\InputLimits::DOC_REF_NO }});" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none" autocomplete="off">
                @else
                    <span class="flex-1 border-b border-black">{{ $invoiceValue }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="shrink-0">D.R. No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_dr_no" value="{{ $drValue }}" maxlength="{{ \App\Support\InputLimits::DOC_REF_NO }}" pattern="[A-Za-z0-9\/\-]*" title="Letters, numbers, - and / only · max {{ \App\Support\InputLimits::DOC_REF_NO }}" oninput="this.value = this.value.replace(/[^A-Za-z0-9\/\-]/g, '').slice(0, {{ \App\Support\InputLimits::DOC_REF_NO }});" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none" autocomplete="off">
                @else
                    <span class="flex-1 border-b border-black">{{ $drValue }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="shrink-0">Date:</span>
                @if($editable)
                    <input type="date" name="receiving_report_delivery_date" value="{{ $deliveryValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $deliveryValue ? \Carbon\Carbon::parse($deliveryValue)->format('d/m/Y') : '' }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Official Receipt amount vs check — change returned to cashier; OR amount settles the DV (screen only; omitted from print) --}}
    @if($showOrCard)
    <div
        class="rr-or-card print-hidden mt-5 rounded border border-black/20 bg-slate-50/80 p-3 text-[12px]"
        @if($editable)
            x-data="{
                checkAmt: {{ $checkAmountValue !== '' && $checkAmountValue !== null ? (float) $checkAmountValue : 'null' }},
                orAmt: {{ $orAmountValue !== '' && $orAmountValue !== null ? (float) $orAmountValue : 'null' }},
                orFileName: @js($orAttachmentName ?: ''),
                orCleared: false,
                clearOrFile() {
                    this.orFileName = '';
                    this.orCleared = true;
                    if (this.$refs.orAttachment) this.$refs.orAttachment.value = '';
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                },
                onOrPick(e) {
                    const f = e.target.files && e.target.files[0];
                    this.orFileName = f ? f.name : '';
                    if (this.orFileName) this.orCleared = false;
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                },
                limitMoney(raw) {
                    let v = String(raw ?? '').replace(/[^\d.]/g, '');
                    const parts = v.split('.');
                    parts[0] = (parts[0] || '').slice(0, 10);
                    if (parts.length > 1) {
                        v = parts[0] + '.' + parts.slice(1).join('').slice(0, 2);
                    } else {
                        v = parts[0];
                    }
                    if (v === '' || v === '.') return null;
                    const n = Number(v);
                    return Number.isNaN(n) ? null : n;
                },
                setCheck(e) { this.checkAmt = this.limitMoney(e.target.value); e.target.value = this.checkAmt ?? ''; },
                setOr(e) { this.orAmt = this.limitMoney(e.target.value); e.target.value = this.orAmt ?? ''; },
                changeAmt() {
                    if (this.checkAmt === null || this.checkAmt === '' || this.orAmt === null || this.orAmt === '') return null;
                    const c = Number(this.checkAmt);
                    const o = Number(this.orAmt);
                    if (Number.isNaN(c) || Number.isNaN(o)) return null;
                    return Math.max(0, Math.round((c - o) * 100) / 100);
                },
                fmt(n) {
                    if (n === null || n === '' || Number.isNaN(Number(n))) return '—';
                    return '₱' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }"
            x-init="window.addEventListener('rr-check-amount', (e) => { checkAmt = limitMoney(e.detail?.amount) })"
        @endif
    >
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Official Receipt · Check vs OR</p>
        <div class="mb-3 grid items-start gap-3 sm:grid-cols-3">
            <div class="min-w-0 self-start">
                <label class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">OR number @if($editable)<span class="text-red-500">*</span>@endif</label>
                @if($editable)
                    <input
                        type="text"
                        name="receiving_report_or_number"
                        value="{{ $orNumberValue }}"
                        maxlength="{{ \App\Support\InputLimits::DOC_REF_NO }}"
                        pattern="[A-Za-z0-9\/\-]*"
                        title="Letters, numbers, - and / only · max {{ \App\Support\InputLimits::DOC_REF_NO }}"
                        oninput="this.value = this.value.replace(/[^A-Za-z0-9\/\-]/g, '').slice(0, {{ \App\Support\InputLimits::DOC_REF_NO }});"
                        class="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2 text-sm"
                        placeholder="Official Receipt number"
                        autocomplete="off"
                    >
                @else
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $orNumberValue !== '' ? $orNumberValue : '—' }}</p>
                @endif
            </div>
        </div>
        <div class="grid items-start gap-3 sm:grid-cols-3">
            <div class="min-w-0 self-start">
                <label class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">Check / funds amount</label>
                @if($editable)
                    <input type="hidden" name="receiving_report_check_amount" :value="checkAmt ?? ''">
                    <p class="mt-1 flex h-8 shrink-0 items-center rounded border border-slate-200 bg-slate-100 px-2 text-sm font-semibold tabular-nums text-slate-800" x-text="fmt(checkAmt)"></p>
                    <p class="mt-0.5 text-[10px] text-slate-400">From released check — not editable</p>
                @else
                    <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                        {{ $checkAmountValue !== '' && $checkAmountValue !== null ? '₱'.number_format((float) $checkAmountValue, 2) : '—' }}
                    </p>
                @endif
            </div>
            <div class="min-w-0 self-start">
                <label class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">OR amount (actual spend) @if($editable)<span class="text-red-500">*</span>@endif</label>
                @if($editable)
                    <input
                        type="text"
                        inputmode="decimal"
                        name="receiving_report_or_amount"
                        :value="orAmt ?? ''"
                        x-on:input="setOr($event)"
                        maxlength="13"
                        pattern="\d{1,10}(\.\d{1,2})?"
                        title="Up to 10 digits (optional 2 decimals)"
                        class="mt-1 h-8 w-full shrink-0 rounded border border-slate-300 bg-white px-2 text-sm tabular-nums"
                        placeholder="Amount on the Official Receipt (required to submit)"
                        autocomplete="off"
                    >
                @else
                    <p class="mt-1 text-sm font-semibold tabular-nums text-slate-900">
                        {{ $orAmountValue !== '' && $orAmountValue !== null ? '₱'.number_format((float) $orAmountValue, 2) : '—' }}
                    </p>
                @endif
            </div>
            <div class="min-w-0 self-start">
                <label class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">Change (return to cashier)</label>
                @if($editable)
                    <input type="hidden" name="receiving_report_change_amount" :value="changeAmt() ?? ''">
                    <p class="mt-1 flex h-8 shrink-0 items-center text-sm font-semibold tabular-nums text-amber-800" x-text="fmt(changeAmt())"></p>
                @else
                    <p class="mt-1 text-sm font-semibold tabular-nums text-amber-800">
                        {{ $changeAmountValue !== '' && $changeAmountValue !== null ? '₱'.number_format((float) $changeAmountValue, 2) : '—' }}
                    </p>
                @endif
            </div>
        </div>
        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-950">
                        OR photo / attachment
                        @if($editable)<span class="text-red-500">*</span>@endif
                    </p>
                    <p class="truncate text-[11px] text-slate-500">Required to submit · 1 file · PDF, JPG, PNG · max 10MB</p>
                </div>
            </div>
            @if($editable)
                <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
                    <template x-if="orCleared && !orFileName">
                        <input type="hidden" name="clear_or_attachment" value="1">
                    </template>
                    <div
                        class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5"
                        x-show="orFileName"
                        x-cloak
                    >
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200/80">
                            <i data-lucide="file-image" class="h-3.5 w-3.5"></i>
                        </div>
                        <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="orFileName"></p>
                        <button
                            type="button"
                            class="shrink-0 text-[11px] font-medium text-slate-400 transition hover:text-slate-800"
                            x-on:click="clearOrFile()"
                        >Remove</button>
                    </div>
                    <label
                        x-show="!orFileName"
                        x-cloak
                        class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2.5 transition hover:border-slate-400 hover:bg-slate-50"
                    >
                        <input
                            type="file"
                            name="or_attachment"
                            accept=".pdf,.jpg,.jpeg,.png,image/*"
                            class="sr-only"
                            x-ref="orAttachment"
                            x-on:change="onOrPick($event)"
                        >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 bg-white text-slate-500 transition group-hover:border-slate-400 group-hover:text-slate-800">
                            <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-800">Upload Official Receipt</p>
                            <p class="truncate text-[10px] text-slate-500">One file only · settles the Disbursement Voucher</p>
                        </div>
                    </label>
                </div>
            @elseif($orAttachmentPath && $orAttachmentName)
                <div class="border-t border-slate-100 px-3.5 py-2.5">
                    <a
                        href="{{ asset('storage/'.$orAttachmentPath) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5 text-xs font-medium text-[#0025cc] transition hover:bg-slate-100"
                    >
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200/80">
                            <i data-lucide="file-image" class="h-3.5 w-3.5"></i>
                        </div>
                        <span class="min-w-0 truncate">{{ $orAttachmentName }}</span>
                    </a>
                </div>
            @else
                <p class="border-t border-slate-100 px-3.5 py-2.5 text-sm text-slate-400">No OR attachment</p>
            @endif
        </div>
    </div>
    @endif

    {{-- Per-item equipment photos (aligned with items table row indices) --}}
    @if($showItemPhotos)
    <div
        data-rr-item-photos
        class="mt-5 rounded border border-black/20 bg-slate-50/80 p-3 text-[12px] print:hidden"
    >
        <div class="mb-2 flex items-baseline justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Item photos</p>
            <span class="text-[10px] text-slate-400">1 file per slot · JPG, PNG, WEBP · max 5MB</span>
        </div>
        <p class="mb-2 text-[10px] leading-snug text-slate-400">Optional. Each slot accepts one photo only — remove to replace. Slots follow the items table below.</p>
        <div data-rr-photo-list class="space-y-2">
            @for($i = 0; $i < $rrRowCount; $i++)
                @include('partials.receiving-report-item-photo-slot', ['i' => $i, 'row' => $oldItems[$i] ?? $rows[$i] ?? null, 'editable' => $editable])
            @endfor
        </div>
        @if($editable)
            <template data-rr-photo-template>
                @include('partials.receiving-report-item-photo-slot', ['i' => '__INDEX__', 'row' => null, 'editable' => true])
            </template>
            <p data-rr-photo-empty class="hidden text-sm text-slate-400">Add an article in the items table to attach a photo.</p>
        @endif
        <p data-rr-photo-none class="hidden text-sm text-slate-400">No item photos</p>
    </div>
    @endif

    <p class="mt-6">Received the following items:</p>

    @if($editable)
    <div data-doc-rows data-doc-rows-name="items" data-doc-rows-min="1" data-doc-rows-max="{{ $rrMaxRows }}" data-doc-rows-default="{{ $rrDefaultRows }}" class="mt-2">
    @endif
    <table class="{{ $editable ? '' : 'mt-2' }} w-full border-collapse border border-black text-center">
        <thead>
            <tr>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]" title="From purchase authority — not editable">ORDERED</th>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">RECEIVED @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">DAMAGED</th>
                <th class="w-20 border border-black py-1 font-semibold">UNIT @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black py-1 font-semibold">ARTICLE @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="w-24 border border-black py-1 font-semibold text-[10px]">CONDITION</th>
                @if($allowMultiSupplier && $editable)
                    <th class="w-36 border border-black py-1 font-semibold">SUPPLIER</th>
                    <th class="w-20 border border-black py-1 font-semibold text-[10px]">UNIT PRICE</th>
                @endif
            </tr>
        </thead>
        <tbody @if($editable) data-doc-rows-body @endif>
            @for($i = 0; $i < $rrRowCount; $i++)
                @include('partials.receiving-report-item-row', ['i' => $i, 'row' => $oldItems[$i] ?? $rows[$i] ?? null])
            @endfor
        </tbody>
    </table>
    @if($editable)
        <template data-doc-rows-template>
            @include('partials.receiving-report-item-row', ['i' => '__INDEX__', 'row' => null])
        </template>
        @include('partials.doc-rows-editor', ['count' => $rrRowCount, 'max' => $rrMaxRows, 'min' => 1])
    </div>
    @endif

    <div class="mt-12 grid grid-cols-2 gap-16">
        <div class="text-left">
            <div class="font-semibold">Second Count: @if($signSecondCount)<span class="text-red-500">*</span>@endif</div>
            @if($signSecondCount)
                <div class="relative mt-6 w-56">
                    <span class="signature-name-stack w-full">
                        <img
                            id="scSigOverlay-{{ $signSuffix }}"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            name="second_count_by"
                            id="scName-{{ $signSuffix }}"
                            value="{{ old('second_count_by', $officerName) }}"
                            required
                            maxlength="255"
                            autocomplete="off"
                            class="relative z-[1] block w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm font-medium outline-none"
                            title="Receiving Officer name for Second Count"
                        >
                    </span>
                </div>
                <div class="mt-3 text-xs font-semibold">Date:</div>
                <div class="mt-1 w-40 border-b border-black pb-0.5 text-center text-sm">
                    {{ now()->format('d/m/Y') }}
                </div>
            @else
                <div class="relative mt-6 w-56 border-b border-black pb-1 min-h-[1.5rem]">
                    @include('partials.drawn-signature', [
                        'value' => $secondCount,
                        'printedName' => \App\Support\RisWorkflow::isDrawnSignature((string) $secondCount)
                            ? trim((string) ($rr->receiving_report_second_count_by ?? $officerName ?? ''))
                            : '',
                    ])
                </div>
            @endif
        </div>
        <div class="flex justify-end">
            <div class="w-56 text-left">
                <div class="font-semibold">Received by: @if($editable)<span class="text-red-500">*</span>@endif</div>
                @if($editable)
                    <div class="relative mt-6 w-full">
                        <span class="signature-name-stack w-full">
                            <img
                                id="purSigOverlay-{{ $signKey }}"
                                alt=""
                                class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                                style="display:none;"
                            >
                            <input
                                type="text"
                                name="receiving_report_received_by_name"
                                id="purSigName-{{ $signKey }}"
                                value="{{ $receivedByName }}"
                                maxlength="255"
                                autocomplete="off"
                                class="relative z-[1] block w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm outline-none"
                            >
                        </span>
                        <input
                            type="hidden"
                            name="receiving_report_received_by_signature"
                            id="purSigImage-{{ $signKey }}"
                            value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $receivedBySignature) ? $receivedBySignature : '' }}"
                        >
                    </div>
                @else
                    <div class="relative mt-6 w-full border-b border-black pb-1 min-h-[1.5rem]">
                        @include('partials.drawn-signature', [
                            'value' => $receivedBySignature ?: $legacyReceivedSig,
                            'printedName' => $receivedByName,
                            'empty' => $receivedByName,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .rr-print-sheet {
        min-height: 0 !important;
        height: auto !important;
    }

    .rr-address-textarea {
        line-height: 1.75rem;
        height: 3.5rem;
        overflow: hidden;
        background-image: repeating-linear-gradient(
            to bottom,
            transparent 0,
            transparent calc(1.75rem - 1px),
            #000 calc(1.75rem - 1px),
            #000 1.75rem
        );
        background-size: 100% 1.75rem;
        background-repeat: repeat-y;
        background-position: left top;
    }

    .rr-print-sheet input.rr-cell-invalid {
        background-color: #fef2f2;
        box-shadow: inset 0 0 0 1px #f87171;
    }

    @media print {
        .rr-print-sheet {
            min-height: 0 !important;
            height: auto !important;
            box-shadow: none !important;
        }

        .rr-print-sheet .rr-or-card,
        .rr-print-sheet .print-hidden {
            display: none !important;
        }
    }
</style>

@pushOnce('scripts')
<script>
(function () {
    var pendingPhotoRemoveIndex = null;

    function num(row, field) {
        var input = row.querySelector('input[name$="[' + field + ']"]');
        if (!input || String(input.value).trim() === '') return null;
        var value = parseInt(input.value, 10);
        return isNaN(value) ? null : Math.max(0, value);
    }

    function refreshRow(row) {
        var hidden = row.querySelector('[data-rr-condition-input]');
        var label = row.querySelector('[data-rr-condition-label]');
        if (!hidden || !label) return;

        var original = hidden.getAttribute('data-rr-condition-original') || 'ok';
        var ordered = num(row, 'ordered_qty');
        var received = num(row, 'quantity');
        var damaged = num(row, 'damaged_qty') || 0;
        var article = row.querySelector('input[name$="[article]"]');
        var hasData = received !== null || (article && article.value.trim() !== '');

        var missing = 0;
        var isMissing;
        if (ordered !== null && received !== null) {
            missing = Math.max(0, ordered - received);
            isMissing = missing > 0;
        } else {
            isMissing = original === 'short';
        }

        var parts = [];
        if (original === 'bad_order' && damaged === 0) parts.push('Bad Order');
        if (isMissing) parts.push(missing > 0 ? 'Missing ' + missing : 'Missing');
        if (damaged > 0) parts.push('Damaged ' + damaged);

        hidden.value = isMissing ? 'short' : (original === 'bad_order' ? 'bad_order' : 'ok');

        var problem = null;
        var problemField = null;
        var rowHasData = hasData || ordered !== null || damaged > 0;
        if (rowHasData) {
            if (ordered === null) {
                problem = 'Ordered qty missing';
                problemField = 'ordered_qty';
            } else if (received === null) {
                problem = 'Enter received';
                problemField = 'quantity';
            } else if (received > ordered) {
                problem = 'Over ordered by ' + (received - ordered);
                problemField = 'quantity';
            } else if (damaged > received) {
                problem = 'Damaged > received';
                problemField = 'damaged_qty';
            } else if (!article || article.value.trim() === '') {
                problem = 'Enter article';
                problemField = 'article';
            }
        }

        ['ordered_qty', 'quantity', 'damaged_qty', 'article'].forEach(function (field) {
            var input = row.querySelector('input[name$="[' + field + ']"]');
            if (input) input.classList.toggle('rr-cell-invalid', field === problemField);
        });

        var text = problem || (parts.length ? parts.join(' · ') : (hasData ? 'OK' : '—'));
        label.textContent = text;
        label.title = problem ? 'Fix this before submitting' : 'Worked out from Ordered, Received and Damaged';
        var flagged = parts.length > 0 && !problem;
        label.classList.toggle('font-semibold', flagged || !!problem);
        label.classList.toggle('text-amber-700', flagged);
        label.classList.toggle('text-red-600', !!problem);
        label.classList.toggle('text-slate-500', !flagged && !problem);
    }

    function paperRoot(el) {
        return el && el.closest ? el.closest('.rr-print-sheet') : null;
    }

    function photoSlots(list) {
        return Array.prototype.filter.call(list.children, function (el) {
            return el.hasAttribute && el.hasAttribute('data-rr-photo-slot');
        });
    }

    function rowLabel(row, index) {
        var articleInput = row.querySelector('input[name$="[article]"]');
        var article = articleInput ? String(articleInput.value || '').trim() : '';
        if (!article) {
            var articleCell = row.querySelector('td.text-left');
            if (articleCell) {
                var text = (articleCell.childNodes[0] && articleCell.childNodes[0].textContent)
                    ? String(articleCell.childNodes[0].textContent).trim()
                    : String(articleCell.textContent || '').trim().split('\n')[0].trim();
                article = text;
            }
        }
        var ordered = num(row, 'ordered_qty');
        var received = num(row, 'quantity');
        if (ordered === null) {
            var orderedDisplay = row.querySelector('[data-rr-ordered-display]');
            if (orderedDisplay) {
                var oText = String(orderedDisplay.textContent || '').trim();
                if (oText && oText !== '—') ordered = parseInt(oText, 10);
                if (isNaN(ordered)) ordered = null;
            }
        }
        var parts = [article || ('Item ' + (index + 1))];
        if (ordered !== null) parts.push('ordered ' + ordered);
        if (received !== null) parts.push('received ' + received);
        return { article: article, label: parts.join(' · ') };
    }

    function slotHasExistingPhoto(slot) {
        if (slot.querySelector('img[src*="storage/"]')) {
            return true;
        }
        var files = slot.querySelectorAll('input[type="file"]');
        for (var i = 0; i < files.length; i++) {
            if (files[i].files && files[i].files.length > 0) {
                return true;
            }
        }
        return false;
    }

    function unitSlots(list) {
        return Array.prototype.filter.call(list.children, function (el) {
            return el.hasAttribute && el.hasAttribute('data-rr-unit-slot');
        });
    }

    function renumberPhotoSlot(slot, index) {
        slot.setAttribute('data-rr-photo-index', String(index));
        slot.querySelectorAll('[name]').forEach(function (el) {
            var name = el.getAttribute('name');
            if (!name) return;
            if (/^items\[(?:\d+|__INDEX__)\]/.test(name)) {
                el.setAttribute('name', name.replace(/^items\[(?:\d+|__INDEX__)\]/, 'items[' + index + ']'));
            }
        });
    }

    function syncUnitSlotsForLine(slot, received) {
        var section = slot.querySelector('[data-rr-unit-photos]');
        if (!section) return;
        var list = section.querySelector('[data-rr-unit-list]');
        var template = section.querySelector('template[data-rr-unit-template]');
        var empty = section.querySelector('[data-rr-unit-empty]');
        var countLabel = section.querySelector('[data-rr-unit-count-label]');
        if (!list) return;

        var n = Math.max(0, Math.min(50, received == null ? 0 : received));
        var lineIndex = slot.getAttribute('data-rr-photo-index') || '0';
        var units = unitSlots(list);

        while (template && units.length < n) {
            var next = units.length;
            var html = template.innerHTML
                .replace(/__UNIT_LABEL__/g, String(next + 1))
                .replace(/__UNIT__/g, String(next));
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var clone = wrap.firstElementChild;
            if (!clone) break;
            list.appendChild(clone);
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(clone);
            }
            units.push(clone);
        }

        while (units.length > n) {
            var last = units.pop();
            if (last) last.remove();
        }

        units.forEach(function (unitEl, u) {
            unitEl.setAttribute('data-rr-unit-index', String(u));
            unitEl.classList.remove('hidden');
            unitEl.removeAttribute('hidden');
            var titleEl = unitEl.querySelector('[data-rr-unit-title]');
            if (!titleEl) {
                titleEl = unitEl.querySelector('p');
            }
            if (titleEl) titleEl.textContent = 'Unit ' + (u + 1);
            unitEl.querySelectorAll('[name]').forEach(function (el) {
                var name = el.getAttribute('name');
                if (!name) return;
                name = name.replace(/^items\[(?:\d+|__INDEX__)\]/, 'items[' + lineIndex + ']');
                if (/\[unit_photos\]\[(?:\d+|__UNIT__)\]/.test(name)) {
                    name = name.replace(/\[unit_photos\]\[(?:\d+|__UNIT__)\]/, '[unit_photos][' + u + ']');
                }
                if (/\[clear_unit_photos\]\[(?:\d+|__UNIT__)\]/.test(name)) {
                    name = name.replace(/\[clear_unit_photos\]\[(?:\d+|__UNIT__)\]/, '[clear_unit_photos][' + u + ']');
                    el.value = String(u);
                }
                el.setAttribute('name', name);
            });
        });

        section.classList.toggle('hidden', n <= 0);
        if (n > 0) section.removeAttribute('hidden');
        else section.setAttribute('hidden', '');
        if (countLabel) {
            countLabel.textContent = n > 0
                ? (n + ' slot' + (n === 1 ? '' : 's') + ' from received qty')
                : '';
        }
        if (empty) empty.classList.toggle('hidden', n > 0);
    }

    function updatePhotoSlotVisibility(slot, article, keepVisible) {
        var show = keepVisible || !!(article && article.trim());
        slot.classList.toggle('hidden', !show);
        if (show) slot.removeAttribute('hidden');
        else slot.setAttribute('hidden', '');
    }

    function syncPhotoEmptyState(photosRoot, visibleCount) {
        if (!photosRoot) return;
        var empty = photosRoot.querySelector('[data-rr-photo-empty]');
        var none = photosRoot.querySelector('[data-rr-photo-none]');
        var editable = !!photosRoot.querySelector('template[data-rr-photo-template]');
        if (empty) empty.classList.toggle('hidden', !editable || visibleCount > 0);
        if (none) none.classList.toggle('hidden', editable || visibleCount > 0);
    }

    function syncItemPhotoSlots(paper, removedIndex) {
        if (!paper) return;
        var photosRoot = paper.querySelector('[data-rr-item-photos]');
        if (!photosRoot) return;
        var list = photosRoot.querySelector('[data-rr-photo-list]');
        var template = photosRoot.querySelector('template[data-rr-photo-template]');
        var rowsRoot = paper.querySelector('[data-doc-rows]');
        var tableRows = rowsRoot && window.docRows
            ? window.docRows.rows(rowsRoot)
            : Array.prototype.slice.call(paper.querySelectorAll('[data-rr-row]'));
        var slots = photoSlots(list);

        if (typeof removedIndex === 'number' && removedIndex >= 0 && slots[removedIndex]) {
            slots[removedIndex].remove();
            slots = photoSlots(list);
        }

        while (template && slots.length < tableRows.length) {
            var clone = template.content.firstElementChild.cloneNode(true);
            list.appendChild(clone);
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(clone);
            }
            slots.push(clone);
        }
        while (slots.length > tableRows.length) {
            var last = slots.pop();
            if (last) last.remove();
        }

        var visibleCount = 0;
        slots.forEach(function (slot, index) {
            renumberPhotoSlot(slot, index);
            var row = tableRows[index];
            var info = row ? rowLabel(row, index) : { article: '', label: 'Item ' + (index + 1) };
            var labelEl = slot.querySelector('[data-rr-photo-label]');
            if (labelEl) labelEl.textContent = info.label;
            var keep = slotHasExistingPhoto(slot);
            updatePhotoSlotVisibility(slot, info.article, keep);
            var received = row ? num(row, 'quantity') : null;
            syncUnitSlotsForLine(slot, received);
            if (!slot.classList.contains('hidden')) visibleCount += 1;
        });

        syncPhotoEmptyState(photosRoot, visibleCount);
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    var ORDERED_INPUT_CLASS = 'h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]';

    function orderedCellFrom(el) {
        return el && el.closest ? el.closest('[data-rr-ordered-cell], td') : null;
    }

    window.rrLockOrderedCell = function (orderedInput) {
        if (!orderedInput) return;
        var td = orderedCellFrom(orderedInput);
        if (!td) return;
        var val = String(orderedInput.value ?? '').trim();
        if (val === '') {
            window.rrUnlockOrderedCell(orderedInput);
            return;
        }
        if (orderedInput.type === 'hidden') {
            var display = td.querySelector('[data-rr-ordered-display]');
            if (display) display.textContent = val;
            else {
                var span = document.createElement('span');
                span.setAttribute('data-rr-ordered-display', '');
                span.className = 'block text-center text-[11px] text-slate-700';
                span.textContent = val;
                td.appendChild(span);
            }
            td.classList.add('bg-slate-50/80');
            td.title = 'Ordered qty is locked from the purchase authority';
            return;
        }
        var name = orderedInput.getAttribute('name');
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name;
        hidden.value = val;
        hidden.setAttribute('data-rr-ordered-input', '');
        orderedInput.replaceWith(hidden);
        var existing = td.querySelector('[data-rr-ordered-display]');
        if (existing) {
            existing.textContent = val;
        } else {
            var label = document.createElement('span');
            label.setAttribute('data-rr-ordered-display', '');
            label.className = 'block text-center text-[11px] text-slate-700';
            label.textContent = val;
            td.appendChild(label);
        }
        td.classList.add('bg-slate-50/80');
        td.title = 'Ordered qty is locked from the purchase authority';
    };

    window.rrUnlockOrderedCell = function (orderedInput) {
        if (!orderedInput) return;
        var td = orderedCellFrom(orderedInput);
        if (!td) return;
        var name = orderedInput.getAttribute('name');
        if (orderedInput.type !== 'hidden') {
            orderedInput.value = '';
            td.classList.remove('bg-slate-50/80');
            td.removeAttribute('title');
            var displayOnly = td.querySelector('[data-rr-ordered-display]');
            if (displayOnly) displayOnly.remove();
            return;
        }
        var number = document.createElement('input');
        number.type = 'number';
        number.name = name;
        number.value = '';
        number.setAttribute('data-rr-ordered-input', '');
        number.setAttribute('min', '0');
        number.setAttribute('max', '9999999');
        number.setAttribute('step', '1');
        number.setAttribute('inputmode', 'numeric');
        number.className = ORDERED_INPUT_CLASS;
        number.onkeydown = function (event) {
            if (['e', 'E', '+', '-', '.'].includes(event.key)) event.preventDefault();
        };
        number.oninput = function () {
            if (this.value.length > 7) this.value = this.value.replace(/\D/g, '').slice(0, 7);
        };
        orderedInput.replaceWith(number);
        var display = td.querySelector('[data-rr-ordered-display]');
        if (display) display.remove();
        td.classList.remove('bg-slate-50/80');
        td.removeAttribute('title');
    };

    window.rrRefreshConditions = function (root) {
        var scope = root || document;
        scope.querySelectorAll('[data-rr-row]').forEach(refreshRow);
        if (root && root.querySelectorAll) {
            root.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                syncItemPhotoSlots(sheet);
            });
            if (root.classList && root.classList.contains('rr-print-sheet')) {
                syncItemPhotoSlots(root);
            } else {
                var sheet = paperRoot(root);
                if (sheet) syncItemPhotoSlots(sheet);
            }
        } else {
            document.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                syncItemPhotoSlots(sheet);
            });
        }
    };

    window.rrSyncItemPhotos = function (root) {
        if (!root) {
            document.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                syncItemPhotoSlots(sheet);
            });
            return;
        }
        var sheet = root.classList && root.classList.contains('rr-print-sheet') ? root : paperRoot(root);
        if (sheet) syncItemPhotoSlots(sheet);
    };

    document.addEventListener('input', function (event) {
        var row = event.target.closest && event.target.closest('[data-rr-row]');
        if (!row) return;
        refreshRow(row);
        syncItemPhotoSlots(paperRoot(row));
    });

    document.addEventListener('click', function (event) {
        var hit = event.target.closest && event.target.closest('[data-doc-row-remove]');
        if (!hit) return;
        var root = hit.closest('[data-doc-rows]');
        var row = hit.closest('[data-doc-row]');
        if (!root || !row || !paperRoot(root)) return;
        pendingPhotoRemoveIndex = window.docRows ? window.docRows.rows(root).indexOf(row) : -1;
    }, true);

    document.addEventListener('doc-rows:change', function (event) {
        var root = event.target;
        if (!root || !root.closest) return;
        var sheet = paperRoot(root);
        if (!sheet) return;
        syncItemPhotoSlots(sheet, pendingPhotoRemoveIndex);
        pendingPhotoRemoveIndex = null;
        root.querySelectorAll('[data-rr-row]').forEach(refreshRow);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.rrRefreshConditions(); });
    } else {
        window.rrRefreshConditions();
    }
})();
</script>
@endPushOnce
