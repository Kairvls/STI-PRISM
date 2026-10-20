@php
    $editable = $editable ?? false;
    $dv = $dv ?? null;
    $printClass = $printClass ?? '';
    $printId = $printId ?? null;
    $useOld = $useOld ?? (
        $editable
        && (int) old('_dv_id', 0) > 0
        && (int) old('_dv_id') === (int) ($dv->dv_id ?? 0)
    );
    $val = fn ($key, $fallback = '') => $useOld ? old($key, $fallback) : $fallback;

    $payee = $val('dv_payee', $dv?->dv_payee ?? '');
    $dateValue = $val('dv_date', $dv?->dv_date ?? ($editable ? now()->format('Y-m-d') : ''));
    $particulars = $val('dv_particulars', $dv?->dv_particulars ?? ($dv?->dv_remarks ?? ''));
    $amountRaw = $val('dv_amount', $dv?->dv_amount ?? '');
    $amount = is_string($amountRaw)
        ? str_replace(',', '', $amountRaw)
        : $amountRaw;
    $orAmountRaw = $dv?->dv_or_amount ?? null;
    $changeAmountRaw = $dv?->dv_change_amount ?? null;
    $hasOrSettlement = $orAmountRaw !== null && $orAmountRaw !== '';
    $preparedBy = $val('dv_prepared_by_name', $dv?->dv_prepared_by_name ?? '');
    $checkedBy = $val('dv_checked_by_name', $dv?->dv_checked_by_name ?? '');
    $cdvNo = $val('dv_cdv_number', $dv?->dv_cdv_number ?? ($dv?->dv_form_number ?? ''));
    $checkNo = $val('dv_check_number', $dv?->dv_check_number ?? '');
    $receivedBy = $val('dv_received_by_name', $dv?->dv_received_by_name ?? '');
    $receivedDate = $val(
        'dv_received_date',
        $dv?->dv_received_date ?? ($editable ? now()->format('Y-m-d') : '')
    );

    $oldLines = $useOld ? old('account_lines') : null;
    if (is_array($oldLines)) {
        $accountLines = array_values($oldLines);
    } else {
        $decoded = [];
        if (! empty($dv?->dv_account_lines)) {
            $decoded = is_string($dv->dv_account_lines)
                ? (json_decode($dv->dv_account_lines, true) ?: [])
                : (array) $dv->dv_account_lines;
        }
        $accountLines = array_values($decoded);
    }

    // Drop trailing blank lined rows so the lower area stays open for free writing.
    while (count($accountLines) > 2) {
        $last = $accountLines[count($accountLines) - 1] ?? [];
        $isBlank = trim((string) ($last['account'] ?? '')) === ''
            && ($last['debit'] ?? null) !== 0
            && ($last['debit'] ?? null) !== '0'
            && trim((string) ($last['debit'] ?? '')) === ''
            && ($last['credit'] ?? null) !== 0
            && ($last['credit'] ?? null) !== '0'
            && trim((string) ($last['credit'] ?? '')) === '';
        if (! $isBlank) {
            break;
        }
        array_pop($accountLines);
    }

    if ($accountLines === [] && $editable) {
        $defaultAmount = $amount !== '' && $amount !== null ? (float) $amount : null;
        $accountLines = [
            ['account' => '', 'debit' => $defaultAmount, 'credit' => null],
            ['account' => 'Cash In Bank -PSB', 'debit' => null, 'credit' => $defaultAmount],
        ];
    }

    // Keep at least two entry rows when editing.
    if ($editable) {
        while (count($accountLines) < 2) {
            $accountLines[] = ['account' => '', 'debit' => null, 'credit' => null];
        }
    }

    $fmt = fn ($n) => ($n === null || $n === '') ? '' : number_format((float) $n, 2);
    $acctFreeText = collect($accountLines)
        ->map(fn ($line) => (string) ($line['account'] ?? ''))
        ->implode("\n");
    $amtFreeText = collect($accountLines)
        ->map(function ($line) use ($fmt) {
            $debit = $line['debit'] ?? null;
            $credit = $line['credit'] ?? null;
            if ($debit !== null && $debit !== '') {
                return $fmt($debit);
            }
            if ($credit !== null && $credit !== '') {
                return $fmt($credit);
            }

            return '';
        })
        ->implode("\n");

    $inp = 'dv-field';
    $dateDisplay = $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d-M-y') : '';
    $adminName = trim((string) ($dv?->dv_admin_signed_name ?? ''));
    $accountantDefault = trim((string) ($dv?->dv_accountant_signed_name ?? ''));
    if ($accountantDefault === '' && $editable) {
        $accountantDefault = preg_replace('/[^A-Za-z0-9 ]/', '', trim((string) (auth()->user()->user_full_name ?? ''))) ?? '';
        $accountantDefault = substr($accountantDefault, 0, 26);
    }
    $accountantName = $val('dv_accountant_signed_name', $accountantDefault);
    $accountantSignature = $val(
        'dv_accountant_signature',
        $dv?->dv_accountant_signature ?? ''
    );
    $signKey = $signKey ?? ($dv?->dv_id ? 'dv-'.$dv->dv_id : 'dv-create');
