<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Builds cash-installment RFC schedules from an ATP:
 * optional downpayment RFC + N installment RFCs for (total − downpayment) / N.
 * A downpayment equal to the ATP total creates a single downpayment RFC only.
 */
class CashInstallmentPlanner
{
    public static function supported(): bool
    {
        return Schema::hasTable('installment_terms_table')
            && Schema::hasTable('cash_installment_plans_table')
            && Schema::hasTable('cash_installment_plan_items_table')
            && Schema::hasTable('request_check_table');
    }

    public static function activeTerms(): Collection
    {
        if (! Schema::hasTable('installment_terms_table')) {
            return collect();
        }

        return DB::table('installment_terms_table')
            ->where('term_is_active', 1)
            ->orderBy('term_months')
            ->orderBy('term_name')
            ->get();
    }

    public static function activeDayIntervals(): Collection
    {
        if (! Schema::hasTable('installment_day_intervals_table')) {
            return collect();
        }

        $query = DB::table('installment_day_intervals_table')
            ->where('interval_is_active', 1);

        if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
            $query->orderByRaw("CASE interval_unit WHEN 'days' THEN 0 WHEN 'monthly' THEN 1 WHEN 'quarterly' THEN 2 WHEN 'semiannual' THEN 3 WHEN 'annual' THEN 4 ELSE 9 END");
        }

