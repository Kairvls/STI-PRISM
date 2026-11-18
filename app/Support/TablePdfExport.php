<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class TablePdfExport
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public static function download(
        string $title,
        array $headers,
        array $rows,
        string $filename,
        ?int $amountIndex = null,
        string $orientation = 'landscape'
    ): Response {
        $pdf = Pdf::loadView('partials.table-export-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'amountIndex' => $amountIndex,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', $orientation);

        return $pdf->download($filename);
    }

    public static function money(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return number_format((float) $amount, 2);
    }

    public static function date(mixed $value, string $format = 'M j, Y'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }
}
