@php
    $editable = $editable ?? false;
    $liq = $liq ?? null;
    $rows = ($rows ?? collect())->values();
    $oldItems = old('items');
    $fmt = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2);
@endphp

<div @if(!empty($printId)) id="{{ $printId }}" @endif class="liq-print-sheet mx-auto w-[297mm] max-w-full bg-white px-10 py-8 text-[12px] leading-relaxed text-black shadow {{ $printClass ?? '' }}">
    <div class="text-center text-[15px] font-bold tracking-wide">LIQUIDATION REPORT For CASH ADVANCES</div>

    {{-- Header meta (two columns, Excel-like spacing) --}}
    <div class="mt-8 grid grid-cols-2 gap-x-16 gap-y-1">
        <div class="space-y-1">
            @include('partials.liquidation-line', ['label' => 'Employee Name', 'name' => 'liquidation_report_employee_name', 'value' => old('liquidation_report_employee_name', $liq?->liquidation_report_employee_name ?? (auth()->user()->user_full_name ?? '')), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Cheque Number', 'name' => 'liquidation_report_cheque_number', 'value' => old('liquidation_report_cheque_number', $liq?->liquidation_report_cheque_number ?? ''), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Purpose', 'name' => 'liquidation_report_purpose', 'value' => old('liquidation_report_purpose', $liq?->liquidation_report_purpose ?? ''), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Amount', 'name' => 'liquidation_report_amount_advance', 'value' => old('liquidation_report_amount_advance', $liq?->liquidation_report_amount_advance ?? ''), 'editable' => $editable, 'type' => 'number'])
            @include('partials.liquidation-line', ['label' => 'Date Released', 'name' => 'liquidation_report_date_released', 'value' => old('liquidation_report_date_released', $liq?->liquidation_report_date_released ?? ''), 'editable' => $editable, 'type' => 'date'])
            @include('partials.liquidation-line', [
                'label' => 'Charge to Expense/Refundable Account',
                'name' => 'liquidation_report_charge_to_account',
                'value' => old('liquidation_report_charge_to_account', $liq?->liquidation_report_charge_to_account ?? ''),
                'editable' => $editable,
                'labelClass' => 'w-[13.5rem] shrink-0 leading-snug',
            ])
        </div>
        <div class="space-y-1">
            @include('partials.liquidation-line', ['label' => 'Date Of Activity End', 'name' => 'liquidation_report_activity_end_date', 'value' => old('liquidation_report_activity_end_date', $liq?->liquidation_report_activity_end_date ?? ''), 'editable' => $editable, 'type' => 'date'])
            @include('partials.liquidation-line', [
                'label' => 'Deadline For The Liquidation Submission',
                'name' => 'liquidation_report_submission_deadline',
                'value' => old('liquidation_report_submission_deadline', $liq?->liquidation_report_submission_deadline ?? ''),
                'editable' => $editable,
                'type' => 'date',
                'labelClass' => 'w-[13.5rem] shrink-0 leading-snug',
            ])
            @include('partials.liquidation-line', ['label' => 'Date Submitted', 'name' => 'liquidation_report_date_submitted', 'value' => old('liquidation_report_date_submitted', $liq?->liquidation_report_date_submitted ?? ''), 'editable' => $editable, 'type' => 'date'])
            <div class="flex items-end gap-3 py-1.5">
                <span class="w-[13.5rem] shrink-0 leading-snug">No. Of Days Lapsed:</span>
                <span class="min-h-[2rem] flex-1 border-b border-black pb-0.5 leading-8">{{ $liq?->liquidation_report_days_lapse ?? '' }}</span>
            </div>
            @include('partials.liquidation-line', ['label' => 'Other Income', 'name' => 'liquidation_report_other_income', 'value' => old('liquidation_report_other_income', $liq?->liquidation_report_other_income ?? ''), 'editable' => $editable, 'type' => 'number'])
        </div>
    </div>

    {{-- Items table --}}
    <table class="mt-8 w-full border-collapse border border-black text-center">
        <thead>
            <tr>
                <th class="border border-black px-2 py-2 align-middle" rowspan="2">PARTICULAR / Breakdown For Cash Advances</th>
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
        <tbody>
            @for($i = 0; $i < 8; $i++)
                @php
                    $row = $oldItems[$i] ?? $rows[$i] ?? null;
                    $p = is_array($row) ? ($row['particulars'] ?? '') : ($row->liquidation_item_particulars ?? '');
                    $a = is_array($row) ? ($row['amount'] ?? '') : ($row->liquidation_item_particulars_amount ?? '');
                    $aa = is_array($row) ? ($row['actual_amount'] ?? '') : ($row->liquidation_item_actual_breakdown_amount ?? '');
                    $at = is_array($row) ? ($row['actual_total'] ?? '') : ($row->liquidation_item_actual_total_amount ?? '');
                    $v = is_array($row) ? '' : ($row->liquidation_item_variance ?? '');
                    $ref = is_array($row) ? ($row['ref_no'] ?? '') : ($row->liquidation_item_ref_no ?? '');
                @endphp
                <tr>
                    <td class="h-9 border border-black px-2 text-left align-middle">
                        @if($editable)<input type="text" name="items[{{ $i }}][particulars]" value="{{ $p }}" class="w-full border-0 bg-transparent px-1 outline-none">@else{{ $p }}@endif
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][amount]" value="{{ $a }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($a) }}@endif
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][actual_amount]" value="{{ $aa }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($aa) }}@endif
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][actual_total]" value="{{ $at }}" class="w-full border-0 bg-transparent text-right outline-none">@else{{ $fmt($at) }}@endif
                    </td>
                    <td class="h-9 border border-black px-2 text-right align-middle">{{ $editable ? '' : $fmt($v) }}</td>
                    <td class="h-9 border border-black px-2 text-center align-middle">
                        @if($editable)<input type="text" name="items[{{ $i }}][ref_no]" value="{{ $ref }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $ref }}@endif
                    </td>
                </tr>
            @endfor
            <tr class="font-semibold">
                <td class="border border-black px-2 py-2 text-left">Total Cash Advance</td>
                <td class="border border-black px-2 py-2 text-right">{{ $fmt($liq?->liquidation_report_summary_amt_advanced ?? null) }}</td>
                <td class="border border-black px-2 py-2 text-left">Total Actual Expense</td>
                <td class="border border-black px-2 py-2 text-right">{{ $fmt($liq?->liquidation_report_summary_actual_expense ?? null) }}</td>
                <td class="border border-black" colspan="2"></td>
            </tr>
        </tbody>
    </table>

    {{-- Note / summary — Cash Returned Under OR# sits on the Balance row (Excel layout) --}}
    <div class="mt-6 space-y-2.5">
        <div class="font-semibold">Note:</div>
        <div class="flex items-baseline gap-3">
            <span class="w-44 shrink-0">Amount Advanced:</span>
            <span>{{ $fmt($liq?->liquidation_report_summary_amt_advanced ?? null) }}</span>
        </div>
        <div class="flex items-baseline gap-3">
            <span class="w-44 shrink-0">Total Actual Expense:</span>
            <span>{{ $fmt($liq?->liquidation_report_summary_actual_expense ?? null) }}</span>
        </div>
        <div class="flex flex-wrap items-end gap-x-10 gap-y-3">
            <div class="flex min-w-[16rem] items-baseline gap-3">
                <span class="w-44 shrink-0">Balance:</span>
                <span>{{ $fmt($liq?->liquidation_report_summary_balance ?? null) }}</span>
            </div>
            <div class="flex min-w-[18rem] flex-1 items-end gap-3">
                <span class="shrink-0 font-medium">Cash Returned Under OR#:</span>
                @if($editable)
                    <input
                        type="text"
                        name="liquidation_report_cash_returned_or_no"
                        value="{{ old('liquidation_report_cash_returned_or_no', $liq?->liquidation_report_cash_returned_or_no ?? '') }}"
                        class="h-8 min-h-[2rem] max-w-xs flex-1 border-0 border-b border-black bg-transparent outline-none"
                    >
                @else
                    <span class="min-h-[2rem] min-w-[8rem] max-w-xs flex-1 border-b border-black pb-0.5 leading-8">
                        {{ $liq?->liquidation_report_cash_returned_or_no ?? '' }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Signatures stacked vertically like Excel (not a 2×2 grid) --}}
    <div class="mt-14 space-y-10">
        <div class="max-w-xl">
            <div class="flex items-start justify-between gap-8">
                <div class="font-semibold">Submitted By:</div>
                <div class="min-w-[7.5rem] text-right text-xs">
                    <div class="font-semibold">Date</div>
                    <div class="mt-1 min-h-[1.5rem] border-b border-black pb-0.5">
                        {{ $liq?->liquidation_report_submitted_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_submitted_by_date)->format('m/d/Y') : '' }}
                    </div>
                </div>
            </div>
            @if($editable)
                <input type="text" name="liquidation_report_submitted_by_signature" value="{{ old('liquidation_report_submitted_by_signature', $liq?->liquidation_report_submitted_by_signature ?? (auth()->user()->user_full_name ?? '')) }}" class="mt-5 w-full border-0 border-b border-black bg-transparent outline-none">
            @else
                <div class="mt-5 min-h-[1.75rem] border-b border-black pb-1">{{ $liq?->liquidation_report_submitted_by_signature ?? '' }}</div>
            @endif
            <div class="mt-1 text-[11px] italic">(Name of employee)</div>
        </div>

        <div class="max-w-xl">
            <div class="flex items-start justify-between gap-8">
                <div class="font-semibold">Checked By:</div>
                <div class="min-w-[7.5rem] text-right text-xs">
                    <div class="font-semibold">Date</div>
                    <div class="mt-1 min-h-[1.5rem] border-b border-black pb-0.5">
                        {{ $liq?->liquidation_report_checked_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_checked_by_date)->format('m/d/Y') : '' }}
                    </div>
                </div>
            </div>
            <div class="mt-5 min-h-[1.75rem] border-b border-black pb-1">
                @include('partials.drawn-signature', ['value' => $liq?->liquidation_report_checked_by_accountant ?? ''])
            </div>
            <div class="mt-1 text-[11px] italic">(Accountant)</div>
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
            <div class="mt-5 min-h-[1.75rem] border-b border-black pb-1">{{ $liq?->liquidation_report_indorsed_by_supervisor ?? '' }}</div>
            <div class="mt-1 text-[11px] italic">(Supervisor)</div>
        </div>

        <div class="max-w-xl">
            <div class="font-semibold">Recommending Approval:</div>
            <div class="mt-5 min-h-[1.75rem] border-b border-black pb-1">{{ $liq?->liquidation_report_recommending_approval ?? '' }}</div>
            <div class="mt-1 text-[11px] italic">(Recommending Approval)</div>
        </div>
    </div>
</div>