        return $query
            ->orderBy('interval_days')
            ->orderBy('interval_name')
            ->get();
    }

    public static function intervalUnit(object $interval): string
    {
        $unit = (string) ($interval->interval_unit ?? 'days');

        return in_array($unit, ['days', 'monthly', 'quarterly', 'semiannual', 'annual'], true)
            ? $unit
            : 'days';
    }

    public static function shiftDate(string $anchor, string $unit, int $intervalDays, int $steps): string
    {
        $cursor = \Carbon\Carbon::parse($anchor)->startOfDay();
        if ($steps < 1) {
            return $cursor->toDateString();
        }

        return match ($unit) {
            'monthly' => $cursor->addMonths($steps)->toDateString(),
            'quarterly' => $cursor->addMonths(3 * $steps)->toDateString(),
            'semiannual' => $cursor->addMonths(6 * $steps)->toDateString(),
            'annual' => $cursor->addYears($steps)->toDateString(),
            default => $cursor->addDays(max(1, $intervalDays) * $steps)->toDateString(),
        };
    }

    /**
     * Fill installment due dates from an anchor date and a payment interval.
     * Downpayment keeps the anchor. Later rows step by days, months, quarter, half-year, or year.
     *
     * @param  list<array{kind: string, sequence: int, amount: float, due_date: string|null, label: string}>  $schedule
     * @return list<array{kind: string, sequence: int, amount: float, due_date: string|null, label: string}>
     */
    public static function applyIntervalDates(array $schedule, ?string $anchorDate, int $intervalDays, string $unit = 'days'): array
    {
        $anchor = self::normalizeDate($anchorDate);
        if ($anchor === null || $schedule === []) {
            return $schedule;
        }
        if ($unit === 'days' && $intervalDays < 1) {
            return $schedule;
        }

        $hasDownpayment = ($schedule[0]['kind'] ?? '') === 'downpayment';

        foreach ($schedule as $i => $row) {
            if ($hasDownpayment && $i === 0) {
                $schedule[$i]['due_date'] = $anchor;
                continue;
            }

            $steps = $hasDownpayment
                ? (int) ($row['sequence'] ?? $i)
                : max(0, ((int) ($row['sequence'] ?? ($i + 1))) - 1);
            $schedule[$i]['due_date'] = self::shiftDate($anchor, $unit, $intervalDays, $steps);
        }

        return $schedule;
    }

    public static function atpTotal(int $atpId): float
    {
        if ($atpId < 1 || ! Schema::hasTable('authority_to_purchase_items_table')) {
            return 0.0;
        }

        return round((float) DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $atpId)
            ->sum('atp_amount'), 2);
    }

    public static function planExistsForAtp(int $atpId): bool
    {
        if (! self::supported() || $atpId < 1) {
            return false;
        }

        return DB::table('cash_installment_plans_table')
            ->where('authority_purchase_id', $atpId)
            ->exists();
    }

    /**
     * Installment count is the number of payments typed on the plan.
     */
    public static function installmentCount(int $count): int
    {
        return max(1, min(36, $count));
    }

    /**
     * Build schedule rows (not yet persisted).
     *
     * @param  array<int, string|null>  $dates  due dates aligned to schedule rows
     * @return list<array{kind: string, sequence: int, amount: float, due_date: string|null, label: string}>
     */
    public static function buildSchedule(
        float $total,
        float $downpayment,
        int $installmentCount,
        array $dates = []
    ): array {
        $total = round($total, 2);
        $downpayment = round(max(0, $downpayment), 2);
        $count = self::installmentCount($installmentCount);

        if ($total <= 0) {
            throw new InvalidArgumentException('ATP total must be greater than zero.');
        }
        if ($downpayment > $total) {
            throw new InvalidArgumentException('Downpayment cannot exceed the ATP total.');
        }

        $remaining = round($total - $downpayment, 2);
        $rows = [];
        $dateIndex = 0;

        if ($downpayment > 0) {
            $rows[] = [
                'kind' => 'downpayment',
                'sequence' => 0,
                'amount' => $downpayment,
                'due_date' => self::normalizeDate($dates[$dateIndex] ?? null),
                'label' => 'Downpayment',
            ];
            $dateIndex++;
        }

        // Full downpayment covers the ATP — no installment RFCs.
        if ($remaining <= 0) {
            if ($rows === []) {
                throw new InvalidArgumentException('Enter a downpayment or choose an installment term.');
            }

            return $rows;
        }

        $base = $count > 0 ? floor(($remaining / $count) * 100) / 100 : 0.0;
        $allocated = 0.0;
        for ($i = 1; $i <= $count; $i++) {
            $amount = $i === $count
                ? round($remaining - $allocated, 2)
                : $base;
            $allocated = round($allocated + $amount, 2);
            $rows[] = [
                'kind' => 'installment',
                'sequence' => $i,
                'amount' => $amount,
                'due_date' => self::normalizeDate($dates[$dateIndex] ?? null),
                'label' => 'Installment '.$i.' of '.$count,
            ];
            $dateIndex++;
        }

        return $rows;
    }

    /**
     * Persist payment path, plan, and draft RFCs for the ATP.
     *
     * @param  array<int, string|null>  $dates
     * @return array{plan_id: int, rfc_ids: list<int>, schedule: list<array>}
     */
    public static function create(
        int $atpId,
        int $installmentCount,
        float $downpayment,
        array $dates = [],
        ?int $dayIntervalId = null
    ): array {
        if (! self::supported()) {
            throw new InvalidArgumentException('Cash installment tables are not ready.');
        }

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        if (! $atp) {
            throw new InvalidArgumentException('Authority to Purchase not found.');
        }

        if (self::planExistsForAtp($atpId) || RfcAtpLinks::fundedAtpIds([$atpId]) !== []) {
            throw new InvalidArgumentException('A funding request or installment plan already exists for this ATP.');
        }

        $count = self::installmentCount($installmentCount);

        $interval = null;
        $intervalDays = 0;
        $intervalUnit = 'days';
        if ($dayIntervalId) {
            $interval = DB::table('installment_day_intervals_table')
                ->where('interval_id', $dayIntervalId)
                ->where('interval_is_active', 1)
                ->first();
            if (! $interval) {
                throw new InvalidArgumentException('Select a valid payment interval.');
            }
            $intervalUnit = self::intervalUnit($interval);
            $intervalDays = (int) $interval->interval_days;
            if ($intervalUnit === 'days' && $intervalDays < 1) {
                throw new InvalidArgumentException('Select a valid payment interval.');
            }
        }

        $total = self::atpTotal($atpId);
        $schedule = self::buildSchedule($total, $downpayment, $count, $dates);

        if ($interval) {
            $anchor = null;
            foreach ($dates as $date) {
                $normalized = self::normalizeDate(is_string($date) ? $date : null);
                if ($normalized !== null) {
                    $anchor = $normalized;
                    break;
                }
            }
            if ($anchor === null) {
                throw new InvalidArgumentException(
                    $downpayment > 0
                        ? 'Set the downpayment due date so installment dates can be counted from it.'
                        : 'Set a start date so installment dates can be counted from it.'
                );
            }
            if ($anchor < now()->toDateString()) {
                throw new InvalidArgumentException('Start / downpayment date must be today or a future date.');
            }
            $schedule = self::applyIntervalDates($schedule, $anchor, $intervalDays, $intervalUnit);
        } else {
            foreach ($schedule as $row) {
                $due = self::normalizeDate($row['due_date'] ?? null);
                if ($due !== null && $due < now()->toDateString()) {
                    throw new InvalidArgumentException('Installment dates must be today or a future date.');
                }
            }
        }

        $payee = self::payeeForAtp($atp);
        $purposeBase = self::purposeForAtp($atp);
        $formLabel = (string) ($atp->authority_purchase_form_number ?? ('ATP #'.$atpId));
        $now = now();
        $userId = Auth::id();

        return DB::transaction(function () use (
            $atpId,
            $interval,
            $intervalDays,
            $count,
            $total,
            $downpayment,
            $schedule,
            $payee,
            $purposeBase,
            $formLabel,
            $now,
            $userId
        ) {
            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $atpId)
                ->update([
                    'authority_purchase_payment_path' => ProcurementPaymentPath::CASH_INSTALLMENT,
                    'authority_purchase_payment_path_chosen_at' => $now,
                    'authority_purchase_updated_at' => $now,
                ]);

            $remaining = round($total - round(max(0, $downpayment), 2), 2);
            $planPayload = [
                'authority_purchase_id' => $atpId,
                'term_id' => null,
                'plan_months' => $count,
                'plan_atp_total' => $total,
                'plan_downpayment' => round(max(0, $downpayment), 2),
                'plan_remaining' => $remaining,
                'plan_created_by' => $userId,
                'plan_created_at' => $now,
                'plan_updated_at' => $now,
            ];
            if (Schema::hasColumn('cash_installment_plans_table', 'day_interval_id')) {
                $planPayload['day_interval_id'] = $interval ? (int) $interval->interval_id : null;
            }
            if (Schema::hasColumn('cash_installment_plans_table', 'plan_interval_days')) {
                $planPayload['plan_interval_days'] = $intervalDays > 0 ? $intervalDays : null;
            }

            $planId = (int) DB::table('cash_installment_plans_table')->insertGetId($planPayload);

            $rfcIds = [];
            foreach ($schedule as $row) {
                $particulars = trim($purposeBase.' — '.$row['label'].' for '.$formLabel);
                $payload = [
                    'request_check_authority_purchase_id' => $atpId,
                    'request_check_funding_type' => ProcurementPaymentPath::CASH_INSTALLMENT,
                    'request_check_date' => $row['due_date'],
                    'request_check_payee' => $payee,
                    'request_check_amount_figures' => $row['amount'],
                    'request_check_amount_words' => self::amountInWords($row['amount']),
                    'request_check_particulars_purpose' => mb_substr($particulars, 0, 2000),
                    'request_check_requested_by' => Auth::user()->user_full_name ?? null,
                    'request_check_requested_by_user_id' => $userId,
                    'request_check_status' => 'Draft',
                    'request_check_is_archived' => 0,
                    'request_check_created_at' => $now,
                    'request_check_updated_at' => $now,
                ];

                if (Schema::hasColumn('request_check_table', 'request_check_request_no')) {
                    $requestNo = ProcurementRequestNumber::resolveFromAtp($atpId);
                    if ($requestNo) {
                        $payload['request_check_request_no'] = $requestNo;
                    }
                }

                $rfcId = (int) DB::table('request_check_table')->insertGetId($payload);
                RfcAtpLinks::sync($rfcId, [$atpId]);
                $rfcIds[] = $rfcId;

                DB::table('cash_installment_plan_items_table')->insert([
                    'plan_id' => $planId,
                    'request_check_id' => $rfcId,
                    'item_kind' => $row['kind'],
                    'item_sequence' => $row['sequence'],
                    'item_amount' => $row['amount'],
                    'item_due_date' => $row['due_date'],
                    'item_created_at' => $now,
                ]);
            }

            return [
                'plan_id' => $planId,
                'rfc_ids' => $rfcIds,
                'schedule' => $schedule,
            ];
        });
    }

    private static function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function payeeForAtp(object $atp): string
    {
        $joined = DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('authority_to_purchase_table.authority_purchase_id', $atp->authority_purchase_id)
            ->select(
                'suppliers_table.supplier_check_name',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->first();

        if ($joined && method_exists(PurchaseOrderBasket::class, 'checkPayee')) {
            $atp->supplier_check_name = $joined->supplier_check_name ?? null;
            $atp->company_name = $joined->company_name ?? null;
            $atp->shop_name = $joined->shop_name ?? null;

            return (string) (PurchaseOrderBasket::checkPayee($atp) ?: 'Supplier');
        }

        $check = trim((string) ($joined->supplier_check_name ?? ''));
        if ($check !== '') {
            return $check;
        }

        return trim((string) ($joined->company_name ?? $joined->shop_name ?? 'Supplier')) ?: 'Supplier';
    }

    private static function purposeForAtp(object $atp): string
    {
        $risId = (int) ($atp->authority_purchase_ris_id ?? 0);
        if ($risId > 0 && Schema::hasTable('requisition_issue_slip_table')) {
            $purpose = trim((string) (DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->value('ris_purpose_description') ?? ''));
            if ($purpose !== '') {
                return mb_substr($purpose, 0, 500);
            }
        }

        return 'Cash installment funding for '.(string) ($atp->authority_purchase_form_number ?? ('ATP #'.$atp->authority_purchase_id));
    }

    public static function amountInWords($amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $number = (float) $amount;
        $pesos = (int) floor($number);
        $cents = (int) round(($number - $pesos) * 100);
        $words = self::convertNumberToWords($pesos).' Pesos';
        if ($cents > 0) {
            $words .= ' and '.self::convertNumberToWords($cents).' Centavos';
        }

        return $words.' Only';
    }

    private static function convertNumberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $chunk = function (int $n) use ($ones, $tens): string {
            $parts = [];
            if ($n >= 100) {
                $parts[] = $ones[(int) floor($n / 100)].' Hundred';
                $n %= 100;
            }
            if ($n >= 20) {
                $parts[] = $tens[(int) floor($n / 10)].($n % 10 ? '-'.$ones[$n % 10] : '');
            } elseif ($n > 0) {
                $parts[] = $ones[$n];
            }

            return implode(' ', $parts);
        };

        $scales = ['', 'Thousand', 'Million', 'Billion'];
        $parts = [];
        $scale = 0;
        while ($number > 0) {
            $n = $number % 1000;
            if ($n > 0) {
                $parts[] = trim($chunk($n).' '.$scales[$scale]);
            }
            $number = (int) floor($number / 1000);
            $scale++;
        }

        return implode(' ', array_reverse($parts));
    }
}
