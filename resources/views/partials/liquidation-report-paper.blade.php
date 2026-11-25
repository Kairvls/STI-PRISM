@php
    $editable = $editable ?? false;
    $liq = $liq ?? null;
    $rows = ($rows ?? collect())->values();
    $oldItems = $editable && is_array(old('items')) ? array_values(old('items')) : [];
    $fmt = fn ($v) => \App\Support\Money::format($v);
    $todayDate = now()->format('Y-m-d');
    $defaultDeadlineDate = now()->addDays(\App\Support\LiquidationDeadline::DAYS)->format('Y-m-d');
    $isBlankPrint = !$editable && !$liq && $rows->isEmpty();
    $liqMaxRows = 20;
    $liqRowCount = $isBlankPrint
        ? 8
        : min($liqMaxRows, $oldItems !== [] ? count($oldItems) : max(1, $rows->count()));
@endphp

<div @if(!empty($printId)) id="{{ $printId }}" @endif class="liq-print-sheet mx-auto w-full max-w-[1095px] bg-white px-10 py-8 text-[12px] leading-relaxed text-black shadow {{ $printClass ?? '' }}">
    <div class="text-center text-[15px] font-bold tracking-wide">LIQUIDATION REPORT For CASH ADVANCES</div>

    {{-- Header meta (two columns, Excel-like spacing) --}}
    <div class="mt-8 grid grid-cols-2 gap-x-16 gap-y-1">
        <div class="space-y-1">
            @include('partials.liquidation-line', ['label' => 'Employee Name', 'name' => 'liquidation_report_employee_name', 'value' => old('liquidation_report_employee_name', $liq?->liquidation_report_employee_name ?? (auth()->user()->user_full_name ?? '')), 'editable' => $editable, 'required' => true])
            @include('partials.liquidation-line', ['label' => 'Cheque Number', 'name' => 'liquidation_report_cheque_number', 'value' => old('liquidation_report_cheque_number', $liq?->liquidation_report_cheque_number ?? ''), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Purpose', 'name' => 'liquidation_report_purpose', 'value' => old('liquidation_report_purpose', $liq?->liquidation_report_purpose ?? ''), 'editable' => $editable, 'required' => true])
            @include('partials.liquidation-line', ['label' => 'Amount', 'name' => 'liquidation_report_amount_advance', 'value' => old('liquidation_report_amount_advance', $liq?->liquidation_report_amount_advance ?? ''), 'editable' => $editable, 'type' => 'number', 'required' => true])
            @include('partials.liquidation-line', ['label' => 'Date Released', 'name' => 'liquidation_report_date_released', 'value' => old('liquidation_report_date_released', filled($liq?->liquidation_report_date_released) ? $liq->liquidation_report_date_released : ($editable ? $todayDate : '')), 'editable' => $editable, 'type' => 'date', 'attrs' => 'data-liq-date-released'])
            @include('partials.liquidation-line', [
                'label' => 'Charge to Expense/Refundable Account',
                'name' => 'liquidation_report_charge_to_account',
                'value' => old('liquidation_report_charge_to_account', $liq?->liquidation_report_charge_to_account ?? ''),
                'editable' => $editable,
                'labelClass' => 'w-[13.5rem] shrink-0 leading-snug',
            ])
        </div>
        <div class="space-y-1">
            @include('partials.liquidation-line', ['label' => 'Date Of Activity End', 'name' => 'liquidation_report_activity_end_date', 'value' => old('liquidation_report_activity_end_date', filled($liq?->liquidation_report_activity_end_date) ? $liq->liquidation_report_activity_end_date : ($editable ? $todayDate : '')), 'editable' => $editable, 'type' => 'date', 'attrs' => 'data-liq-activity-end'])
            @include('partials.liquidation-line', [
                'label' => 'Deadline For The Liquidation Submission',
                'name' => 'liquidation_report_submission_deadline',
                'value' => old('liquidation_report_submission_deadline', filled($liq?->liquidation_report_submission_deadline) ? $liq->liquidation_report_submission_deadline : ($editable ? $defaultDeadlineDate : '')),
                'editable' => $editable,
                'type' => 'date',
                'attrs' => 'data-liq-deadline',
                'labelClass' => 'w-[13.5rem] shrink-0 leading-snug',
                'hint' => $editable ? 'Auto: activity end (or date released) + 15 days' : null,
            ])
            @include('partials.liquidation-line', ['label' => 'Date Submitted', 'name' => 'liquidation_report_date_submitted', 'value' => old('liquidation_report_date_submitted', filled($liq?->liquidation_report_date_submitted) ? $liq->liquidation_report_date_submitted : ($editable ? $todayDate : '')), 'editable' => $editable, 'type' => 'date'])
            <div class="flex items-end gap-3 py-1.5">
                <span class="w-[13.5rem] shrink-0 leading-snug">No. Of Days Lapsed:</span>
                @if($editable)
                    <input
                        type="number"
                        min="0"
                        max="99999"
                        step="1"
                        inputmode="numeric"
                        name="liquidation_report_days_lapse"
                        value="{{ old('liquidation_report_days_lapse', $liq?->liquidation_report_days_lapse ?? '') }}"
                        data-liq-days-lapsed
                        class="min-h-[2rem] flex-1 border-0 border-b border-black bg-transparent pb-0.5 leading-8 outline-none"
                    >
                @else
                    <span class="min-h-[2rem] flex-1 border-b border-black pb-0.5 leading-8" data-liq-days-lapsed>{{ $liq?->liquidation_report_days_lapse ?? '' }}</span>
                @endif
            </div>
            @include('partials.liquidation-line', ['label' => 'Other Income', 'name' => 'liquidation_report_other_income', 'value' => old('liquidation_report_other_income', $liq?->liquidation_report_other_income ?? ''), 'editable' => $editable, 'type' => 'number'])
        </div>
    </div>

    {{-- Items table --}}
    <div @if($editable) data-doc-rows data-doc-rows-min="1" data-doc-rows-max="{{ $liqMaxRows }}" @endif>
        <table class="mt-8 w-full border-collapse border border-black text-center">
            <thead>
                <tr>
                    <th class="border border-black px-2 py-2 align-middle" rowspan="2">PARTICULAR / Breakdown For Cash Advances @if($editable)<span class="text-red-500">*</span>@endif</th>
                    <th class="border border-black px-2 py-2 align-middle" rowspan="2">AMOUNT</th>
                    <th class="border border-black px-2 py-2 align-middle" colspan="2">ACTUAL EXPENSES Amount</th>
                    <th class="border border-black px-2 py-2 align-middle" rowspan="2">Variance</th>
                    <th class="border border-black px-2 py-2 align-middle" colspan="1">Supporting Documents</th>
                </tr>
                <tr>
                    <th class="border border-black px-2 py-1.5">Amount</th>
                    <th class="border border-black px-2 py-1.5">Total Amount</th>
                    <th class="border border-black px-2 py-1.5">REF.No.</th>
                </tr>
            </thead>
            <tbody @if($editable) data-doc-rows-body @endif>
                @for($i = 0; $i < $liqRowCount; $i++)
                    @php
                        $row = $oldItems[$i] ?? $rows[$i] ?? null;
                        $p = is_array($row) ? ($row['particulars'] ?? '') : ($row->liquidation_item_particulars ?? '');
                        $a = is_array($row) ? ($row['amount'] ?? '') : ($row->liquidation_item_particulars_amount ?? '');
                        $aa = is_array($row) ? ($row['actual_amount'] ?? '') : ($row->liquidation_item_actual_breakdown_amount ?? '');
                        $at = is_array($row) ? ($row['actual_total'] ?? '') : ($row->liquidation_item_actual_total_amount ?? '');
                        $v = is_array($row) ? '' : ($row->liquidation_item_variance ?? '');
                        $ref = is_array($row) ? ($row['ref_no'] ?? '') : ($row->liquidation_item_ref_no ?? '');
                    @endphp
                    <tr @if($editable) data-doc-row @endif>
                        <td class="h-9 border border-black px-2 text-left align-middle">
                            @if($editable)<input type="text" name="items[{{ $i }}][particulars]" value="{{ $p }}" maxlength="{{ \App\Support\InputLimits::NOTES }}" class="w-full border-0 bg-transparent px-1 outline-none">@else{{ $p }}@endif
                        </td>
                        <td class="h-9 border border-black px-2 text-right align-middle">
                            @if($editable)<input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[{{ $i }}][amount]" value="{{ $a }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($a) }}@endif
                        </td>
                        <td class="h-9 border border-black px-2 text-right align-middle">
                            @if($editable)<input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[{{ $i }}][actual_amount]" value="{{ $aa }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($aa) }}@endif
                        </td>
                        <td class="h-9 border border-black px-2 text-right align-middle">
                            @if($editable)<input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[{{ $i }}][actual_total]" value="{{ $at }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($at) }}@endif
                        </td>
                        <td class="h-9 border border-black px-2 text-right align-middle" data-liq-row-variance>{{ $editable ? '' : $fmt($v) }}</td>
                        <td class="h-9 border border-black px-2 text-center align-middle">
                            @if($editable)<input type="text" name="items[{{ $i }}][ref_no]" value="{{ $ref }}" maxlength="{{ \App\Support\InputLimits::SHORT }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $ref }}@endif
                        </td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr class="font-semibold">
                    <td class="border border-black px-2 py-2 text-left">Total Cash Advance</td>
                    <td class="border border-black px-2 py-2 text-right" data-liq-total-advance>{{ $fmt($liq?->liquidation_report_summary_amt_advanced ?? null) }}</td>
                    <td class="border border-black px-2 py-2 text-left">Total Actual Expense</td>
                    <td class="border border-black px-2 py-2 text-right" data-liq-total-actual>{{ $fmt($liq?->liquidation_report_summary_actual_expense ?? null) }}</td>
                    <td class="border border-black" colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        @if($editable)
            <template data-doc-rows-template>
                <tr data-doc-row>
                    <td class="h-9 border border-black px-2 text-left align-middle">
                        <input type="text" name="items[__INDEX__][particulars]" value="" maxlength="{{ \App\Support\InputLimits::NOTES }}" class="w-full border-0 bg-transparent px-1 outline-none">
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        <input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[__INDEX__][amount]" value="" class="w-full border-0 bg-transparent text-right outline-none">
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        <input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[__INDEX__][actual_amount]" value="" class="w-full border-0 bg-transparent text-right outline-none">
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        <input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[__INDEX__][actual_total]" value="" class="w-full border-0 bg-transparent text-right outline-none">
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle" data-liq-row-variance></td>
                    <td class="h-9 border border-black px-2 text-center align-middle">
                        <input type="text" name="items[__INDEX__][ref_no]" value="" maxlength="{{ \App\Support\InputLimits::SHORT }}" class="w-full border-0 bg-transparent text-center outline-none">
                    </td>
                </tr>
            </template>
            @include('partials.doc-rows-editor', ['count' => $liqRowCount, 'max' => $liqMaxRows, 'min' => 1])
        @endif
    </div>

    {{-- Note / summary — Cash Returned Under OR# sits on the Balance row (Excel layout) --}}
    <div class="mt-6 space-y-2.5">
        <div class="font-semibold">Note:</div>
        <div class="flex items-baseline gap-3">
            <span class="w-44 shrink-0">Amount Advanced:</span>
            <span class="font-medium tabular-nums" data-liq-summary-advance>{{ $fmt($liq?->liquidation_report_summary_amt_advanced ?? null) }}</span>
        </div>
        <div class="flex items-baseline gap-3">
            <span class="w-44 shrink-0">Total Actual Expense:</span>
            <span class="font-medium tabular-nums" data-liq-summary-actual>{{ $fmt($liq?->liquidation_report_summary_actual_expense ?? null) }}</span>
        </div>
        <div class="flex flex-wrap items-end gap-x-10 gap-y-3">
            <div class="flex min-w-[16rem] items-baseline gap-3">
                <span class="w-44 shrink-0">Balance:</span>
                <span class="font-semibold tabular-nums" data-liq-summary-balance>{{ $fmt($liq?->liquidation_report_summary_balance ?? null) }}</span>
            </div>
            <div class="flex min-w-[18rem] flex-1 flex-col gap-1">
                <div class="flex items-end gap-3">
                    <span class="shrink-0 font-medium">Cash Returned Under OR#:</span>
                    @if($editable)
                        <input
                            type="text"
                            inputmode="decimal"
                            data-money
                            max="{{ \App\Support\InputLimits::COST }}"
                            name="liquidation_report_cash_returned_or_no"
                            value="{{ old('liquidation_report_cash_returned_or_no', is_numeric($liq?->liquidation_report_cash_returned_or_no) ? number_format((float) $liq->liquidation_report_cash_returned_or_no, 2) : ($liq?->liquidation_report_cash_returned_or_no ?? '')) }}"
                            class="h-8 min-h-[2rem] max-w-xs flex-1 border-0 border-b border-black bg-transparent outline-none tabular-nums"
                            placeholder="0.00"
                            data-cash-returned-input
                        >
                    @else
                        @php $cashRetVal = $liq?->liquidation_report_cash_returned_or_no ?? ''; @endphp
                        <span class="min-h-[2rem] min-w-[8rem] max-w-xs flex-1 border-b border-black pb-0.5 leading-8 tabular-nums">
                            {{ is_numeric($cashRetVal) ? $fmt($cashRetVal) : $cashRetVal }}
                        </span>
                    @endif
                </div>
                <p class="hidden text-[10px] text-amber-800" data-cash-returned-hint></p>
                <p class="hidden text-[11px] font-medium" style="color:#dc2626;" data-cash-returned-error></p>
            </div>
        </div>
    </div>

    {{-- Signatures stacked vertically like Excel (not a 2×2 grid) --}}
    @php
        $signKey = $signKey ?? ($liq?->liquidation_report_id ? 'liq-'.$liq->liquidation_report_id : 'liq-create');
        $submittedSigRaw = old(
            'liquidation_report_submitted_by_signature',
            $liq?->liquidation_report_submitted_by_signature ?? ''
        );
        $submittedName = old(
            'liquidation_report_submitted_by_name',
            \App\Support\RisWorkflow::isDrawnSignature((string) $submittedSigRaw)
                ? trim((string) ($liq?->liquidation_report_employee_name ?? auth()->user()->user_full_name ?? ''))
                : trim((string) ($submittedSigRaw !== '' ? $submittedSigRaw : (auth()->user()->user_full_name ?? '')))
        );
        $submittedImage = \App\Support\RisWorkflow::isDrawnSignature((string) $submittedSigRaw) ? $submittedSigRaw : '';
    @endphp
    <div class="mt-14 space-y-10">
        <div class="max-w-xl">
            <div class="flex items-start justify-between gap-8">
                <div class="font-semibold">Submitted By: @if($editable)<span class="text-red-500">*</span>@endif</div>
                <div class="min-w-[8.5rem] text-right text-xs">
                    <div class="font-semibold">Date</div>
                    @if($editable)
                        <input
                            type="date"
                            name="liquidation_report_submitted_by_date"
                            value="{{ old('liquidation_report_submitted_by_date', filled($liq?->liquidation_report_submitted_by_date) ? \Carbon\Carbon::parse($liq->liquidation_report_submitted_by_date)->format('Y-m-d') : $todayDate) }}"
                            class="mt-1 h-7 w-36 border-0 border-b border-black bg-transparent px-1 text-right text-xs outline-none"
                        >
                    @else
                        <div class="mt-1 min-h-[1.5rem] border-b border-black pb-0.5">
                            {{ $liq?->liquidation_report_submitted_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_submitted_by_date)->format('m/d/Y') : '' }}
                        </div>
                    @endif
                </div>
            </div>
            @if($editable)
                <div class="relative mt-5 w-full">
                    <span class="signature-name-stack w-full">
                        <img
                            id="purSigOverlay-{{ $signKey }}"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            name="liquidation_report_submitted_by_name"
                            id="purSigName-{{ $signKey }}"
                            value="{{ $submittedName }}"
                            maxlength="255"
                            autocomplete="off"
                            class="relative z-[1] w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm outline-none"
                        >
                    </span>
                    <input
                        type="hidden"
                        name="liquidation_report_submitted_by_signature"
                        id="purSigImage-{{ $signKey }}"
                        value="{{ $submittedImage }}"
                    >
                </div>
            @else
                <div class="relative mt-5 flex min-h-[1.5rem] items-center justify-center border-b border-black pb-1 text-center">
                    @include('partials.drawn-signature', [
                        'value' => $submittedSigRaw,
                        'printedName' => $submittedName,
                        'empty' => $submittedName,
                    ])
                </div>
            @endif
            <div class="mt-1 text-[11px] italic">(Name of employee)</div>
        </div>

        <div class="max-w-xl">
            <div class="flex items-start justify-between gap-8">
                <div class="font-semibold">Checked By:</div>
                <div class="min-w-[8.5rem] text-right text-xs">
                    <div class="font-semibold">Date</div>
                    @if(!empty($accLiveSign))
                        <input
                            type="date"
                            id="accCheckedByDate"
                            name="checked_by_date"
                            value="{{ !empty($liq?->liquidation_report_checked_by_date) ? \Carbon\Carbon::parse($liq->liquidation_report_checked_by_date)->format('Y-m-d') : $todayDate }}"
                            class="mt-1 h-7 w-36 border-0 border-b border-black bg-transparent px-1 text-right text-xs outline-none"
                        >
                    @else
                        <div class="mt-1 min-h-[1.5rem] border-b border-black pb-0.5">
                            {{ $liq?->liquidation_report_checked_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_checked_by_date)->format('m/d/Y') : '' }}
                        </div>
                    @endif
                </div>
            </div>
            <div
                class="relative mt-5 min-h-[1.5rem] border-b border-black pb-1 flex items-center justify-center"
                @if(!empty($accLiveSign)) id="accPaperSigTarget" @endif
            >
                @if(!empty($accLiveSign) && !\App\Support\RisWorkflow::isDrawnSignature((string) ($liq?->liquidation_report_checked_by_accountant ?? '')))
                    <span class="signature-name-stack w-full">
                        <img
                            id="accPaperSigOverlay"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            id="accPaperSigPrintedName"
                            value="{{ \App\Support\AccountingSigner::currentUserName() ?: 'Accountant' }}"
                            maxlength="120"
                            autocomplete="off"
                            aria-label="Checked by printed name"
                            class="ris-signature-input relative z-[1] w-full border-0 bg-transparent px-1 text-center text-xs font-normal not-italic leading-5 text-slate-900 outline-none ring-0 focus:outline-none focus:ring-0"
                        >
                    </span>
                @else
                    @include('partials.drawn-signature', [
                        'value' => $liq?->liquidation_report_checked_by_accountant ?? '',
                        'printedName' => \App\Support\AccountingSigner::forLiq($liq),
                    ])
                @endif
            </div>
            <div class="mt-1 text-[11px] italic">({{ \App\Support\AccountingSigner::SIGNATURE_TITLE }})</div>
        </div>

        <div class="max-w-xl">
            <div class="flex items-start justify-between gap-8">
                <div class="font-semibold">Indorsed By:</div>
                <div class="min-w-[7.5rem] text-right text-xs">
                    <div class="font-semibold">Date</div>
                    <div class="mt-1 min-h-[1.5rem] border-b border-black pb-0.5">
                        {{ $liq?->liquidation_report_indorsed_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_indorsed_by_date)->format('m/d/Y') : '' }}
                    </div>
                </div>
            </div>
            <div class="mt-5 flex min-h-[1.75rem] items-center justify-center border-b border-black pb-1 text-center">{{ $liq?->liquidation_report_indorsed_by_supervisor ?? '' }}</div>
            <div class="mt-1 text-[11px] italic">(Supervisor)</div>
        </div>

        <div class="max-w-xl">
            <div class="font-semibold">Recommending Approval:</div>
            <div class="mt-5 flex min-h-[1.75rem] items-center justify-center border-b border-black pb-1 text-center">{{ $liq?->liquidation_report_recommending_approval ?? '' }}</div>
            <div class="mt-1 text-[11px] italic">(Recommending Approval)</div>
        </div>
    </div>
</div>
