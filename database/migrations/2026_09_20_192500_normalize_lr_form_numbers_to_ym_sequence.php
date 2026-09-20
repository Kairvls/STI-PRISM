<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert LR "No." values to LR-YYYYMM-00001.
     * Existing numbers are reassigned by created month, ordered by id within each month.
     */
    public function up(): void
    {
        if (! Schema::hasTable('liquidation_reports_table')
            || ! Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')) {
            return;
        }

        $hasCreatedAt = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_at');
        $hasUpdatedAt = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_updated_at');

        $select = ['liquidation_report_id', 'liquidation_report_form_number'];
        if ($hasCreatedAt) {
            $select[] = 'liquidation_report_created_at';
        }
        if ($hasUpdatedAt) {
            $select[] = 'liquidation_report_updated_at';
        }

        $rows = DB::table('liquidation_reports_table')
            ->orderBy('liquidation_report_id')
            ->get($select);

        $used = [];
        $counters = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->liquidation_report_form_number ?? ''));
            if ($current !== '' && preg_match('/^LR-(\d{6})-(\d{5})$/', $current, $matches)) {
                $used[$current] = true;
                $ym = $matches[1];
                $seq = (int) $matches[2];
                $counters[$ym] = max($counters[$ym] ?? 0, $seq);
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->liquidation_report_form_number ?? ''));
            if ($current === '' || preg_match('/^LR-\d{6}-\d{5}$/', $current)) {
                continue;
            }

            $timestamp = null;
            if ($hasCreatedAt && ! empty($row->liquidation_report_created_at)) {
                $timestamp = $row->liquidation_report_created_at;
            } elseif ($hasUpdatedAt && ! empty($row->liquidation_report_updated_at)) {
                $timestamp = $row->liquidation_report_updated_at;
            }

            try {
                $ym = $timestamp
                    ? \Carbon\Carbon::parse($timestamp)->format('Ym')
                    : now()->format('Ym');
            } catch (\Throwable $e) {
                $ym = now()->format('Ym');
            }

            $seq = ($counters[$ym] ?? 0) + 1;
            if ($seq > 99999) {
                $seq = 99999;
            }

            $candidate = 'LR-'.$ym.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 99999) {
                $seq++;
                $candidate = 'LR-'.$ym.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            }

            $used[$candidate] = true;
            $counters[$ym] = $seq;

            $update = ['liquidation_report_form_number' => $candidate];
            if ($hasUpdatedAt) {
                $update['liquidation_report_updated_at'] = now();
            }

            DB::table('liquidation_reports_table')
                ->where('liquidation_report_id', $row->liquidation_report_id)
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: original LR form numbers are not retained.
    }
};
