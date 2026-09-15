<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert RIS "No." values to RIS-YYYYMM-0000001.
     * Existing numbers are reassigned by created month (or update month),
     * ordered by ris_id within each month.
     */
    public function up(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_table')
            || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_form_number')) {
            return;
        }

        $hasCreatedAt = Schema::hasColumn('requisition_issue_slip_table', 'ris_created_at');
        $hasUpdatedAt = Schema::hasColumn('requisition_issue_slip_table', 'ris_updated_at');

        $select = ['ris_id', 'ris_form_number'];
        if ($hasCreatedAt) {
            $select[] = 'ris_created_at';
        }
        if ($hasUpdatedAt) {
            $select[] = 'ris_updated_at';
        }

        $rows = DB::table('requisition_issue_slip_table')
            ->orderBy('ris_id')
            ->get($select);

        $used = [];
        $counters = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->ris_form_number ?? ''));
            if ($current !== '' && preg_match('/^RIS-(\d{6})-(\d{7})$/', $current, $matches)) {
                $used[$current] = true;
                $ym = $matches[1];
                $seq = (int) $matches[2];
                $counters[$ym] = max($counters[$ym] ?? 0, $seq);
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->ris_form_number ?? ''));
            if ($current === '' || preg_match('/^RIS-\d{6}-\d{7}$/', $current)) {
                continue;
            }

            $timestamp = null;
            if ($hasCreatedAt && ! empty($row->ris_created_at)) {
                $timestamp = $row->ris_created_at;
            } elseif ($hasUpdatedAt && ! empty($row->ris_updated_at)) {
                $timestamp = $row->ris_updated_at;
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

            $candidate = 'RIS-' . $ym . '-' . str_pad((string) $seq, 7, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 9999999) {
                $seq++;
                $candidate = 'RIS-' . $ym . '-' . str_pad((string) $seq, 7, '0', STR_PAD_LEFT);
            }

            $used[$candidate] = true;
            $counters[$ym] = $seq;

            $update = ['ris_form_number' => $candidate];
            if ($hasUpdatedAt) {
                $update['ris_updated_at'] = now();
            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $row->ris_id)
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: original RIS form numbers are not retained.
    }
};
