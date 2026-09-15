<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert ATP "No." values to ATP-YYYYMM-0001.
     * Existing numbers are reassigned by created month, ordered by id within each month.
     */
    public function up(): void
    {
        if (! Schema::hasTable('authority_to_purchase_table')
            || ! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')) {
            return;
        }

        $hasCreatedAt = Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_at');
        $hasUpdatedAt = Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_updated_at');

        $select = ['authority_purchase_id', 'authority_purchase_form_number'];
        if ($hasCreatedAt) {
            $select[] = 'authority_purchase_created_at';
        }
        if ($hasUpdatedAt) {
            $select[] = 'authority_purchase_updated_at';
        }

        $rows = DB::table('authority_to_purchase_table')
            ->orderBy('authority_purchase_id')
            ->get($select);

        $used = [];
        $counters = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->authority_purchase_form_number ?? ''));
            if ($current !== '' && preg_match('/^ATP-(\d{6})-(\d{4})$/', $current, $matches)) {
                $used[$current] = true;
                $ym = $matches[1];
                $seq = (int) $matches[2];
                $counters[$ym] = max($counters[$ym] ?? 0, $seq);
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->authority_purchase_form_number ?? ''));
            if ($current === '' || preg_match('/^ATP-\d{6}-\d{4}$/', $current)) {
                continue;
            }

            $timestamp = null;
            if ($hasCreatedAt && ! empty($row->authority_purchase_created_at)) {
                $timestamp = $row->authority_purchase_created_at;
            } elseif ($hasUpdatedAt && ! empty($row->authority_purchase_updated_at)) {
                $timestamp = $row->authority_purchase_updated_at;
            }

            try {
                $ym = $timestamp
                    ? \Carbon\Carbon::parse($timestamp)->format('Ym')
                    : now()->format('Ym');
            } catch (\Throwable $e) {
                $ym = now()->format('Ym');
            }

            $seq = ($counters[$ym] ?? 0) + 1;
            if ($seq > 9999) {
                $seq = 9999;
            }

            $candidate = 'ATP-'.$ym.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && $seq < 9999) {
                $seq++;
                $candidate = 'ATP-'.$ym.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }

            $used[$candidate] = true;
            $counters[$ym] = $seq;

            $update = ['authority_purchase_form_number' => $candidate];
            if ($hasUpdatedAt) {
                $update['authority_purchase_updated_at'] = now();
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $row->authority_purchase_id)
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: original ATP form numbers are not retained.
    }
};
