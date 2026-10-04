@php
    $editable = $editable ?? false;
    $allowMultiSupplier = $allowMultiSupplier ?? false;
    $suppliers = $suppliers ?? collect();
    $signSecondCount = $signSecondCount ?? false;
    $rr = $rr ?? null;
    $rows = $rows ?? collect();
    $dateValue = old(
        'receiving_report_date',
        $rr?->receiving_report_date ?? ($editable && !$rr ? now()->format('Y-m-d') : '')
    );
    $fromValue = old('receiving_report_received_from', $rr?->receiving_report_received_from ?? '');
    $addressValue = old('receiving_report_supplier_address_override', $rr?->receiving_report_supplier_address_override ?? '');
    $invoiceValue = old('receiving_report_invoice_no', $rr?->receiving_report_invoice_no ?? '');
    $drValue = old('receiving_report_dr_no', $rr?->receiving_report_dr_no ?? '');
    $deliveryValue = old('receiving_report_delivery_date', $rr?->receiving_report_delivery_date ?? '');
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
    class="rr-print-sheet mx-auto w-full max-w-[1095px] bg-white px-10 pb-5 pt-8 text-[13px] text-black shadow {{ $printClass ?? '' }}"
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
                    title="Assigned on submit (RR-YYYYMM-0000001)"
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
                    <input type="text" name="receiving_report_received_from" value="{{ $fromValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $fromValue }}</span>
                @endif
            </div>
            @php
                $addressRaw = trim((string) $addressValue);
                if (preg_match('/\R/', $addressRaw)) {
                    $addressParts = preg_split('/\R/', $addressRaw, 2);
                    $addressLine1 = trim((string) ($addressParts[0] ?? ''));
                    $addressLine2 = trim((string) ($addressParts[1] ?? ''));
                } elseif (mb_strlen($addressRaw) > 48) {
                    $cut = mb_strrpos(mb_substr($addressRaw, 0, 48), ' ');
                    $cut = $cut === false ? 48 : $cut;
                    $addressLine1 = trim(mb_substr($addressRaw, 0, $cut));
                    $addressLine2 = trim(mb_substr($addressRaw, $cut));
                } else {
                    $addressLine1 = $addressRaw;
                    $addressLine2 = '';
                }
            @endphp
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
                        <div class="min-h-[1.75rem] border-b border-black pb-0.5 leading-[1.75rem]">{{ $addressLine1 }}</div>
                        <div class="mt-1 min-h-[1.75rem] border-b border-black pb-0.5 leading-[1.75rem]">{{ $addressLine2 }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="shrink-0">Refer Invoice No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_invoice_no" value="{{ $invoiceValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $invoiceValue }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="shrink-0">D.R. No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_dr_no" value="{{ $drValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
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

    <p class="mt-6">Received the following items:</p>

    @if($editable)
    <div data-doc-rows data-doc-rows-name="items" data-doc-rows-min="1" data-doc-rows-max="{{ $rrMaxRows }}" data-doc-rows-default="{{ $rrDefaultRows }}" class="mt-2">
    @endif
    <table class="{{ $editable ? '' : 'mt-2' }} w-full border-collapse border border-black text-center">
        <thead>
            <tr>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">ORDERED @if($editable)<span class="text-red-500">*</span>@endif</th>
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
                <div class="mt-10 w-56 border-b border-black pb-1 min-h-[1.5rem]">
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
    }
</style>

@pushOnce('scripts')
<script>
(function () {
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
                problem = 'Enter ordered';
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

    window.rrRefreshConditions = function (root) {
        (root || document).querySelectorAll('[data-rr-row]').forEach(refreshRow);
    };

    document.addEventListener('input', function (event) {
        var row = event.target.closest && event.target.closest('[data-rr-row]');
        if (row) refreshRow(row);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.rrRefreshConditions(); });
    } else {
        window.rrRefreshConditions();
    }
})();
</script>
@endPushOnce
