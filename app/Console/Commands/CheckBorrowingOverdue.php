<?php

namespace App\Console\Commands;

use App\Support\BorrowingDecisionMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckBorrowingOverdue extends Command
{
    protected $signature = 'borrowing:check-overdue';

    protected $description = 'Mark past-due borrows as Overdue and email borrowers once';

    public function handle(): int
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            $this->warn('borrowing_records_table is missing.');

            return self::SUCCESS;
        }

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_expected_return_date')) {
            $this->warn('Expected return column is missing.');

            return self::SUCCESS;
        }

        // Due after the calendar return day ends (stored as endOfDay).
        $now = Carbon::now();
        $hasNotifiedAt = Schema::hasColumn('borrowing_records_table', 'borrowing_overdue_notified_at');
        $hasSlip = Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no');

        $dueRows = DB::table('borrowing_records_table')
            ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
            ->whereNotNull('borrowing_expected_return_date')
            ->where('borrowing_expected_return_date', '<', $now)
            ->when(
                $hasNotifiedAt,
                fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull('borrowing_overdue_notified_at')
                        ->orWhere('borrowing_status', 'Borrowed');
                })
            )
            ->orderBy('borrowing_record_id')
            ->get();

        if ($dueRows->isEmpty()) {
            $this->info('No past-due borrow lines to process.');

            return self::SUCCESS;
        }

        $groups = [];
        foreach ($dueRows as $row) {
            $slip = $hasSlip ? trim((string) ($row->borrowing_transaction_no ?? '')) : '';
            $key = $slip !== '' ? 'slip:'.$slip : 'id:'.(int) $row->borrowing_record_id;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'slip' => $slip,
                    'ids' => [],
                    'needs_status' => false,
                    'needs_email' => false,
                ];
            }
            $groups[$key]['ids'][] = (int) $row->borrowing_record_id;
            if ((string) ($row->borrowing_status ?? '') === 'Borrowed') {
                $groups[$key]['needs_status'] = true;
            }
            if (! $hasNotifiedAt || empty($row->borrowing_overdue_notified_at)) {
                $groups[$key]['needs_email'] = true;
            }
        }

        $marked = 0;
        $emailed = 0;

        foreach ($groups as $group) {
            $ids = array_values(array_unique($group['ids']));
            if ($ids === []) {
                continue;
            }

            if ($group['needs_status']) {
                $updated = DB::table('borrowing_records_table')
                    ->whereIn('borrowing_record_id', $ids)
                    ->where('borrowing_status', 'Borrowed')
                    ->update(['borrowing_status' => 'Overdue']);
                $marked += (int) $updated;
            }

            if ($group['needs_email'] && $group['slip'] !== '') {
                if (BorrowingDecisionMail::sendOverdue($group['slip'])) {
                    $emailed++;
                }
            }

            if ($hasNotifiedAt && $group['needs_email']) {
                // Stamp even when mail is skipped (no email on file) so we do not retry forever.
                DB::table('borrowing_records_table')
                    ->whereIn('borrowing_record_id', $ids)
                    ->whereNull('borrowing_overdue_notified_at')
                    ->update(['borrowing_overdue_notified_at' => now()]);
            }
        }

        $this->info("Overdue lines marked: {$marked}; overdue emails sent: {$emailed}.");

        return self::SUCCESS;
    }
}
