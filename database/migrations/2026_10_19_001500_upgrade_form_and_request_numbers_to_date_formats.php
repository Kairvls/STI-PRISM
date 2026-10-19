<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade existing numbers:
 * - Request No.: PR-YYYY-#### → PR-YYYYMM-####
 * - Form Nos: PREFIX-YYYYMM-SEQ → PREFIX-YYYYMMDD-SEQ (keep sequence width)
 *
 * Existing YYYYMMDD / YYYYMM Request Nos are left alone when already correct.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeRequestNumbers();

        $this->upgradeFormNumbers(
            table: 'requisition_issue_slip_table',
            idColumn: 'ris_id',
            numberColumn: 'ris_form_number',
            prefix: 'RIS',
            seqDigits: 7,
            dateColumns: ['ris_submitted_at', 'ris_created_at', 'ris_updated_at']
        );

        $this->upgradeFormNumbers(
            table: 'authority_to_purchase_table',
            idColumn: 'authority_purchase_id',
            numberColumn: 'authority_purchase_form_number',
            prefix: 'ATP',
            seqDigits: 4,
            dateColumns: ['authority_purchase_submitted_at', 'authority_purchase_created_at', 'authority_purchase_updated_at']
        );

        $this->upgradeFormNumbers(
            table: 'request_check_table',
            idColumn: 'request_check_id',
            numberColumn: 'request_check_form_number',
            prefix: 'RFC',
            seqDigits: 5,
            dateColumns: ['request_check_submitted_at', 'request_check_created_at', 'request_check_updated_at']
        );

        $this->upgradeFormNumbers(
            table: 'disbursement_vouchers_table',
            idColumn: 'dv_id',
            numberColumn: 'dv_form_number',
            prefix: 'DV',
            seqDigits: 4,
            dateColumns: ['dv_submitted_at', 'dv_created_at', 'dv_updated_at']
        );

        $this->upgradeFormNumbers(
            table: 'receiving_reports_table',
            idColumn: 'receiving_report_id',
            numberColumn: 'receiving_report_form_number',
            prefix: 'RR',
            seqDigits: 7,
            dateColumns: ['receiving_report_submitted_at', 'receiving_report_created_at', 'receiving_report_updated_at']
        );

        $this->upgradeFormNumbers(
            table: 'liquidation_reports_table',
            idColumn: 'liquidation_report_id',
            numberColumn: 'liquidation_report_form_number',
            prefix: 'LR',
            seqDigits: 5,
            dateColumns: ['liquidation_report_submitted_at', 'liquidation_report_created_at', 'liquidation_report_updated_at']
        );
    }

    public function down(): void
    {
        // Irreversible data rewrite — kept empty on purpose.
    }

    private function upgradeRequestNumbers(): void
    {
        $sources = [
            ['requisition_issue_slip_table', 'ris_request_no', 'ris_id', ['ris_submitted_at', 'ris_created_at', 'ris_updated_at']],
            ['authority_to_purchase_table', 'authority_purchase_request_no', 'authority_purchase_id', ['authority_purchase_submitted_at', 'authority_purchase_created_at', 'authority_purchase_updated_at']],
            ['request_check_table', 'request_check_request_no', 'request_check_id', ['request_check_submitted_at', 'request_check_created_at', 'request_check_updated_at']],
            ['disbursement_vouchers_table', 'dv_request_no', 'dv_id', ['dv_submitted_at', 'dv_created_at', 'dv_updated_at']],
            ['receiving_reports_table', 'receiving_report_request_no', 'receiving_report_id', ['receiving_report_submitted_at', 'receiving_report_created_at', 'receiving_report_updated_at']],
            ['liquidation_reports_table', 'liquidation_report_request_no', 'liquidation_report_id', ['liquidation_report_submitted_at', 'liquidation_report_created_at', 'liquidation_report_updated_at']],
        ];

        // old PR => [best Ym candidate, earliest timestamp]
        $mapHints = [];

        foreach ($sources as [$table, $column, $idColumn, $dateColumns]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $select = [$idColumn, $column];
            foreach ($dateColumns as $dateCol) {
                if (Schema::hasColumn($table, $dateCol)) {
                    $select[] = $dateCol;
                }
            }

            $rows = DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', 'PR-%')
                ->orderBy($idColumn)
                ->get($select);

            foreach ($rows as $row) {
                $current = trim((string) ($row->{$column} ?? ''));
                if ($current === '' || preg_match('/^PR-\d{6}-\d{4}$/', $current)) {
                    continue;
                }
                if (! preg_match('/^PR-(\d{4})-(\d{1,6})$/', $current, $m)) {
                    continue;
                }

                $year = $m[1];
                $seq = (int) $m[2];
                $ts = $this->firstTimestamp($row, $dateColumns);
                $ym = $ts ? $ts->format('Ym') : ($year.now()->format('m'));
                if (! str_starts_with($ym, $year)) {
                    // Prefer year from the ticket; keep month from timestamp when same year, else Jan.
                    $ym = $year.($ts && $ts->format('Y') === $year ? $ts->format('m') : '01');
                }

                if (! isset($mapHints[$current])
                    || ($ts && (! $mapHints[$current]['ts'] || $ts->lt($mapHints[$current]['ts'])))) {
                    $mapHints[$current] = [
                        'ym' => $ym,
                        'seq' => $seq,
                        'ts' => $ts,
                    ];
                }
            }
        }

        if ($mapHints === []) {
            return;
        }

        // Build unique new PR values.
        $used = [];
        foreach ($sources as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull($column)->pluck($column) as $value) {
                $value = trim((string) $value);
                if (preg_match('/^PR-\d{6}-\d{4}$/', $value)) {
                    $used[$value] = true;
                }
            }
        }

        $remap = [];
        foreach ($mapHints as $old => $hint) {
            $seq = max(1, min((int) $hint['seq'], 9999));
            $candidate = 'PR-'.$hint['ym'].'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 9999) {
                $seq++;
                $candidate = 'PR-'.$hint['ym'].'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
            $used[$candidate] = true;
            $remap[$old] = $candidate;
        }

        foreach ($sources as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            foreach ($remap as $old => $new) {
                if ($old === $new) {
                    continue;
                }
                DB::table($table)->where($column, $old)->update([$column => $new]);
            }
        }
    }

    /**
     * @param  array<int, string>  $dateColumns
     */
    private function upgradeFormNumbers(
        string $table,
        string $idColumn,
        string $numberColumn,
        string $prefix,
        int $seqDigits,
        array $dateColumns
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $numberColumn)) {
            return;
        }

        $select = [$idColumn, $numberColumn];
        foreach ($dateColumns as $dateCol) {
            if (Schema::hasColumn($table, $dateCol)) {
                $select[] = $dateCol;
            }
        }

        $rows = DB::table($table)
            ->whereNotNull($numberColumn)
            ->where($numberColumn, 'like', $prefix.'-%')
            ->orderBy($idColumn)
            ->get($select);

        $used = [];
        foreach ($rows as $row) {
            $current = trim((string) ($row->{$numberColumn} ?? ''));
            if ($current !== '') {
                $used[$current] = true;
            }
        }

        $patternYm = '/^'.preg_quote($prefix, '/').'-(\d{6})-(\d{'.$seqDigits.'})$/';
        $patternYmd = '/^'.preg_quote($prefix, '/').'-(\d{8})-(\d{'.$seqDigits.'})$/';
        // Legacy DV year-only: DV-YYYY-####
        $patternY = $prefix === 'DV' ? '/^DV-(\d{4})-(\d{4})$/' : null;

        foreach ($rows as $row) {
            $current = trim((string) ($row->{$numberColumn} ?? ''));
            if ($current === '' || preg_match($patternYmd, $current)) {
                continue;
            }

            $seq = null;
            $baseYm = null;

            if (preg_match($patternYm, $current, $m)) {
                $baseYm = $m[1];
                $seq = (int) $m[2];
            } elseif ($patternY && preg_match($patternY, $current, $m)) {
                $baseYm = $m[1].'01';
                $seq = (int) $m[2];
            } else {
                continue;
            }

            $ts = $this->firstTimestamp($row, $dateColumns);
            if ($ts) {
                $ymd = $ts->format('Ymd');
                // Keep the ticket's year-month when timestamp month differs wildly.
                if (substr($ymd, 0, 6) !== $baseYm && strlen($baseYm) === 6) {
                    $day = $ts->format('d');
                    $ymd = $baseYm.$day;
                }
            } else {
                $ymd = $baseYm.'01';
            }

            $seq = max(1, min($seq, (10 ** $seqDigits) - 1));
            $candidate = $prefix.'-'.$ymd.'-'.str_pad((string) $seq, $seqDigits, '0', STR_PAD_LEFT);

            $maxSeq = (10 ** $seqDigits) - 1;
            while (isset($used[$candidate]) && $candidate !== $current && $seq < $maxSeq) {
                $seq++;
                $candidate = $prefix.'-'.$ymd.'-'.str_pad((string) $seq, $seqDigits, '0', STR_PAD_LEFT);
            }

            if ($candidate === $current) {
                continue;
            }

            unset($used[$current]);
            $used[$candidate] = true;

            DB::table($table)
                ->where($idColumn, $row->{$idColumn})
                ->update([$numberColumn => $candidate]);

            // Keep DV CDV number in sync when it mirrored the old DV form number.
            if ($table === 'disbursement_vouchers_table'
                && Schema::hasColumn($table, 'dv_cdv_number')) {
                DB::table($table)
                    ->where($idColumn, $row->{$idColumn})
                    ->where('dv_cdv_number', $current)
                    ->update(['dv_cdv_number' => $candidate]);
            }
        }
    }

    /**
     * @param  array<int, string>  $dateColumns
     */
    private function firstTimestamp(object $row, array $dateColumns): ?Carbon
    {
        foreach ($dateColumns as $col) {
            $raw = $row->{$col} ?? null;
            if (empty($raw)) {
                continue;
            }
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
};
