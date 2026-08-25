<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RisFormExporter
{
    use FormExporterHelpers;
    public static function blankData(): array
    {
        return [
            'ris' => (object) [
                'ris_form_number' => '',
                'ris_purpose_description' => '',
                'ris_requested_by_signature' => '',
                'ris_requested_by_date' => null,
                'ris_approved_by_signature' => '',
                'ris_approved_by_date' => null,
                'ris_issued_by_signature' => '',
                'ris_issued_by_date' => null,
                'ris_received_by_signature' => '',
                'ris_received_by_date' => null,
            ],
            'items' => collect(),
        ];
    }

    /**
     * @param  array|object|null  $risOrData
     * @param  mixed  $items
     */
    public function downloadExcel($risOrData = null, $items = null)
    {
        $data = $this->resolveExportData($risOrData, $items, 'ris', fn () => self::blankData());
        $ris = $data['ris'];
        $items = collect($data['items'] ?? []);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RIS');
        $borderAll = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $bottom = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]];

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'STI COLLEGE - ORMOC, INC.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'REQUISITION AND ISSUE SLIP');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('D3', 'No.');
        $sheet->setCellValue('E3', $ris->ris_form_number ?? '');
        $sheet->getStyle('E3')->applyFromArray($bottom);

        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'ITEM');
        $sheet->mergeCells('B5:C5');
        $sheet->setCellValue('B5', 'QUANTITY');
        $sheet->setCellValue('B6', 'REQUESTED');
        $sheet->setCellValue('C6', 'ISSUED');
        $sheet->mergeCells('D5:D6');
        $sheet->setCellValue('D5', 'UNIT COST');
        $sheet->mergeCells('E5:E6');
        $sheet->setCellValue('E5', 'AMOUNT');
        $sheet->getStyle('A5:E6')->getFont()->setBold(true);
        $sheet->getStyle('A5:E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A5:E6')->applyFromArray($borderAll);

        $row = 7;
        foreach ($items as $item) {
            $qtyReq = $item->ris_quantity_requested ?? null;
            $qtyIss = $item->ris_quantity_issued ?? null;
            $unitCost = $item->ris_unit_cost ?? null;
            $amount = ($qtyIss !== null && $unitCost !== null)
                ? (float) $qtyIss * (float) $unitCost
                : null;

            $sheet->setCellValue("A{$row}", $item->ris_item_name_description ?? '');
            $sheet->setCellValue("B{$row}", $qtyReq);
            $sheet->setCellValue("C{$row}", $qtyIss);
            $sheet->setCellValue("D{$row}", $unitCost !== null ? (float) $unitCost : null);
            $sheet->setCellValue("E{$row}", $amount);
            if ($unitCost !== null) {
                $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            if ($amount !== null) {
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $row++;
        }

        $minRows = max(8, $items->count());
        while ($row < 7 + $minRows) {
            $row++;
        }
        $sheet->getStyle('A7:E' . ($row - 1))->applyFromArray($borderAll);

        $sheet->setCellValue("A{$row}", 'PURPOSE');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->mergeCells("B{$row}:E{$row}");
        $sheet->setCellValue("B{$row}", $ris->ris_purpose_description ?? '');
        $sheet->getStyle("B{$row}:E{$row}")->applyFromArray($bottom);
        $row += 2;

        $sigs = [
            ['Requested by:', $ris->ris_requested_by_signature ?? '', $ris->ris_requested_by_date ?? null],
            ['Approved by:', $this->plainName($ris->ris_approved_by_signature ?? ''), $ris->ris_approved_by_date ?? null],
            ['Issued by:', $ris->ris_issued_by_signature ?? '', $ris->ris_issued_by_date ?? null],
            ['Received by:', $ris->ris_received_by_signature ?? '', $ris->ris_received_by_date ?? null],
        ];
        $col = 'A';
        foreach ($sigs as $sig) {
            $sheet->setCellValue("{$col}{$row}", $sig[0]);
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $col++;
        }
        $row++;
        $col = 'A';
        foreach ($sigs as $sig) {
            $sheet->setCellValue("{$col}{$row}", $this->plainName($sig[1]));
            $sheet->getStyle("{$col}{$row}")->applyFromArray($bottom);
            $col++;
        }
        $row++;
        $col = 'A';
        foreach ($sigs as $sig) {
            $sheet->setCellValue("{$col}{$row}", 'Date: ' . $this->d($sig[2]));
            $col++;
        }

        foreach (range('A', 'E') as $c) {
            $sheet->getColumnDimension($c)->setWidth($c === 'A' ? 36 : 14);
        }

        $filename = ($ris->ris_form_number ?: 'blank-ris') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @param  array|object|null  $risOrData
     * @param  mixed  $items
     */
    public function downloadWord($risOrData = null, $items = null)
    {
        $data = $this->resolveExportData($risOrData, $items, 'ris', fn () => self::blankData());
        $ris = $data['ris'];
        $items = collect($data['items'] ?? []);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 700,
            'marginRight' => 700,
        ]);

        $section->addText('STI COLLEGE - ORMOC, INC.', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addText('REQUISITION AND ISSUE SLIP', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);
        $section->addText('No. ' . ($ris->ris_form_number ?? ''), ['size' => 10], ['alignment' => Jc::END, 'spaceAfter' => 200]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => 14000,
        ]);
        $h = ['bold' => true, 'size' => 9];
        $c = ['alignment' => Jc::CENTER];

        $table->addRow();
        $table->addCell(5000, ['vMerge' => 'restart', 'valign' => 'center'])->addText('ITEM', $h, $c);
        $table->addCell(3000, ['gridSpan' => 2])->addText('QUANTITY', $h, $c);
        $table->addCell(2500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('UNIT COST', $h, $c);
        $table->addCell(3500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('AMOUNT', $h, $c);

        $table->addRow();
        $table->addCell(5000, ['vMerge' => 'continue']);
        $table->addCell(1500)->addText('REQUESTED', $h, $c);
        $table->addCell(1500)->addText('ISSUED', $h, $c);
        $table->addCell(2500, ['vMerge' => 'continue']);
        $table->addCell(3500, ['vMerge' => 'continue']);

        $body = ['size' => 9];
        $right = ['alignment' => Jc::END];
        foreach ($items as $item) {
            $qtyIss = $item->ris_quantity_issued ?? null;
            $unitCost = $item->ris_unit_cost ?? null;
            $amount = ($qtyIss !== null && $unitCost !== null)
                ? number_format((float) $qtyIss * (float) $unitCost, 2)
                : '';

            $table->addRow();
            $table->addCell(5000)->addText((string) ($item->ris_item_name_description ?? ''), $body);
            $table->addCell(1500)->addText((string) ($item->ris_quantity_requested ?? ''), $body, $c);
            $table->addCell(1500)->addText((string) ($qtyIss ?? ''), $body, $c);
            $table->addCell(2500)->addText($unitCost !== null ? number_format((float) $unitCost, 2) : '', $body, $right);
            $table->addCell(3500)->addText($amount, $body, $right);
        }

        for ($i = $items->count(); $i < 8; $i++) {
            $table->addRow();
            foreach ([5000, 1500, 1500, 2500, 3500] as $w) {
                $table->addCell($w)->addText('', $body);
            }
        }

        $section->addTextBreak(1);
        $section->addText('PURPOSE: ' . ($ris->ris_purpose_description ?? ''), ['size' => 10]);
        $section->addTextBreak(1);

        $sigTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 40, 'unit' => TblWidth::TWIP, 'width' => 14000]);
        $sigTable->addRow();
        foreach (['Requested by:', 'Approved by:', 'Issued by:', 'Received by:'] as $label) {
            $sigTable->addCell(3500)->addText($label, ['bold' => true, 'size' => 9]);
        }
        $sigTable->addRow();
        foreach ([
            $ris->ris_requested_by_signature ?? '',
            $ris->ris_approved_by_signature ?? '',
            $ris->ris_issued_by_signature ?? '',
            $ris->ris_received_by_signature ?? '',
        ] as $name) {
            $cell = $sigTable->addCell(3500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000']);
            $cell->addText($this->plainName($name), ['size' => 9]);
        }
        $sigTable->addRow();
        foreach ([
            $ris->ris_requested_by_date ?? null,
            $ris->ris_approved_by_date ?? null,
            $ris->ris_issued_by_date ?? null,
            $ris->ris_received_by_date ?? null,
        ] as $date) {
            $sigTable->addCell(3500)->addText('Date: ' . $this->d($date), ['size' => 8]);
        }

        $filename = ($ris->ris_form_number ?: 'blank-ris') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'ris');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    protected function d($value, string $format = 'm/d/Y'): string
    {
        return $this->formatDate($value, $format);
    }
}
