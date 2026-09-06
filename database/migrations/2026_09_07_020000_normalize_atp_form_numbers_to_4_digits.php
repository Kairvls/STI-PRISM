<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('authority_to_purchase_table')
            || ! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')) {
            return;
        }

        $rows = DB::table('authority_to_purchase_table')
            ->orderBy('authority_purchase_id')
            ->get(['authority_purchase_id', 'authority_purchase_form_number']);

        $used = [];

        foreach ($rows as $row) {
            $current = trim((string) ($row->authority_purchase_form_number ?? ''));
            if ($current !== '' && preg_match('/^\d{4}$/', $current)) {
                $used[$current] = true;
            }
        }

        foreach ($rows as $row) {
            $current = trim((string) ($row->authority_purchase_form_number ?? ''));
            if ($current === '' || preg_match('/^\d{4}$/', $current)) {
                continue;
            }

            $seq = null;
            if (preg_match('/(\d+)$/', $current, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > 9999) {
                    $seq = (int) substr((string) $seq, -4);
                }
            } else {
                $seq = (int) $row->authority_purchase_id;
                if ($seq > 9999) {
                    $seq = (int) substr((string) $seq, -4);
                }
            }

            $candidate = str_pad((string) max(0, min($seq, 9999)), 4, '0', STR_PAD_LEFT);
            while (isset($used[$candidate]) && (int) $candidate < 9999) {
                $candidate = str_pad((string) (((int) $candidate) + 1), 4, '0', STR_PAD_LEFT);
            }
            $used[$candidate] = true;

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $row->authority_purchase_id)
                ->update([
                    'authority_purchase_form_number' => $candidate,
                    'authority_purchase_updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Irreversible: original ATP-YYYY-##### values are not retained.
    }
};
