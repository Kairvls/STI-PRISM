<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Banner data (partials.attention-focus-chip) for a list narrowed to the single record a notification points to.
 */
class RecordFocus
{
    public static function make(string $label, string $clearUrl, string $noun = 'record'): array
    {
        return [
            'key' => '',
            'label' => $label,
            'description' => 'Only the '.$noun.' from your notification is listed.',
            'scope' => null,
            'clear_url' => $clearUrl,
        ];
    }

    public static function ris(int $risId, string $clearUrl): array
    {
        $formNumber = trim((string) DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->value('ris_form_number'));

        return self::make($formNumber !== '' ? $formNumber : 'RIS #'.$risId, $clearUrl, 'RIS');
    }

    public static function receivingReport(int $rrId, string $clearUrl): array
    {
        $formNumber = trim((string) DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->value('receiving_report_form_number'));

        return self::make($formNumber !== '' ? $formNumber : 'RR #'.$rrId, $clearUrl, 'receiving report');
    }
}
