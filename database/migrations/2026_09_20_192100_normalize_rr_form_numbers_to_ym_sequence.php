<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert RR "No." values to RR-YYYYMM-0000001.
     * Existing numbers are reassigned by created month, ordered by id within each month.
     */
    public function up(): void
    {
        if (! Schema::hasTable('receiving_reports_table')
            || ! Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
            return;
        }

        $hasCreatedAt = Schema::hasColumn('receiving_reports_table', 'receiving_report_created_at');
        $hasUpdatedAt = Schema::hasColumn('receiving_reports_table', 'receiving_report_updated_at');

        $select = ['receiving_report_id', 'receiving_report_form_number'];
        if ($hasCreatedAt) {
            $select[] = 'receiving_report_created_at';
        }
        if ($hasUpdatedAt) {
            $select[] = 'receiving_report_updated_at';
        }

        $rows = DB::table('receiving_reports_table')
            ->orderBy('receiving_report_id')
            ->get($select);

        $used = [];
        $counters = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->receiving_report_form_number ?? ''));
            if ($current !== '' && preg_match('/^RR-(\d{6})-(\d{7})$/', $current, $matches)) {
                $used[$current] = true;
                $ym = $matches[1];
                $seq = (int) $matches[2];
                $counters[$ym] = max($counters[$ym] ?? 0, $seq);
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->receiving_report_form_number ?? ''));
            if ($current === '' || preg_match('/^RR-\d{6}-\d{7}$/', $current)) {
                continue;
            }

            $timestamp = null;
            if ($hasCreatedAt && ! empty($row->receiving_report_created_at)) {
                $timestamp = $row->receiving_report_created_at;
            } elseif ($hasUpdatedAt && ! empty($row->receiving_report_updated_at)) {
                $timestamp = $row->receiving_report_updated_at;
            }

            try {
                $ym = $timestamp
                    ? \Carbon\Carbon::parse($timestamp)->format('Ym')
                    : now()->format('Ym');
            } catch (\Throwable $e) {
                $ym = now()->format('Ym');
            }

            $seq = ($counters[$ym] ?? 0) + 1;
            if ($seq > 9999999) {
                $seq = 9999999;
            }

            $candidate = 'RR-'.$ym.'-'.str_pad((string) $seq, 7, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 9999999) {
                $seq++;
                $candidate = 'RR-'.$ym.'-'.str_pad((string) $seq, 7, '0', STR_PAD_LEFT);
            }

            $used[$candidate] = true;
            $counters[$ym] = $seq;

            $update = ['receiving_report_form_number' => $candidate];
            if ($hasUpdatedAt) {
                $update['receiving_report_updated_at'] = now();
            }

            DB::table('receiving_reports_table')
                ->where('receiving_report_id', $row->receiving_report_id)
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: original RR form numbers are not retained.
    }
};
