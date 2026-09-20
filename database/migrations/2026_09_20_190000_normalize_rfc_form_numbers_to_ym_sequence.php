<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert RFC "No." values to RFC-YYYYMM-00001.
     * Existing numbers are reassigned by created month, ordered by id within each month.
     */
    public function up(): void
    {
        if (! Schema::hasTable('request_check_table')
            || ! Schema::hasColumn('request_check_table', 'request_check_form_number')) {
            return;
        }

        $hasCreatedAt = Schema::hasColumn('request_check_table', 'request_check_created_at');
        $hasUpdatedAt = Schema::hasColumn('request_check_table', 'request_check_updated_at');

        $select = ['request_check_id', 'request_check_form_number'];
        if ($hasCreatedAt) {
            $select[] = 'request_check_created_at';
        }
        if ($hasUpdatedAt) {
            $select[] = 'request_check_updated_at';
        }

        $rows = DB::table('request_check_table')
            ->orderBy('request_check_id')
            ->get($select);

        $used = [];
        $counters = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->request_check_form_number ?? ''));
            if ($current !== '' && preg_match('/^RFC-(\d{6})-(\d{5})$/', $current, $matches)) {
                $used[$current] = true;
                $ym = $matches[1];
                $seq = (int) $matches[2];
                $counters[$ym] = max($counters[$ym] ?? 0, $seq);
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->request_check_form_number ?? ''));
            if ($current === '' || preg_match('/^RFC-\d{6}-\d{5}$/', $current)) {
                continue;
            }

            $timestamp = null;
            if ($hasCreatedAt && ! empty($row->request_check_created_at)) {
                $timestamp = $row->request_check_created_at;
            } elseif ($hasUpdatedAt && ! empty($row->request_check_updated_at)) {
                $timestamp = $row->request_check_updated_at;
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

            $candidate = 'RFC-'.$ym.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 99999) {
                $seq++;
                $candidate = 'RFC-'.$ym.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            }

            $used[$candidate] = true;
            $counters[$ym] = $seq;

            $update = ['request_check_form_number' => $candidate];
            if ($hasUpdatedAt) {
                $update['request_check_updated_at'] = now();
            }

            DB::table('request_check_table')
                ->where('request_check_id', $row->request_check_id)
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: original RFC form numbers are not retained.
    }
};
