<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RisFormExporter
{
    use FormExporterHelpers;

    /** Usable table width in twips for A4 landscape with ~4mm page margins + form padding. */
    private const WORD_TABLE_WIDTH = 15200;

    /** Column widths matching print CSS: 32 / 18 / 10 / 10 / 15 / 15. */
    private const WORD_COLS = [
        'item' => 4864,
        'supplier' => 2736,
        'requested' => 1520,
        'issued' => 1520,
        'unit_cost' => 2280,
        'amount' => 2280,
    ];

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
        $items = collect($data['items'] ?? [])->values();
        $rows = $this->normalizedItemRows($items);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RIS');

        $thin = Border::BORDER_THIN;
        $borderAll = ['borders' => ['allBorders' => ['borderStyle' => $thin, 'color' => ['rgb' => '1F2937']]]];
        $bottom = ['borders' => ['bottom' => ['borderStyle' => $thin, 'color' => ['rgb' => '1F2937']]]];
        $center = Alignment::HORIZONTAL_CENTER;
        $right = Alignment::HORIZONTAL_RIGHT;
        $middle = Alignment::VERTICAL_CENTER;

        // A4 landscape — match browser print page setup
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(1);
        $sheet->getPageMargins()
            ->setTop(0.16)
            ->setBottom(0.16)
            ->setLeft(0.16)
            ->setRight(0.16);

        // Column widths (~print proportions)
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(11);
        $sheet->getColumnDimension('D')->setWidth(11);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);

        // Header
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'STI COLLEGE - ORMOC, INC.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setName('Arial')->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal($center)->setVertical($middle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'REQUISITION AND ISSUE SLIP');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setName('Arial')->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal($center)->setVertical($middle);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $sheet->setCellValue('E3', 'No.');
        $sheet->getStyle('E3')->getFont()->setBold(true)->setName('Arial')->setSize(10);
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_BOTTOM);
        $sheet->setCellValue('F3', (string) ($ris->ris_form_number ?? ''));
        $sheet->getStyle('F3')->getFont()->setName('Arial')->setSize(9);
        $sheet->getStyle('F3')->getAlignment()->setHorizontal($center)->setVertical(Alignment::VERTICAL_BOTTOM);
        $sheet->getStyle('F3')->applyFromArray($bottom);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(8);

        // Table headers (rowspan via merge)
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'ITEM');
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'SUPPLIER');
        $sheet->mergeCells('C5:D5');
        $sheet->setCellValue('C5', 'QUANTITY');
        $sheet->setCellValue('C6', 'REQUESTED');
        $sheet->setCellValue('D6', 'ISSUED');
        $sheet->mergeCells('E5:E6');
        $sheet->setCellValue('E5', 'UNIT COST');
        $sheet->mergeCells('F5:F6');
        $sheet->setCellValue('F5', 'AMOUNT');

        $sheet->getStyle('A5:F6')->getFont()->setBold(true)->setName('Arial')->setSize(9);
        $sheet->getStyle('A5:F6')->getAlignment()->setHorizontal($center)->setVertical($middle)->setWrapText(true);
        $sheet->getStyle('A5:F6')->applyFromArray($borderAll);
        $sheet->getRowDimension(5)->setRowHeight(18);
        $sheet->getRowDimension(6)->setRowHeight(16);

        // Exactly 8 item rows
        $startRow = 7;
        for ($i = 0; $i < 8; $i++) {
            $r = $startRow + $i;
            $row = $rows[$i] ?? null;

            $sheet->setCellValue("A{$r}", $row['item'] ?? '');
            $sheet->setCellValue("B{$r}", $row['supplier'] ?? '');
            $sheet->setCellValue("C{$r}", $row['requested'] ?? '');
            $sheet->setCellValue("D{$r}", $row['issued'] ?? '');
            $sheet->setCellValue("E{$r}", $row['unit_cost'] ?? '');
            $sheet->setCellValue("F{$r}", $row['amount'] ?? '');

            if (($row['unit_cost'] ?? '') !== '') {
                $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            if (($row['amount'] ?? '') !== '') {
                $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            $sheet->getStyle("A{$r}:F{$r}")->applyFromArray($borderAll);
            $sheet->getStyle("A{$r}:B{$r}")->getFont()->setName('Arial')->setSize(9);
            $sheet->getStyle("C{$r}:D{$r}")->getFont()->setName('Arial')->setSize(9);
            $sheet->getStyle("E{$r}:F{$r}")->getFont()->setName('Arial')->setSize(9);
            $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setVertical($middle)->setWrapText(true);
            $sheet->getStyle("C{$r}:D{$r}")->getAlignment()->setHorizontal($center)->setVertical($middle);
            $sheet->getStyle("E{$r}:F{$r}")->getAlignment()->setHorizontal($right)->setVertical($middle);
            $sheet->getRowDimension($r)->setRowHeight(28);
        }

        $purposeRow = $startRow + 8;
        $sheet->getRowDimension($purposeRow)->setRowHeight(10);
        $purposeRow++;

        // PURPOSE (label + underlined value with spacer feel)
        $sheet->setCellValue("A{$purposeRow}", 'PURPOSE');
        $sheet->getStyle("A{$purposeRow}")->getFont()->setBold(true)->setName('Arial')->setSize(10);
        $sheet->getRowDimension($purposeRow)->setRowHeight(18);
        $purposeRow++;

        $sheet->setCellValue("A{$purposeRow}", '');
        $sheet->mergeCells("B{$purposeRow}:F{$purposeRow}");
        $sheet->setCellValue("B{$purposeRow}", (string) ($ris->ris_purpose_description ?? ''));
        $sheet->getStyle("B{$purposeRow}")->getFont()->setName('Arial')->setSize(9);
        $sheet->getStyle("B{$purposeRow}")->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM)->setWrapText(true);
        $sheet->getStyle("B{$purposeRow}:F{$purposeRow}")->applyFromArray($bottom);
        $sheet->getRowDimension($purposeRow)->setRowHeight(24);
        $purposeRow += 2;

        // Signatures — 4 balanced blocks across A–F
        $sigCols = [
            ['A', 'A'],
            ['B', 'C'],
            ['D', 'D'],
            ['E', 'F'],
        ];
        $sigs = [
            ['Requested by:', $this->plainName($ris->ris_requested_by_signature ?? ''), $ris->ris_requested_by_date ?? null],
            ['Approved by:', $this->plainName($ris->ris_approved_by_signature ?? ''), $ris->ris_approved_by_date ?? null],
            ['Issued by:', $this->plainName($ris->ris_issued_by_signature ?? ''), $ris->ris_issued_by_date ?? null],
            ['Received by:', $this->plainName($ris->ris_received_by_signature ?? ''), $ris->ris_received_by_date ?? null],
        ];

        $labelRow = $purposeRow;
        $lineRow = $purposeRow + 1;
        $dateLabelRow = $purposeRow + 2;
        $dateLineRow = $purposeRow + 3;

        foreach ($sigs as $i => $sig) {
            [$startCol, $endCol] = $sigCols[$i];
            if ($startCol !== $endCol) {
                $sheet->mergeCells("{$startCol}{$labelRow}:{$endCol}{$labelRow}");
                $sheet->mergeCells("{$startCol}{$lineRow}:{$endCol}{$lineRow}");
                $sheet->mergeCells("{$startCol}{$dateLabelRow}:{$endCol}{$dateLabelRow}");
                $sheet->mergeCells("{$startCol}{$dateLineRow}:{$endCol}{$dateLineRow}");
            }

            $sheet->setCellValue("{$startCol}{$labelRow}", $sig[0]);
            $sheet->getStyle("{$startCol}{$labelRow}")->getFont()->setName('Arial')->setSize(8);

            $sheet->setCellValue("{$startCol}{$lineRow}", $sig[1]);
            $sheet->getStyle("{$startCol}{$lineRow}")->getFont()->setName('Arial')->setSize(9);
            $sheet->getStyle("{$startCol}{$lineRow}")->getAlignment()->setHorizontal($center)->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->getStyle("{$startCol}{$lineRow}:{$endCol}{$lineRow}")->applyFromArray($bottom);

            $sheet->setCellValue("{$startCol}{$dateLabelRow}", 'Date:');
            $sheet->getStyle("{$startCol}{$dateLabelRow}")->getFont()->setName('Arial')->setSize(8);

            $sheet->setCellValue("{$startCol}{$dateLineRow}", $this->d($sig[2], 'M d, Y'));
            $sheet->getStyle("{$startCol}{$dateLineRow}")->getFont()->setName('Arial')->setSize(9);
            $sheet->getStyle("{$startCol}{$dateLineRow}")->getAlignment()->setHorizontal($center)->setVertical(Alignment::VERTICAL_BOTTOM);
            $sheet->getStyle("{$startCol}{$dateLineRow}:{$endCol}{$dateLineRow}")->applyFromArray($bottom);
        }

        $sheet->getRowDimension($labelRow)->setRowHeight(16);
        $sheet->getRowDimension($lineRow)->setRowHeight(36);
        $sheet->getRowDimension($dateLabelRow)->setRowHeight(16);
        $sheet->getRowDimension($dateLineRow)->setRowHeight(22);

        $sheet->getStyle('A1:F'.$dateLineRow)->getFont()->setName('Arial');
        $sheet->getPageSetup()->setPrintArea('A1:F'.$dateLineRow);
        $sheet->getPageSetup()->setHorizontalCentered(true);

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
        $items = collect($data['items'] ?? [])->values();
        $rows = $this->normalizedItemRows($items);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        // A4 landscape, ~4mm margins (print CSS @page)
        $margin = Converter::cmToTwip(0.4);
        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'orientation' => 'landscape',
            'marginTop' => $margin,
            'marginBottom' => $margin,
            'marginLeft' => $margin,
            'marginRight' => $margin,
        ]);

        $w = self::WORD_TABLE_WIDTH;
        $cols = self::WORD_COLS;
        $borderForm = [
            'borderSize' => 12,
            'borderColor' => '1F2937',
            'cellMarginTop' => Converter::cmToTwip(0.55),
            'cellMarginBottom' => Converter::cmToTwip(0.45),
            'cellMarginLeft' => Converter::cmToTwip(0.45),
            'cellMarginRight' => Converter::cmToTwip(0.45),
            'unit' => TblWidth::TWIP,
            'width' => $w + Converter::cmToTwip(0.9),
        ];

        // Outer form border (matches .ris-original-form)
        $form = $section->addTable($borderForm);
        $form->addRow();
        $cell = $form->addCell($borderForm['width']);

        // Header: school + title + No.
        $header = $cell->addTable([
            'borderSize' => 0,
            'cellMargin' => 0,
            'unit' => TblWidth::TWIP,
            'width' => $w,
        ]);
        $header->addRow();
        $headerCell = $header->addCell($w);
        $headerCell->addText('STI COLLEGE - ORMOC, INC.', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        $headerCell->addText('REQUISITION AND ISSUE SLIP', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $noTable = $cell->addTable([
            'borderSize' => 0,
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => $w,
        ]);
        $noTable->addRow();
        $noTable->addCell((int) ($w * 0.68))->addText('', ['size' => 1]);
        $noTable->addCell((int) ($w * 0.08), ['valign' => VerticalJc::BOTTOM])
            ->addText('No.', ['bold' => true, 'size' => 10], ['alignment' => Jc::END]);
        $noTable->addCell((int) ($w * 0.24), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '1F2937',
            'valign' => VerticalJc::BOTTOM,
        ])->addText((string) ($ris->ris_form_number ?? ''), ['size' => 9], ['alignment' => Jc::CENTER]);

        $cell->addTextBreak(1);

        // Items table
        $table = $cell->addTable([
            'borderSize' => 6,
            'borderColor' => '1F2937',
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => $w,
        ]);

        $h = ['bold' => true, 'size' => 8];
        $c = ['alignment' => Jc::CENTER];
        $body = ['size' => 8];
        $right = ['alignment' => Jc::END];
        $cellCenter = ['valign' => VerticalJc::CENTER];

        $table->addRow(Converter::cmToTwip(0.55));
        $table->addCell($cols['item'], array_merge($cellCenter, ['vMerge' => 'restart']))->addText('ITEM', $h, $c);
        $table->addCell($cols['supplier'], array_merge($cellCenter, ['vMerge' => 'restart']))->addText('SUPPLIER', $h, $c);
        $table->addCell($cols['requested'] + $cols['issued'], array_merge($cellCenter, ['gridSpan' => 2]))->addText('QUANTITY', $h, $c);
        $table->addCell($cols['unit_cost'], array_merge($cellCenter, ['vMerge' => 'restart']))->addText('UNIT COST', $h, $c);
        $table->addCell($cols['amount'], array_merge($cellCenter, ['vMerge' => 'restart']))->addText('AMOUNT', $h, $c);

        $table->addRow(Converter::cmToTwip(0.45));
        $table->addCell($cols['item'], ['vMerge' => 'continue']);
        $table->addCell($cols['supplier'], ['vMerge' => 'continue']);
        $table->addCell($cols['requested'], $cellCenter)->addText('REQUESTED', ['bold' => true, 'size' => 7], $c);
        $table->addCell($cols['issued'], $cellCenter)->addText('ISSUED', ['bold' => true, 'size' => 7], $c);
        $table->addCell($cols['unit_cost'], ['vMerge' => 'continue']);
        $table->addCell($cols['amount'], ['vMerge' => 'continue']);

        for ($i = 0; $i < 8; $i++) {
            $row = $rows[$i] ?? null;
            $table->addRow(Converter::cmToTwip(1.05));
            $table->addCell($cols['item'], $cellCenter)->addText((string) ($row['item'] ?? ''), $body);
            $table->addCell($cols['supplier'], $cellCenter)->addText((string) ($row['supplier'] ?? ''), $body);
            $table->addCell($cols['requested'], $cellCenter)->addText($this->cellText($row['requested'] ?? ''), $body, $c);
            $table->addCell($cols['issued'], $cellCenter)->addText($this->cellText($row['issued'] ?? ''), $body, $c);
            $table->addCell($cols['unit_cost'], $cellCenter)->addText($this->moneyText($row['unit_cost'] ?? ''), $body, $right);
            $table->addCell($cols['amount'], $cellCenter)->addText($this->moneyText($row['amount'] ?? ''), $body, $right);
        }

        $cell->addTextBreak(1);

        // PURPOSE
        $cell->addText('PURPOSE', ['bold' => true, 'size' => 9], ['spaceAfter' => 120]);
        $purpose = $cell->addTable([
            'borderSize' => 0,
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => $w,
        ]);
        $purpose->addRow(Converter::cmToTwip(0.7));
        $purpose->addCell(Converter::cmToTwip(2.1))->addText('', ['size' => 1]);
        $purpose->addCell($w - Converter::cmToTwip(2.1), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '1F2937',
            'valign' => VerticalJc::BOTTOM,
        ])->addText((string) ($ris->ris_purpose_description ?? ''), ['size' => 8]);

        $cell->addTextBreak(1);

        // Signatures — 4 equal columns
        $sigWidth = (int) floor($w / 4);
        $remainder = $w - ($sigWidth * 4);
        $sigs = $cell->addTable([
            'borderSize' => 0,
            'cellMargin' => 60,
            'unit' => TblWidth::TWIP,
            'width' => $w,
        ]);

        $sigData = [
            ['Requested by:', $this->plainName($ris->ris_requested_by_signature ?? ''), $ris->ris_requested_by_date ?? null],
            ['Approved by:', $this->plainName($ris->ris_approved_by_signature ?? ''), $ris->ris_approved_by_date ?? null],
            ['Issued by:', $this->plainName($ris->ris_issued_by_signature ?? ''), $ris->ris_issued_by_date ?? null],
            ['Received by:', $this->plainName($ris->ris_received_by_signature ?? ''), $ris->ris_received_by_date ?? null],
        ];

        $sigs->addRow();
        foreach ($sigData as $i => $sig) {
            $colW = $sigWidth + ($i === 3 ? $remainder : 0);
            $innerW = max(400, $colW - 120);
            $sigCell = $sigs->addCell($colW);
            $sigCell->addText($sig[0], ['size' => 8], ['spaceAfter' => 40]);

            $line = $sigCell->addTable([
                'borderSize' => 0,
                'cellMargin' => 20,
                'unit' => TblWidth::TWIP,
                'width' => $innerW,
            ]);
            $line->addRow(Converter::cmToTwip(1.0));
            $line->addCell($innerW, [
                'borderBottomSize' => 6,
                'borderBottomColor' => '1F2937',
                'valign' => VerticalJc::BOTTOM,
            ])->addText($sig[1], ['size' => 8], ['alignment' => Jc::CENTER]);

            $sigCell->addText('Date:', ['size' => 8], ['spaceBefore' => 120, 'spaceAfter' => 40]);
            $dateLine = $sigCell->addTable([
                'borderSize' => 0,
                'cellMargin' => 20,
                'unit' => TblWidth::TWIP,
                'width' => $innerW,
            ]);
            $dateLine->addRow(Converter::cmToTwip(0.6));
            $dateLine->addCell($innerW, [
                'borderBottomSize' => 6,
                'borderBottomColor' => '1F2937',
                'valign' => VerticalJc::BOTTOM,
            ])->addText($this->d($sig[2], 'M d, Y'), ['size' => 8], ['alignment' => Jc::CENTER]);
        }

        $filename = ($ris->ris_form_number ?: 'blank-ris') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'ris');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Normalize up to 8 print rows from RIS items.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @return array<int, array{item:string,supplier:string,requested:int|float|string|null,issued:int|float|string|null,unit_cost:float|string|null,amount:float|string|null}>
     */
    protected function normalizedItemRows($items): array
    {
        $rows = [];

        foreach ($items->take(8) as $item) {
            $name = trim((string) ($item->ris_item_name_description ?? ''));
            $uom = trim((string) ($item->uom_name ?? ''));
            if ($name !== '' && $uom !== '') {
                $name .= ' ('.$uom.')';
            }

            $unitCost = $item->ris_unit_cost ?? null;
            $amount = $item->ris_total_amount ?? null;
            if ($amount === null && $unitCost !== null && isset($item->ris_quantity_issued) && $item->ris_quantity_issued !== null && $item->ris_quantity_issued !== '') {
                $amount = (float) $item->ris_quantity_issued * (float) $unitCost;
            }

            $rows[] = [
                'item' => $name,
                'supplier' => (string) ($item->supplier_display_name ?? ''),
                'requested' => $item->ris_quantity_requested ?? null,
                'issued' => $item->ris_quantity_issued ?? null,
                'unit_cost' => $unitCost !== null && $unitCost !== '' ? (float) $unitCost : null,
                'amount' => $amount !== null && $amount !== '' ? (float) $amount : null,
            ];
        }

        return $rows;
    }

    protected function cellText($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    protected function moneyText($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2);
    }

    protected function d($value, string $format = 'M d, Y'): string
    {
        return $this->formatDate($value, $format);
    }
}
