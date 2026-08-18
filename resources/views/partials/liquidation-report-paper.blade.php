@php
    $editable = $editable ?? false;
    $liq = $liq ?? null;
    $rows = ($rows ?? collect())->values();
    $oldItems = old('items');
    $fmt = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2);
    // #region agent log
    file_put_contents(base_path('debug-fcd40d.log'), json_encode(['sessionId' => 'fcd40d', 'runId' => 'pre-fix', 'hypothesisId' => 'A', 'location' => 'liquidation-report-paper.blade.php:top', 'message' => 'partial rendered', 'data' => ['liqIsNull' => $liq === null, 'liqType' => is_object($liq) ? get_class($liq) : gettype($liq), 'editable' => (bool) $editable, 'liqId' => is_object($liq) ? ($liq->liquidation_report_id ?? null) : null, 'printId' => $printId ?? null, 'url' => request()->path()], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
    // #endregion
@endphp

<div @if(!empty($printId)) id="{{ $printId }}" @endif class="liq-print-sheet mx-auto w-[297mm] max-w-full bg-white p-8 text-[12px] text-black shadow {{ $printClass ?? '' }}">
    <div class="text-center text-base font-bold">LIQUIDATION REPORT For CASH ADVANCES</div>

    <div class="mt-6 grid grid-cols-2 gap-8">
        <div class="space-y-2">
            @include('partials.liquidation-line', ['label' => 'Employee Name', 'name' => 'liquidation_report_employee_name', 'value' => old('liquidation_report_employee_name', $liq->liquidation_report_employee_name ?? (auth()->user()->user_full_name ?? '')), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Cheque Number', 'name' => 'liquidation_report_cheque_number', 'value' => old('liquidation_report_cheque_number', $liq->liquidation_report_cheque_number ?? ''), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Purpose', 'name' => 'liquidation_report_purpose', 'value' => old('liquidation_report_purpose', $liq->liquidation_report_purpose ?? ''), 'editable' => $editable])
            @include('partials.liquidation-line', ['label' => 'Amount', 'name' => 'liquidation_report_amount_advance', 'value' => old('liquidation_report_amount_advance', $liq->liquidation_report_amount_advance ?? ''), 'editable' => $editable, 'type' => 'number'])
            @include('partials.liquidation-line', ['label' => 'Date Released', 'name' => 'liquidation_report_date_released', 'value' => old('liquidation_report_date_released', $liq->liquidation_report_date_released ?? ''), 'editable' => $editable, 'type' => 'date'])
            @include('partials.liquidation-line', ['label' => 'Charge to Expense/Refundable Account', 'name' => 'liquidation_report_charge_to_account', 'value' => old('liquidation_report_charge_to_account', $liq->liquidation_report_charge_to_account ?? ''), 'editable' => $editable])
        </div>
        <div class="space-y-2">
            @include('partials.liquidation-line', ['label' => 'Date Of Activity End', 'name' => 'liquidation_report_activity_end_date', 'value' => old('liquidation_report_activity_end_date', $liq->liquidation_report_activity_end_date ?? ''), 'editable' => $editable, 'type' => 'date'])
            @include('partials.liquidation-line', ['label' => 'Deadline For The Liquidation Submissions', 'name' => 'liquidation_report_submission_deadline', 'value' => old('liquidation_report_submission_deadline', $liq->liquidation_report_submission_deadline ?? ''), 'editable' => $editable, 'type' => 'date'])
            @include('partials.liquidation-line', ['label' => 'Date Submitted', 'name' => 'liquidation_report_date_submitted', 'value' => old('liquidation_report_date_submitted', $liq->liquidation_report_date_submitted ?? ''), 'editable' => $editable, 'type' => 'date'])
            <div class="flex gap-2"><span class="w-48 shrink-0">No. Of Days Lapsed:</span><span class="flex-1 border-b border-black">{{ $liq->liquidation_report_days_lapse ?? '' }}</span></div>
            @include('partials.liquidation-line', ['label' => 'Other Income', 'name' => 'liquidation_report_other_income', 'value' => old('liquidation_report_other_income', $liq->liquidation_report_other_income ?? ''), 'editable' => $editable, 'type' => 'number'])
        </div>
    </div>

    <table class="mt-6 w-full border-collapse border border-black text-center">
        <thead>
            <tr>
                <th class="border border-black py-1" rowspan="2">PARTICULAR / Breakdown For Cash Advances</th>
                <th class="border border-black py-1" rowspan="2">AMOUNT</th>
                <th class="border border-black py-1" colspan="2">ACTUAL EXPENSES BREAKDOWN</th>
                <th class="border border-black py-1" colspan="2">Supporting Documents</th>
            </tr>
            <tr>
                <th class="border border-black py-1">Amount</th>
                <th class="border border-black py-1">Total Amount</th>
                <th class="border border-black py-1">Variance</th>
                <th class="border border-black py-1">REF.No.</th>
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
                    <td class="border border-black text-left">
                        @if($editable)<input type="text" name="items[{{ $i }}][particulars]" value="{{ $p }}" class="w-full border-0 bg-transparent px-1 outline-none">@else{{ $p }}@endif
                    </td>
                    <td class="border border-black">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][amount]" value="{{ $a }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $fmt($a) }}@endif
                    </td>
                    <td class="border border-black">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][actual_amount]" value="{{ $aa }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $fmt($aa) }}@endif
                    </td>
                    <td class="border border-black">
                        @if($editable)<input type="number" step="0.01" name="items[{{ $i }}][actual_total]" value="{{ $at }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $fmt($at) }}@endif
                    </td>
                    <td class="border border-black">{{ $editable ? '' : $fmt($v) }}</td>
                    <td class="border border-black">
                        @if($editable)<input type="text" name="items[{{ $i }}][ref_no]" value="{{ $ref }}" class="w-full border-0 bg-transparent text-center outline-none">@else{{ $ref }}@endif
                    </td>
                </tr>
            @endfor
            <tr class="font-semibold">
                <td class="border border-black text-left px-1">Total Cash Advance</td>
                <td class="border border-black">{{ $fmt($liq->liquidation_report_summary_amt_advanced ?? null) }}</td>
                <td class="border border-black">Total Actual Expense</td>
                <td class="border border-black">{{ $fmt($liq->liquidation_report_summary_actual_expense ?? null) }}</td>
                <td class="border border-black" colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-4 grid grid-cols-2 gap-8">
        <div>
            <div class="font-semibold">Note:</div>
            <div>Amount Advanced: {{ $fmt($liq->liquidation_report_summary_amt_advanced ?? null) }}</div>
            <div>Total Actual Expense: {{ $fmt($liq->liquidation_report_summary_actual_expense ?? null) }}</div>
            <div>Balance: {{ $fmt($liq->liquidation_report_summary_balance ?? null) }}</div>
        </div>
        <div>
            @include('partials.liquidation-line', ['label' => 'Cash Returned Under-Off', 'name' => 'liquidation_report_cash_returned_or_no', 'value' => old('liquidation_report_cash_returned_or_no', $liq->liquidation_report_cash_returned_or_no ?? ''), 'editable' => $editable])
        </div>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-10">
        <div>
            <div class="font-semibold">Submitted By:</div>
            @if($editable)
                <input type="text" name="liquidation_report_submitted_by_signature" value="{{ old('liquidation_report_submitted_by_signature', $liq->liquidation_report_submitted_by_signature ?? (auth()->user()->user_full_name ?? '')) }}" class="mt-6 w-full border-0 border-b border-black bg-transparent outline-none">
            @else
                <div class="mt-6 border-b border-black pb-1">{{ $liq->liquidation_report_submitted_by_signature ?? '' }}</div>
            @endif
            {{-- #region agent log --}}
            @php
                file_put_contents(base_path('debug-fcd40d.log'), json_encode(['sessionId' => 'fcd40d', 'runId' => 'pre-fix', 'hypothesisId' => 'C', 'location' => 'liquidation-report-paper.blade.php:submitted_by_date', 'message' => 'about to read submitted_by_date', 'data' => ['liqIsNull' => $liq === null, 'submittedByDate' => is_object($liq) ? ($liq->liquidation_report_submitted_by_date ?? null) : null, 'usesUnsafeTernary' => true], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
            @endphp
            {{-- #endregion --}}
            <div class="mt-1 text-xs">{{ $liq?->liquidation_report_submitted_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_submitted_by_date)->format('m/d/Y') : '' }}</div>
        </div>
        <div>
            <div class="font-semibold">Checked By:</div>
            <div class="mt-6 border-b border-black pb-1 min-h-[1.4rem]">
                @include('partials.drawn-signature', ['value' => $liq->liquidation_report_checked_by_accountant ?? ''])
            </div>
            <div class="mt-1 text-xs">{{ $liq?->liquidation_report_checked_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_checked_by_date)->format('m/d/Y') : '' }}</div>
        </div>
        <div>
            <div class="font-semibold">Indorsed By:</div>
            <div class="mt-6 border-b border-black pb-1 min-h-[1.4rem]">{{ $liq->liquidation_report_indorsed_by_supervisor ?? '' }}</div>
            <div class="mt-1 text-xs">{{ $liq?->liquidation_report_indorsed_by_date ? \Carbon\Carbon::parse($liq->liquidation_report_indorsed_by_date)->format('m/d/Y') : '' }}</div>
        </div>
        <div>
            <div class="font-semibold">Recommending Approval:</div>
            <div class="mt-6 border-b border-black pb-1 min-h-[1.4rem]">{{ $liq->liquidation_report_recommending_approval ?? '' }}</div>
        </div>
        {{-- #region agent log --}}
        @php
            file_put_contents(base_path('debug-fcd40d.log'), json_encode(['sessionId' => 'fcd40d', 'runId' => 'post-fix', 'hypothesisId' => 'C', 'location' => 'liquidation-report-paper.blade.php:after-dates', 'message' => 'passed signature date fields', 'data' => ['liqIsNull' => $liq === null, 'editable' => (bool) $editable], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
        @endphp
        {{-- #endregion --}}
    </div>
</div>