@endphp

<style>
    .dv-print-sheet {
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }
    .dv-print-sheet,
    .dv-print-sheet * {
        box-sizing: border-box;
    }
    .dv-print-sheet .dv-title {
        font-weight: 700;
        font-size: 18px;
        letter-spacing: 0.12em;
        margin-top: 6px;
        text-transform: uppercase;
    }
    .dv-print-sheet .dv-org {
        font-weight: 700;
        font-size: 15px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
    .dv-print-sheet .dv-addr {
        font-size: 12px;
        margin-top: 1px;
    }
    .dv-print-sheet .dv-line-input {
        border: 0;
        border-bottom: 1px solid #000;
        background: transparent;
        outline: none;
        font: inherit;
        color: #000;
        padding: 0 4px 1px;
        width: 100%;
        min-width: 0;
    }
    .dv-print-sheet .dv-line-input:focus {
        outline: none;
        box-shadow: none;
    }
    .dv-print-sheet .dv-field {
        border: 0;
        background: transparent;
        outline: none;
        font: inherit;
        color: #000;
        width: 100%;
        min-width: 0;
        padding: 2px 4px;
        resize: none;
    }
    .dv-print-sheet .dv-field:focus {
        outline: none;
        box-shadow: none;
    }
    .dv-print-sheet .dv-particulars-pad {
        padding: 6px 6px 8px;
    }
    .dv-print-sheet .dv-particulars {
        box-sizing: border-box;
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        margin: 0;
        padding: 0 !important;
        border: 0;
        outline: none;
        font: inherit;
        font-size: 12px;
        line-height: 18px;
        height: 90px; /* exactly 5 × 18px */
        min-height: 90px;
        max-height: 90px;
        overflow: hidden;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
        scrollbar-width: none;
        -webkit-user-modify: read-write-plaintext-only;
    }
    .dv-print-sheet .dv-particulars:empty:before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }
    .dv-print-sheet .dv-particulars-view {
        box-sizing: border-box;
        width: 100%;
        font: inherit;
        font-size: 12px;
        line-height: 18px;
        max-height: 90px;
        overflow: hidden;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .dv-print-sheet table.dv-grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        border: 1.5px solid #000;
    }
    .dv-print-sheet table.dv-grid > tbody > tr > td,
    .dv-print-sheet table.dv-grid > thead > tr > th {
        border: 1px solid #000;
        vertical-align: top;
        padding: 0;
    }
    .dv-print-sheet .dv-th {
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        padding: 5px 6px;
        text-transform: uppercase;
    }
    .dv-print-sheet .dv-pad {
        padding: 8px 10px;
    }
    .dv-print-sheet .dv-sig-box {
        text-align: center;
        padding: 6px 8px 8px;
        min-height: 78px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .dv-print-sheet .dv-sig-label {
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .dv-print-sheet .dv-sig-space {
        flex: 1;
        min-height: 28px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding-bottom: 2px;
    }
    .dv-print-sheet .dv-sig-name {
        font-weight: 700;
        font-style: italic;
        font-size: 11px;
        line-height: 1.15;
    }
    .dv-print-sheet .dv-sig-role {
        font-style: italic;
        font-size: 10px;
        margin-top: 1px;
    }
    .dv-print-sheet .dv-tiny-label {
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        text-align: center;
        padding: 5px 6px 2px;
    }
    .dv-print-sheet .dv-acct-inner {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .dv-print-sheet .dv-acct-inner td,
    .dv-print-sheet .dv-acct-inner th {
        border: 0;
        padding: 3px 6px;
        font-size: 12px;
        vertical-align: top;
    }
    .dv-print-sheet .dv-acct-inner th {
        text-align: center;
        font-weight: 700;
        padding: 5px 6px;
        border-bottom: 1px solid #000;
    }
    .dv-print-sheet .dv-acct-inner .dv-acct-amt {
        border-left: 1px solid #000;
    }
    .dv-print-sheet .dv-acct-inner tr.dv-acct-body td {
        border-bottom: 0;
        height: 100%;
        min-height: 140px;
        padding: 0;
        position: relative;
        cursor: text;
        vertical-align: top;
    }
    .dv-print-sheet .dv-acct-pad {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 140px;
        padding: 6px 8px;
        box-sizing: border-box;
        cursor: text;
    }
    .dv-print-sheet .dv-acct-free {
        display: block;
        width: 100%;
        min-height: 128px;
        height: 100%;
        border: 0;
        outline: none;
        resize: none;
        background: transparent;
        font: inherit;
        color: #000;
        line-height: 22px;
        padding: 0;
        margin: 0;
        overflow: auto;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
        cursor: text;
        box-sizing: border-box;
    }
    .dv-print-sheet .dv-acct-free:focus {
        outline: none;
        box-shadow: none;
    }
    .dv-print-sheet .dv-acct-free-view {
        min-height: 128px;
        line-height: 22px;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .dv-print-sheet .dv-acct-free.is-amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .dv-print-sheet .dv-footer-stack {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
    }
    .dv-print-sheet .dv-footer-stack td {
        border: 0;
        border-bottom: 1px solid #000;
        vertical-align: top;
        height: 33.33%;
    }
    .dv-print-sheet .dv-footer-stack tr:last-child td {
        border-bottom: 0;
    }
</style>

<div
    @if($printId) id="{{ $printId }}" @endif
    class="dv-print-sheet mx-auto w-full px-6 py-5 text-[12px] leading-snug shadow {{ $printClass }}"
>
    {{-- HEADER --}}
    <div class="text-center">
        <div class="dv-org">STI COLLEGE-ORMOC, INC.</div>
        <div class="dv-addr">Centrum Mall Bldg., Aviles St., Ormoc City</div>
        <div class="dv-title">DISBURSEMENT VOUCHER</div>
        @php
            $dvFormNo = trim((string) ($dv?->dv_form_number ?? ''));
            $dvRequestNo = trim((string) ($dv?->dv_request_no ?? ''));
        @endphp
        @if($dvFormNo !== '' || $dvRequestNo !== '')
            <div class="mt-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[11px] font-semibold tracking-wide text-slate-700">
                @if($dvFormNo !== '')
                    <span>DV No. <span class="tabular-nums">{{ $dvFormNo }}</span></span>
                @endif
                @if($dvRequestNo !== '')
                    <span>Request No. <span class="tabular-nums">{{ $dvRequestNo }}</span></span>
                @endif
            </div>
        @endif
    </div>

    {{-- PAY TO / DATE --}}
    <div class="mt-5 mb-3 flex items-end gap-8">
        <div class="flex min-w-0 flex-1 items-end gap-2">
            <span class="shrink-0 text-[12px] font-bold">PAY TO:</span>
            @if($editable)
                <input type="text" name="dv_payee" value="{{ $payee }}" required maxlength="{{ \App\Support\InputLimits::NAME }}" class="dv-line-input flex-1 text-[13px] font-bold uppercase tracking-wide">
            @else
                <span class="dv-line-input flex-1 text-[13px] font-bold uppercase tracking-wide">{!! $payee !== '' ? e($payee) : '&nbsp;' !!}</span>
            @endif
        </div>
        <div class="flex w-[170px] shrink-0 items-end gap-2">
            <span class="shrink-0 text-[12px] font-bold">DATE:</span>
            @if($editable)
                <input type="date" name="dv_date" value="{{ $dateValue }}" required class="dv-line-input flex-1 text-[12px]">
            @else
                <span class="dv-line-input flex-1 text-[12px]">{!! $dateDisplay !== '' ? e($dateDisplay) : '&nbsp;' !!}</span>
            @endif
        </div>
    </div>

    {{-- MAIN GRID --}}
    <table class="dv-grid">
        <colgroup>
            <col style="width: 52%">
            <col style="width: 20%">
            <col style="width: 28%">
        </colgroup>

        {{-- Row 1: PARTICULARS | AMOUNT --}}
        <tr>
            <td colspan="2">
                <div class="dv-th" style="border-bottom: 1px solid #000;">PARTICULARS</div>
                <div class="dv-pad dv-particulars-pad" style="min-height: 108px;">
                    @if($editable)
                        <input type="hidden" name="dv_particulars" value="{{ $particulars }}" data-dv-particulars-value required>
                        <div
                            class="dv-particulars"
                            contenteditable="true"
                            role="textbox"
                            aria-multiline="true"
                            aria-label="Particulars"
                            data-dv-particulars
                            data-placeholder="Describe the particulars (up to 5 lines)"
                            title="Up to 5 lines of text"
                        >{{ $particulars }}</div>
                    @else
                        <div class="dv-particulars-view">{{ $particulars }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="dv-th" style="border-bottom: 1px solid #000;">AMOUNT</div>
                <div class="dv-pad text-right" style="min-height: 108px;">
                    @if($editable)
                        <input
                            type="text"
                            inputmode="decimal"
                            name="dv_amount"
                            value="{{ $amount !== '' && $amount !== null ? $fmt($amount) : '' }}"
                            required
                            maxlength="17"
                            class="{{ $inp }} text-right text-[16px] font-semibold tabular-nums"
                            style="padding: 2px;"
                            data-dv-amount-input
                            autocomplete="off"
                            title="Up to 10 digits before the decimal and 2 decimal places (max 9,999,999,999.99)"
                        >
                    @else
                        <div class="text-[16px] font-semibold tabular-nums" style="padding: 2px;">{{ $amount !== '' && $amount !== null ? $fmt($amount) : '' }}</div>
                    @endif
                </div>
            </td>
        </tr>

        {{-- Row 2: ACCOUNT CLASSIFICATION + AMOUNT | approval stack --}}
        <tr>
            <td colspan="2" style="padding: 0; vertical-align: stretch; height: 1px;">
                <table class="dv-acct-inner">
                    <colgroup>
                        <col style="width: 72%">
                        <col style="width: 28%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ACCOUNT CLASSIFICATION</th>
                            <th class="dv-acct-amt">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="dv-acct-body">
                            <td data-dv-acct-click="account">
                                @if($editable)
                                    <div class="dv-acct-pad">
                                        <textarea
                                            class="dv-acct-free"
                                            rows="8"
                                            maxlength="{{ \App\Support\InputLimits::NOTES }}"
                                            data-dv-acct-free="account"
                                            placeholder="e.g. Postage, Telephone & Telegram&#10;Cash In Bank -PSB"
                                        >{{ $acctFreeText }}</textarea>
                                    </div>
                                @else
                                    <div class="dv-acct-pad">
                                        <div class="dv-acct-free-view">{{ $acctFreeText }}</div>
                                    </div>
                                @endif
                            </td>
                            <td class="dv-acct-amt" data-dv-acct-click="amount">
                                @if($editable)
                                    <div class="dv-acct-pad">
                                        <textarea
                                            class="dv-acct-free is-amount"
                                            rows="8"
                                            maxlength="{{ \App\Support\InputLimits::NOTES }}"
                                            data-dv-acct-free="amount"
                                            placeholder="0.00"
                                        >{{ $amtFreeText }}</textarea>
                                    </div>
                                @else
                                    <div class="dv-acct-pad">
                                        <div class="dv-acct-free-view" style="text-align:right; font-variant-numeric:tabular-nums;">{{ $amtFreeText }}</div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
                @if($editable)
                    <div class="hidden" data-dv-acct-lines-mount aria-hidden="true"></div>
                @endif
            </td>
            <td style="padding: 0;">
                <div style="border-bottom: 1px solid #000;">
                    <div class="dv-sig-box" style="min-height: 72px;">
                        <div class="dv-sig-label">APPROVED FOR PAYMENT</div>
                        <div class="dv-sig-space"></div>
                    </div>
                </div>
                <div style="border-bottom: 1px solid #000;">
                    <div class="dv-sig-box">
                        <div class="dv-sig-label">AUTHORIZED SIGNATURE</div>
                        <div class="dv-sig-space">
                            @if(!empty($dv?->dv_admin_signature) && str_starts_with((string) $dv->dv_admin_signature, 'data:image'))
                                <img src="{{ $dv->dv_admin_signature }}" alt="" style="max-height: 36px; object-fit: contain;">
                            @endif
                        </div>
                        <div class="dv-sig-name">{!! $adminName !== '' ? e($adminName) : '&nbsp;' !!}</div>
                        <div class="dv-sig-role">School Administrator</div>
                    </div>
                </div>
                <div>
                    <div class="dv-sig-box">
                        <div class="dv-sig-label">AUTHORIZED SIGNATURE</div>
                        @if($editable)
                            <div class="dv-sig-space relative">
                                <span class="signature-name-stack relative mx-auto block w-full max-w-[92%]">
                                    <img
                                        id="purSigOverlay-{{ $signKey }}"
                                        alt=""
                                        class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[36px] w-auto max-w-full -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                                        style="display:none;"
                                    >
                                    <input
                                        type="text"
                                        name="dv_accountant_signed_name"
                                        id="purSigName-{{ $signKey }}"
                                        value="{{ $accountantName }}"
                                        maxlength="26"
                                        autocomplete="off"
                                        class="relative z-[1] dv-field text-center text-[11px] font-bold italic"
                                        placeholder="Type accountant name"
                                        title="Up to 26 letters/digits"
                                        data-dv-max26
                                    >
                                </span>
                            </div>
                            <input
                                type="hidden"
                                name="dv_accountant_signature"
                                id="purSigImage-{{ $signKey }}"
                                value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $accountantSignature) ? $accountantSignature : '' }}"
                            >
                            <div class="dv-sig-role">School Accountant</div>
                        @else
                            <div class="dv-sig-space">
                                @if(\App\Support\RisWorkflow::isDrawnSignature((string) $accountantSignature))
                                    <img src="{{ $accountantSignature }}" alt="" style="max-height: 36px; object-fit: contain;">
                                @endif
                            </div>
                            <div class="dv-sig-name">{!! $accountantName !== '' ? e($accountantName) : '&nbsp;' !!}</div>
                            <div class="dv-sig-role">School Accountant</div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>

        {{-- Row 3: RECEIPT | PREPARED / CHECKED / CDV --}}
        <tr>
            <td colspan="2" class="dv-pad" style="min-height: 168px;">
                <div class="mx-auto w-fit text-left">
                    <div class="text-[12px]">Received from STI COLLEGE-ORMOC, INC</div>
                    <div class="mt-3 flex items-end gap-2">
                        <span class="shrink-0 text-[12px]">CHECK NO.</span>
                        @if($editable)
                            <input
                                type="text"
                                name="dv_check_number"
                                value="{{ $checkNo }}"
                                class="dv-line-input"
                                style="width: 280px;"
                                maxlength="16"
                                inputmode="numeric"
                                pattern="[0-9]{0,16}"
                                autocomplete="off"
                                title="Up to 16 digits"
                                data-dv-check-no
                            >
                        @else
                            <span class="dv-line-input" style="display: inline-block; width: 280px;">{!! $checkNo !== '' ? e($checkNo) : '&nbsp;' !!}</span>
                        @endif
                    </div>
                    <div class="mt-2 text-[12px]">in full/partial payment of particulars described above.</div>

                    @if($hasOrSettlement)
                        <div class="mt-4 rounded border border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] leading-relaxed text-slate-700">
                            <div class="font-semibold uppercase tracking-wide text-slate-500">Final from Official Receipt</div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                <span>OR amount: <strong class="tabular-nums">₱{{ number_format((float) $orAmountRaw, 2) }}</strong></span>
                                @if($changeAmountRaw !== null && $changeAmountRaw !== '' && (float) $changeAmountRaw > 0)
                                    <span>Change returned: <strong class="tabular-nums text-amber-800">₱{{ number_format((float) $changeAmountRaw, 2) }}</strong></span>
                                @endif
                            </div>
                            <div class="mt-0.5 text-[10px] text-slate-400">Synced from Receiving Report after OR upload — this is the final settled amount.</div>
                        </div>
                    @endif

                    <div class="mt-8 flex gap-2">
                        <span class="invisible shrink-0 select-none text-[12px]" aria-hidden="true">CHECK NO.</span>
                        <div class="flex flex-col items-center" style="width: 280px;">
                            @if($editable)
                                <input
                                    type="text"
                                    name="dv_received_by_name"
                                    value="{{ $receivedBy }}"
                                    class="dv-line-input w-full text-center"
                                    autocomplete="off"
                                    maxlength="26"
                                    title="Up to 26 letters/digits"
                                    data-dv-max26
                                >
                            @else
                                <span class="dv-line-input block w-full text-center">{!! $receivedBy !== '' ? e($receivedBy) : '&nbsp;' !!}</span>
                            @endif
                            <div class="mt-0.5 text-center text-[10px]">Signature Over Printed Name</div>

                            <div class="mt-5 flex w-full flex-col items-start">
                                @if($editable)
                                    <input type="date" name="dv_received_date" value="{{ $receivedDate }}" class="dv-line-input text-center" style="width: 140px;">
                                @else
                                    <span class="dv-line-input text-center" style="width: 140px; display: inline-block;">
                                        {!! $receivedDate ? e(\Carbon\Carbon::parse($receivedDate)->format('M d, Y')) : '&nbsp;' !!}
                                    </span>
                                @endif
                                <div class="mt-0.5 w-[140px] text-center text-[10px]">Date</div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
            <td style="padding: 0; vertical-align: top;">
                <table class="dv-footer-stack">
                    <tr>
                        <td>
                            <div class="dv-tiny-label">PREPARED BY</div>
                            <div style="min-height: 42px; padding: 2px 6px; text-align: center;">
                                @if($editable)
                                    <input type="text" name="dv_prepared_by_name" value="{{ $preparedBy }}" class="{{ $inp }} text-center" autocomplete="off" maxlength="26" title="Up to 26 letters/digits" data-dv-max26>
                                @else
                                    <div class="text-[12px]">{{ $preparedBy }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="dv-tiny-label">CHECKED BY:</div>
                            <div style="min-height: 42px; padding: 2px 6px; text-align: center;">
                                @if($editable)
                                    <input type="text" name="dv_checked_by_name" value="{{ $checkedBy }}" class="{{ $inp }} text-center" autocomplete="off" maxlength="26" title="Up to 26 letters/digits" data-dv-max26>
                                @else
                                    <div class="text-[12px]">{{ $checkedBy }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="dv-tiny-label">CDV No.</div>
                            <div style="min-height: 42px; padding: 2px 6px; text-align: center;">
                                @if($editable)
                                    <input type="text" name="dv_cdv_number" value="{{ $cdvNo }}" class="{{ $inp }} text-center" autocomplete="off" maxlength="26" title="Up to 26 letters/digits" data-dv-max26>
                                @else
                                    <div class="text-[12px] font-semibold">{{ $cdvNo }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</div>

@if($editable)
<script>
(function () {
    if (window.__dvAmountFormatBound) return;
    window.__dvAmountFormatBound = true;

    // Safe AMOUNT limit: up to 10 digits before decimal + 2 decimal places.
    var MAX_WHOLE_DIGITS = 10;
    var MAX_DECIMAL_DIGITS = 2;
    var MAX_AMOUNT = 9999999999.99;

    function sanitizeAmount(raw) {
        var cleaned = String(raw || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
        if (cleaned === '') return { whole: '', decimals: null, hasDot: false };
        var parts = cleaned.split('.');
        var whole = (parts[0] || '').replace(/^0+(?=\d)/, '');
        if (whole.length > MAX_WHOLE_DIGITS) whole = whole.slice(0, MAX_WHOLE_DIGITS);
        var hasDot = parts.length > 1;
        var decimals = hasDot ? parts.slice(1).join('').slice(0, MAX_DECIMAL_DIGITS) : null;
        return { whole: whole, decimals: decimals, hasDot: hasDot };
    }

    function formatWhileTyping(raw) {
        var parts = sanitizeAmount(raw);
        if (parts.whole === '' && !parts.hasDot) return '';
        var whole = parts.whole === '' ? '0' : parts.whole;
        var withCommas = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.hasDot ? withCommas + '.' + (parts.decimals || '') : withCommas;
    }

    function formatOnBlur(raw) {
        var parts = sanitizeAmount(raw);
        if (parts.whole === '' && !parts.hasDot) return '';
        var cleaned = (parts.whole || '0') + (parts.hasDot ? '.' + (parts.decimals || '0') : '');
        var num = Number(cleaned);
        if (!isFinite(num)) return '';
        if (num > MAX_AMOUNT) num = MAX_AMOUNT;
        return num.toLocaleString('en-US', {
            minimumFractionDigits: MAX_DECIMAL_DIGITS,
            maximumFractionDigits: MAX_DECIMAL_DIGITS
        });
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || !el.matches || !el.matches('[data-dv-amount-input]')) return;
        var start = el.selectionStart;
        var before = el.value;
        var next = formatWhileTyping(before);
        if (next === before) return;
        el.value = next;
        if (typeof start === 'number') {
            var pos = Math.max(0, start + (next.length - before.length));
            try { el.setSelectionRange(pos, pos); } catch (err) {}
        }
    });

    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el || !el.matches || !el.matches('[data-dv-amount-input]')) return;
        el.value = formatOnBlur(el.value);
    }, true);
})();

(function () {
    if (window.__dvParticularsBound) return;
    window.__dvParticularsBound = true;

    var MAX_LINES = 5;
    var LINE_HEIGHT = 18;
    var MAX_HEIGHT = LINE_HEIGHT * MAX_LINES;

    function getText(el) {
        // Preserve soft line breaks as \n for storage.
        var text = el.innerText || el.textContent || '';
        return text.replace(/\u00a0/g, ' ').replace(/\r\n?/g, '\n');
    }

    function setText(el, text) {
        el.textContent = text || '';
    }

    function syncHidden(el) {
        var form = el.closest('form');
        var hidden = form ? form.querySelector('[data-dv-particulars-value]') : null;
        if (!hidden && el.parentElement) {
            hidden = el.parentElement.querySelector('[data-dv-particulars-value]');
        }
        if (hidden) hidden.value = getText(el);
    }

    function fits(el) {
        return el.scrollHeight <= MAX_HEIGHT + 1;
    }

    function bindParticulars(el) {
        if (!el || el.dataset.dvParticularsReady === '1') return;
        el.dataset.dvParticularsReady = '1';
        el.style.height = MAX_HEIGHT + 'px';
        el._dvParticularsPrev = getText(el);
        syncHidden(el);

        if (!fits(el)) {
            var text = getText(el);
            while (text.length > 0 && !fits(el)) {
                text = text.slice(0, -1);
                setText(el, text);
            }
            el._dvParticularsPrev = getText(el);
            syncHidden(el);
        }

        el.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var hardLines = getText(el).split('\n').length;
            if (hardLines >= MAX_LINES) {
                e.preventDefault();
            }
        });

        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var paste = (e.clipboardData || window.clipboardData).getData('text') || '';
            paste = paste.replace(/\r\n?/g, '\n');
            document.execCommand('insertText', false, paste);
        });

        el.addEventListener('input', function () {
            if (fits(el)) {
                el._dvParticularsPrev = getText(el);
                syncHidden(el);
                return;
            }
            setText(el, el._dvParticularsPrev || '');
            syncHidden(el);
            // Restore caret to end after revert.
            try {
                var range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (err) {}
        });

        el.addEventListener('blur', function () {
            syncHidden(el);
        });
    }

    function scan() {
        document.querySelectorAll('[data-dv-particulars]').forEach(bindParticulars);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) return;
        form.querySelectorAll('[data-dv-particulars]').forEach(function (el) {
            syncHidden(el);
        });
    }, true);

    if (!window.__dvCheckNoBound) {
        window.__dvCheckNoBound = true;
        document.addEventListener('input', function (e) {
            var el = e.target;
            if (!el || !el.matches || !el.matches('[data-dv-check-no]')) return;
            var digits = String(el.value || '').replace(/\D/g, '').slice(0, 16);
            if (el.value !== digits) el.value = digits;
        });
    }

    if (!window.__dvMax26Bound) {
        window.__dvMax26Bound = true;
        document.addEventListener('input', function (e) {
            var el = e.target;
            if (!el || !el.matches || !el.matches('[data-dv-max26]')) return;
            var next = String(el.value || '').replace(/[^A-Za-z0-9 ]/g, '').slice(0, 26);
            if (el.value !== next) el.value = next;
        });
    }

    if (!window.__dvAcctFreeBound) {
        window.__dvAcctFreeBound = true;
        var ACCT_LINE_HEIGHT = 22;

        function parseMoneyLine(raw) {
            var cleaned = String(raw || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
            if (cleaned === '' || cleaned === '.') return '';
            var num = Number(cleaned);
            if (!isFinite(num) || num < 0) return '';
            if (num > 9999999999.99) num = 9999999999.99;
            return num.toFixed(2);
        }

        function syncAccountLines(form) {
            if (!form) return;
            var sheet = form.querySelector('.dv-print-sheet') || form;
            var acctEl = sheet.querySelector('[data-dv-acct-free="account"]');
            var amtEl = sheet.querySelector('[data-dv-acct-free="amount"]');
            var mount = sheet.querySelector('[data-dv-acct-lines-mount]');
            if (!acctEl || !amtEl || !mount) return;

            var accounts = String(acctEl.value || '').replace(/\r\n?/g, '\n').split('\n');
            var amounts = String(amtEl.value || '').replace(/\r\n?/g, '\n').split('\n');
            var len = Math.max(accounts.length, amounts.length, 2);
            mount.innerHTML = '';

            for (var i = 0; i < len; i++) {
                var account = String(accounts[i] || '').trim();
                var amount = parseMoneyLine(amounts[i] || '');
                var isCredit = (i === 1);

                var a = document.createElement('input');
                a.type = 'hidden';
                a.name = 'account_lines[' + i + '][account]';
                a.value = account;
                mount.appendChild(a);

                var d = document.createElement('input');
                d.type = 'hidden';
                d.name = 'account_lines[' + i + '][debit]';
                d.value = (!isCredit && amount !== '') ? amount : '';
                mount.appendChild(d);

                var c = document.createElement('input');
                c.type = 'hidden';
                c.name = 'account_lines[' + i + '][credit]';
                c.value = (isCredit && amount !== '') ? amount : '';
                mount.appendChild(c);
            }
        }

        function lineHeightOf(el) {
            var lh = parseFloat(window.getComputedStyle(el).lineHeight);
            return isFinite(lh) && lh > 0 ? lh : ACCT_LINE_HEIGHT;
        }

        function caretAtLine(textarea, lineIndex) {
            var lines = String(textarea.value || '').replace(/\r\n?/g, '\n').split('\n');
            while (lines.length <= lineIndex) {
                lines.push('');
            }
            textarea.value = lines.join('\n');
            var pos = 0;
            for (var i = 0; i < lineIndex; i++) {
                pos += lines[i].length + 1;
            }
            pos += lines[lineIndex].length;
            textarea.focus();
            try {
                textarea.setSelectionRange(pos, pos);
            } catch (err) {}
        }

        function focusAcctAtPoint(textarea, clientY) {
            if (!textarea) return;
            var rect = textarea.getBoundingClientRect();
            var lh = lineHeightOf(textarea);
            var y = clientY - rect.top + (textarea.scrollTop || 0);
            var lineIndex = Math.max(0, Math.floor(y / lh));
            var maxByHeight = Math.max(0, Math.floor((textarea.clientHeight || rect.height) / lh) - 1);
            if (lineIndex > maxByHeight) lineIndex = maxByHeight;
            caretAtLine(textarea, lineIndex);
        }

        document.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            var cell = e.target && e.target.closest ? e.target.closest('[data-dv-acct-click]') : null;
            if (!cell) return;
            var kind = cell.getAttribute('data-dv-acct-click');
            var textarea = cell.querySelector('[data-dv-acct-free="' + kind + '"]');
            if (!textarea) return;

            var rect = textarea.getBoundingClientRect();
            var lh = lineHeightOf(textarea);
            var y = e.clientY - rect.top + (textarea.scrollTop || 0);
            var lineIndex = Math.max(0, Math.floor(y / lh));
            var contentLines = String(textarea.value || '').replace(/\r\n?/g, '\n').split('\n');
            var contentBottom = Math.max(1, contentLines.length) * lh;

            // Existing text: keep native caret/selection. Empty space / new row: jump there.
            if (e.target === textarea && y < contentBottom) {
                return;
            }

            e.preventDefault();
            focusAcctAtPoint(textarea, e.clientY);
        }, true);

        document.addEventListener('blur', function (e) {
            var el = e.target;
            if (!el || !el.matches || !el.matches('[data-dv-acct-free="amount"]')) return;
            var lines = String(el.value || '').replace(/\r\n?/g, '\n').split('\n');
            el.value = lines.map(function (line) {
                var money = parseMoneyLine(line);
                if (money === '') return String(line || '').trim() === '' ? '' : String(line || '');
                return Number(money).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }).join('\n');
        }, true);

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.querySelector) return;
            if (!form.querySelector('[data-dv-acct-free]')) return;
            syncAccountLines(form);
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
    document.addEventListener('alpine:initialized', scan);
})();
</script>
@endif
