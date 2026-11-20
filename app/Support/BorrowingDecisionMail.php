<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BorrowingDecisionMail
{
    /**
     * Email the teacher after an online borrow slip is accepted or denied.
     */
    public static function sendDecision(string $slipNo, string $decision, ?string $reason = null): bool
    {
        $slipNo = trim($slipNo);
        $decision = strtolower(trim($decision));
        if ($slipNo === '' || ! in_array($decision, ['accepted', 'denied'], true)) {
            return false;
        }

        $payload = self::payloadForSlip($slipNo);
        if (! $payload || ! ReporterPins::canEmail($payload['reporter'])) {
            return false;
        }

        $statusLabel = $decision === 'accepted' ? 'Approved' : 'Denied';
        $subject = $decision === 'accepted'
            ? 'PaAyo borrow '.$slipNo.' was approved'
            : 'PaAyo borrow '.$slipNo.' was denied';

        try {
            Mail::send('emails.borrow-decision', [
                'name' => $payload['reporter']->reporter_full_name,
                'employeeId' => $payload['reporter']->reporter_employee_id,
                'slipNo' => $slipNo,
                'decision' => $decision,
                'statusLabel' => $statusLabel,
                'reason' => $reason,
                'borrowFrom' => $payload['borrow_from'],
                'expectedReturn' => $payload['expected_return'],
                'items' => $payload['items'],
                'nextStep' => $payload['next_step'],
                'appUrl' => $payload['app_url'],
            ], function ($message) use ($payload, $subject) {
                $message->to($payload['reporter']->reporter_email_address)->subject($subject);
            });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Email the slip right after an online request is submitted.
     */
    public static function sendSubmitted(string $slipNo): bool
    {
        $slipNo = trim($slipNo);
        if ($slipNo === '') {
            return false;
        }

        $payload = self::payloadForSlip($slipNo);
        if (! $payload || ! ReporterPins::canEmail($payload['reporter'])) {
            return false;
        }

        $subject = 'PaAyo borrow slip '.$slipNo;

        try {
            Mail::send('emails.borrow-decision', [
                'name' => $payload['reporter']->reporter_full_name,
                'employeeId' => $payload['reporter']->reporter_employee_id,
                'slipNo' => $slipNo,
                'decision' => 'submitted',
                'statusLabel' => 'Submitted',
                'reason' => null,
                'borrowFrom' => $payload['borrow_from'],
                'expectedReturn' => $payload['expected_return'],
                'items' => $payload['items'],
                'nextStep' => 'Show this slip when you claim the equipment. Keep this email until Property / Maintenance releases the item.',
                'appUrl' => $payload['app_url'],
            ], function ($message) use ($payload, $subject) {
                $message->to($payload['reporter']->reporter_email_address)->subject($subject);
            });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Email the borrower once a slip becomes overdue.
     */
    public static function sendOverdue(string $slipNo): bool
    {
        $slipNo = trim($slipNo);
        if ($slipNo === '') {
            return false;
        }

        $payload = self::payloadForSlip($slipNo, false);
        if (! $payload || ! ReporterPins::canEmail($payload['reporter'])) {
            return false;
        }

        $subject = 'PaAyo borrow '.$slipNo.' is overdue — please return';

        try {
            Mail::send('emails.borrow-overdue', [
                'name' => $payload['reporter']->reporter_full_name,
                'employeeId' => $payload['reporter']->reporter_employee_id,
                'slipNo' => $slipNo,
                'borrowFrom' => $payload['borrow_from'],
                'expectedReturn' => $payload['expected_return'],
                'items' => $payload['items'],
                'nextStep' => 'Return the equipment to Property / Maintenance as soon as possible. Open PaAyo → Borrow equipment → Check status if you need the slip details.',
                'appUrl' => $payload['app_url'],
            ], function ($message) use ($payload, $subject) {
                $message->to($payload['reporter']->reporter_email_address)->subject($subject);
            });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * @return array{
     *     reporter: object,
     *     borrow_from: string,
     *     expected_return: string,
     *     items: list<array{name:string,qty:int}>,
     *     next_step: string,
     *     app_url: string
     * }|null
     */
    public static function payloadForSlip(string $slipNo, bool $onlineOnly = true): ?array
    {
        if (! Schema::hasTable('borrowing_records_table') || ! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
            return null;
        }

        $lines = DB::table('borrowing_records_table')
            ->leftJoin('equipment_table', 'borrowing_records_table.borrowing_equipment_id', '=', 'equipment_table.equipment_id')
            ->where('borrowing_records_table.borrowing_transaction_no', $slipNo)
            ->when(
                $onlineOnly && Schema::hasColumn('borrowing_records_table', 'borrowing_source'),
                fn ($q) => $q->where('borrowing_records_table.borrowing_source', 'online')
            )
            ->orderBy('borrowing_records_table.borrowing_record_id')
            ->get([
                'borrowing_records_table.*',
                'equipment_table.equipment_name',
            ]);

        if ($lines->isEmpty()) {
            return null;
        }

        $first = $lines->first();
        $reporter = self::resolveReporter($first);
        if (! $reporter) {
            return null;
        }

        $status = (string) ($first->borrowing_status ?? '');
        $nextStep = match ($status) {
            'Borrowed' => 'Show this slip from your employee or school email when you claim the equipment, then return it by the expected date.',
            'Pending' => 'Check your employee or school email for this slip. Show that slip when you claim the equipment.',
            'Overdue' => 'Return the equipment to Property / Maintenance as soon as possible.',
            'Denied' => 'This request was not approved. Submit a new borrow request if you still need the equipment.',
            default => 'Check your employee or school email for this slip. Show that slip when you claim the equipment.',
        };

        return [
            'reporter' => $reporter,
            'borrow_from' => self::formatDateTime($first->borrowing_date ?? null),
            'expected_return' => self::formatDateTime($first->borrowing_expected_return_date ?? null),
            'items' => $lines->map(fn ($row) => [
                'name' => (string) ($row->equipment_name ?: 'Equipment'),
                'qty' => (int) ($row->borrowing_quantity ?? 1),
            ])->values()->all(),
            'next_step' => $nextStep,
            'app_url' => rtrim((string) config('app.url'), '/'),
        ];
    }

    private static function resolveReporter(object $row): ?object
    {
        if (! Schema::hasTable('reporters_table')) {
            return null;
        }

        $custodianId = (int) ($row->borrowing_borrower_custodian_id ?? 0);
        if ($custodianId > 0 && Schema::hasTable('custodians_table')) {
            $custodian = DB::table('custodians_table')->where('custodian_id', $custodianId)->first();
            if ($custodian) {
                if (! empty($custodian->custodian_reporter_id)) {
                    $byLink = DB::table('reporters_table')
                        ->where('reporter_id', (int) $custodian->custodian_reporter_id)
                        ->first();
                    if ($byLink) {
                        return $byLink;
                    }
                }
                $emp = trim((string) ($custodian->custodian_employee_id ?? ''));
                if ($emp !== '') {
                    $byEmp = ReporterPins::findReporter($emp);
                    if ($byEmp) {
                        return $byEmp;
                    }
                }
            }
        }

        $name = trim((string) ($row->borrowing_borrower_name ?? ''));
        if ($name !== '') {
            return DB::table('reporters_table')
                ->whereRaw('LOWER(TRIM(reporter_full_name)) = ?', [mb_strtolower($name)])
                ->orderBy('reporter_id')
                ->first();
        }

        return null;
    }

    private static function formatDateTime(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }
}
