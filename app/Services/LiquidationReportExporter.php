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

class LiquidationReportExporter
{
    use FormExporterHelpers;

    public static function blankData(): array
    {
        return [
            'liq' => (object) [
                'liquidation_report_form_number' => 'blank-liquidation',
                'liquidation_report_employee_name' => '',
                'liquidation_report_cheque_number' => '',
                'liquidation_report_purpose' => '',
                'liquidation_report_amount_advance' => null,
                'liquidation_report_date_released' => null,
                'liquidation_report_charge_to_account' => '',
                'liquidation_report_activity_end_date' => null,
                'liquidation_report_submission_deadline' => null,
                'liquidation_report_date_submitted' => null,
                'liquidation_report_days_lapse' => '',
                'liquidation_report_other_income' => null,
                'liquidation_report_summary_amt_advanced' => null,
                'liquidation_report_summary_actual_expense' => null,
                'liquidation_report_summary_balance' => null,
                'liquidation_report_cash_returned_or_no' => '',
                'liquidation_report_submitted_by_signature' => '',
                'liquidation_report_submitted_by_date' => null,
                'liquidation_report_checked_by_accountant' => '',
                'liquidation_report_checked_by_date' => null,
                'liquidation_report_indorsed_by_supervisor' => '',
                'liquidation_report_indorsed_by_date' => null,
                'liquidation_report_recommending_approval' => '',
            ],
            'items' => collect(),
        ];
    }

    /**
     * @param  array|object|null  $liqOrData  Full data array, liq object, or null for blank
     * @param  mixed  $items
     */
    public function downloadExcel($liqOrData = null, $items = null)
    {
        $data = $this->resolveExportData($liqOrData, $items, 'liq', fn () => self::blankData());
        $liq = $data['liq'];
        $items = collect($data['items'] ?? []);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liquidation');

        $thin = Border::BORDER_THIN;
        $borderAll = [
            'borders' => [
                'allBorders' => ['borderStyle' => $thin],
            ],
        ];
        $bottomBorder = [
            'borders' => [
                'bottom' => ['borderStyle' => $thin],
            ],
        ];

        // Title
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'LIQUIDATION REPORT For CASH ADVANCES');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header meta — left A/B, right F/G
        $sheet->setCellValue('A3', 'Employee Name:');
        $sheet->setCellValue('B3', $liq->liquidation_report_employee_name ?? '');
        $sheet->setCellValue('F3', 'Date Of Activity End:');
        $sheet->setCellValue('G3', $this->d($liq->liquidation_report_activity_end_date ?? null));

        $sheet->setCellValue('A4', 'Cheque Number:');
        $sheet->setCellValue('B4', $liq->liquidation_report_cheque_number ?? '');
        $sheet->setCellValue('F4', 'Deadline For The Liquidation Submission:');
        $sheet->setCellValue('G4', $this->d($liq->liquidation_report_submission_deadline ?? null));

        $sheet->setCellValue('A5', 'Purpose:');
        $sheet->setCellValue('B5', $liq->liquidation_report_purpose ?? '');
        $sheet->setCellValue('F5', 'Date Submitted:');
        $sheet->setCellValue('G5', $this->d($liq->liquidation_report_date_submitted ?? null));

        $sheet->setCellValue('A6', 'Amount:');
        $sheet->setCellValue('B6', isset($liq->liquidation_report_amount_advance) && $liq->liquidation_report_amount_advance !== null && $liq->liquidation_report_amount_advance !== ''
            ? (float) $liq->liquidation_report_amount_advance
            : null);
        $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('F6', 'No. Of Days Lapsed:');
        $sheet->setCellValue('G6', $liq->liquidation_report_days_lapse ?? '');

        $sheet->setCellValue('A7', 'Date Released:');
        $sheet->setCellValue('B7', $this->d($liq->liquidation_report_date_released ?? null));
        $sheet->setCellValue('F7', 'Other Income:');
        if (isset($liq->liquidation_report_other_income) && $liq->liquidation_report_other_income !== null && $liq->liquidation_report_other_income !== '') {
            $sheet->setCellValue('G7', (float) $liq->liquidation_report_other_income);
            $sheet->getStyle('G7')->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->setCellValue('A8', 'Charge to Expense/Refundable Account:');
        $sheet->setCellValue('B8', $liq->liquidation_report_charge_to_account ?? '');

        $sheet->getStyle('A3:A8')->getFont()->setBold(true);
        $sheet->getStyle('F3:F7')->getFont()->setBold(true);
        $sheet->getStyle('G3:G7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Two-row table header (rows 10–11)
        $sheet->mergeCells('A10:A11');
        $sheet->setCellValue('A10', 'PARTICULAR / Breakdown For Cash Advances');
        $sheet->mergeCells('B10:B11');
        $sheet->setCellValue('B10', 'AMOUNT');
        $sheet->mergeCells('C10:D10');
        $sheet->setCellValue('C10', 'ACTUAL EXPENSES Amount');
        $sheet->setCellValue('C11', 'Amount');
        $sheet->setCellValue('D11', 'Total Amount');
        $sheet->mergeCells('E10:E11');
        $sheet->setCellValue('E10', 'Variance');
        $sheet->mergeCells('F10:G10');
        $sheet->setCellValue('F10', 'Supporting Documents');
        $sheet->mergeCells('F11:G11');
        $sheet->setCellValue('F11', 'REF.No.');

        $sheet->getStyle('A10:G11')->getFont()->setBold(true);
        $sheet->getStyle('A10:G11')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle('A10:G11')->applyFromArray($borderAll);
        $sheet->getRowDimension(10)->setRowHeight(22);
        $sheet->getRowDimension(11)->setRowHeight(20);

        // Item rows (pad to 8 blank rows for form-like blank templates)
        $row = 12;
        $totalAdvance = 0.0;
        $totalActual = 0.0;
        $itemStart = $row;

        foreach ($items as $item) {
            $amt = (float) ($item->liquidation_item_particulars_amount ?? 0);
            $actual = (float) ($item->liquidation_item_actual_breakdown_amount ?? 0);
            $total = (float) ($item->liquidation_item_actual_total_amount ?? 0);
            $variance = (float) ($item->liquidation_item_variance ?? 0);

            $sheet->setCellValue("A{$row}", $item->liquidation_item_particulars ?? '');
            $sheet->setCellValue("B{$row}", $amt);
            $sheet->setCellValue("C{$row}", $actual);
            $sheet->setCellValue("D{$row}", $total);
            $sheet->setCellValue("E{$row}", $variance);
            $sheet->mergeCells("F{$row}:G{$row}");
            $sheet->setCellValue("F{$row}", $item->liquidation_item_ref_no ?? '');

            $totalAdvance += $amt;
            $totalActual += $total;
            $row++;
        }

        $minDataRows = max(8, $items->count());
        $filled = $row - $itemStart;
        for ($i = $filled; $i < $minDataRows; $i++) {
            $sheet->mergeCells("F{$row}:G{$row}");
            $row++;
        }

        $itemEnd = $row - 1;
        if ($itemEnd >= $itemStart) {
            $sheet->getStyle("A{$itemStart}:G{$itemEnd}")->applyFromArray($borderAll);
            $sheet->getStyle("B{$itemStart}:E{$itemEnd}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$itemStart}:E{$itemEnd}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$itemStart}:G{$itemEnd}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Totals row
        $hasItems = $items->isNotEmpty();
        $sheet->setCellValue("A{$row}", 'Total Cash Advance');
        $sheet->setCellValue("B{$row}", $hasItems ? $totalAdvance : null);
        $sheet->setCellValue("C{$row}", 'Total Actual Expense');
        $sheet->setCellValue("D{$row}", $hasItems ? $totalActual : null);
        $sheet->mergeCells("F{$row}:G{$row}");
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($borderAll);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row += 2;

        // Note / summary
        $sheet->setCellValue("A{$row}", 'Note:');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", 'Amount Advanced:');
        $sheet->setCellValue("B{$row}", isset($liq->liquidation_report_summary_amt_advanced) && $liq->liquidation_report_summary_amt_advanced !== null && $liq->liquidation_report_summary_amt_advanced !== ''
            ? (float) $liq->liquidation_report_summary_amt_advanced
            : null);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        $sheet->setCellValue("A{$row}", 'Total Actual Expense:');
        $sheet->setCellValue("B{$row}", isset($liq->liquidation_report_summary_actual_expense) && $liq->liquidation_report_summary_actual_expense !== null && $liq->liquidation_report_summary_actual_expense !== ''
            ? (float) $liq->liquidation_report_summary_actual_expense
            : null);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        $sheet->setCellValue("A{$row}", 'Balance:');
        $sheet->setCellValue("B{$row}", isset($liq->liquidation_report_summary_balance) && $liq->liquidation_report_summary_balance !== null && $liq->liquidation_report_summary_balance !== ''
            ? (float) $liq->liquidation_report_summary_balance
            : null);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue("C{$row}", 'Cash Returned Under OR#:');
        $sheet->setCellValue("D{$row}", $liq->liquidation_report_cash_returned_or_no ?? '');
        $row += 4;

        // Signatures
        $this->excelSignatureBlock(
            $sheet,
            $row,
            'Submitted By:',
            $liq->liquidation_report_submitted_by_signature ?? '',
            '(Name of employee)',
            $this->d($liq->liquidation_report_submitted_by_date ?? null),
            $bottomBorder
        );
        $row += 4;

        $this->excelSignatureBlock(
            $sheet,
            $row,
            'Checked By:',
            is_string($liq->liquidation_report_checked_by_accountant ?? null)
                && ! str_starts_with((string) $liq->liquidation_report_checked_by_accountant, 'data:')
                ? $liq->liquidation_report_checked_by_accountant
                : '',
            '(Accountant)',
            $this->d($liq->liquidation_report_checked_by_date ?? null),
            $bottomBorder
        );
        $row += 4;

        $this->excelSignatureBlock(
            $sheet,
            $row,
            'Indorsed By:',
            $liq->liquidation_report_indorsed_by_supervisor ?? '',
            '(Supervisor)',
            $this->d($liq->liquidation_report_indorsed_by_date ?? null),
            $bottomBorder
        );
        $row += 4;

        $sheet->setCellValue("A{$row}", 'Recommending Approval:');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", $liq->liquidation_report_recommending_approval ?? '');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($bottomBorder);
        $row++;
        $sheet->setCellValue("A{$row}", '(Recommending Approval)');
        $sheet->getStyle("A{$row}")->getFont()->setSize(9)->setItalic(true);

        // Fixed column widths (form-like)
        $sheet->getColumnDimension('A')->setWidth(36);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(16);

        $filename = ($liq->liquidation_report_form_number ?? 'liquidation') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadWord($liqOrData = null, $items = null)
    {
        $data = $this->resolveExportData($liqOrData, $items, 'liq', fn () => self::blankData());
        $liq = $data['liq'];
        $items = collect($data['items'] ?? []);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 700,
            'marginRight' => 700,
        ]);

        $section->addText(
            'LIQUIDATION REPORT For CASH ADVANCES',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );

        $meta = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => 9500,
        ]);
        $this->wordPair($meta, 'Employee Name', $liq->liquidation_report_employee_name ?? '', 'Date Of Activity End', $this->d($liq->liquidation_report_activity_end_date ?? null));
        $this->wordPair($meta, 'Cheque Number', $liq->liquidation_report_cheque_number ?? '', 'Deadline For The Liquidation Submission', $this->d($liq->liquidation_report_submission_deadline ?? null));
        $this->wordPair($meta, 'Purpose', $liq->liquidation_report_purpose ?? '', 'Date Submitted', $this->d($liq->liquidation_report_date_submitted ?? null));
        $this->wordPair($meta, 'Amount', $this->n($liq->liquidation_report_amount_advance ?? null), 'No. Of Days Lapsed', (string) ($liq->liquidation_report_days_lapse ?? ''));
        $this->wordPair($meta, 'Date Released', $this->d($liq->liquidation_report_date_released ?? null), 'Other Income', $this->n($liq->liquidation_report_other_income ?? null));
        $this->wordPair($meta, 'Charge to Expense/Refundable Account', $liq->liquidation_report_charge_to_account ?? '', '', '');

        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => 9500,
        ]);

        // Header row 1
        $table->addRow();
        $cellStyle = ['valign' => 'center'];
        $hFont = ['bold' => true, 'size' => 8];
        $hPara = ['alignment' => Jc::CENTER];

        $c = $table->addCell(2600, array_merge($cellStyle, ['vMerge' => 'restart']));
        $c->addText('PARTICULAR / Breakdown For Cash Advances', $hFont, $hPara);
        $c = $table->addCell(1100, array_merge($cellStyle, ['vMerge' => 'restart']));
        $c->addText('AMOUNT', $hFont, $hPara);
        $c = $table->addCell(2200, array_merge($cellStyle, ['gridSpan' => 2]));
        $c->addText('ACTUAL EXPENSES Amount', $hFont, $hPara);
        $c = $table->addCell(1000, array_merge($cellStyle, ['vMerge' => 'restart']));
        $c->addText('Variance', $hFont, $hPara);
        $c = $table->addCell(1600, array_merge($cellStyle, ['gridSpan' => 1]));
        $c->addText('Supporting Documents', $hFont, $hPara);

        // Header row 2
        $table->addRow();
        $table->addCell(2600, ['vMerge' => 'continue']);
        $table->addCell(1100, ['vMerge' => 'continue']);
        $table->addCell(1100)->addText('Amount', $hFont, $hPara);
        $table->addCell(1100)->addText('Total Amount', $hFont, $hPara);
        $table->addCell(1000, ['vMerge' => 'continue']);
        $table->addCell(1600)->addText('REF.No.', $hFont, $hPara);

        $totalAdvance = 0.0;
        $totalActual = 0.0;
        $bodyFont = ['size' => 9];
        $right = ['alignment' => Jc::END];
        $center = ['alignment' => Jc::CENTER];

        foreach ($items as $item) {
            $amt = (float) ($item->liquidation_item_particulars_amount ?? 0);
            $actual = (float) ($item->liquidation_item_actual_breakdown_amount ?? 0);
            $total = (float) ($item->liquidation_item_actual_total_amount ?? 0);
            $variance = (float) ($item->liquidation_item_variance ?? 0);
            $totalAdvance += $amt;
            $totalActual += $total;

            $table->addRow();
            $table->addCell(2600)->addText((string) ($item->liquidation_item_particulars ?? ''), $bodyFont);
            $table->addCell(1100)->addText($this->n($amt), $bodyFont, $right);
            $table->addCell(1100)->addText($this->n($actual), $bodyFont, $right);
            $table->addCell(1100)->addText($this->n($total), $bodyFont, $right);
            $table->addCell(1000)->addText($this->n($variance), $bodyFont, $right);
            $table->addCell(1600)->addText((string) ($item->liquidation_item_ref_no ?? ''), $bodyFont, $center);
        }

        $blankRows = max(0, 8 - $items->count());
        for ($i = 0; $i < $blankRows; $i++) {
            $table->addRow();
            $table->addCell(2600)->addText('', $bodyFont);
            $table->addCell(1100)->addText('', $bodyFont);
            $table->addCell(1100)->addText('', $bodyFont);
            $table->addCell(1100)->addText('', $bodyFont);
            $table->addCell(1000)->addText('', $bodyFont);
            $table->addCell(1600)->addText('', $bodyFont);
        }

        $table->addRow();
        $table->addCell(2600)->addText('Total Cash Advance', ['bold' => true, 'size' => 9]);
        $table->addCell(1100)->addText($items->isNotEmpty() ? $this->n($totalAdvance) : '', ['bold' => true, 'size' => 9], $right);
        $table->addCell(1100)->addText('Total Actual Expense', ['bold' => true, 'size' => 9]);
        $table->addCell(1100)->addText($items->isNotEmpty() ? $this->n($totalActual) : '', ['bold' => true, 'size' => 9], $right);
        $table->addCell(1000)->addText('', $bodyFont);
        $table->addCell(1600)->addText('', $bodyFont);

        $section->addTextBreak(1);
        $section->addText('Note:', ['bold' => true, 'size' => 10]);
        $section->addText('Amount Advanced: ' . $this->n($liq->liquidation_report_summary_amt_advanced ?? null), ['size' => 10]);
        $section->addText('Total Actual Expense: ' . $this->n($liq->liquidation_report_summary_actual_expense ?? null), ['size' => 10]);
        $section->addText(
            'Balance: ' . $this->n($liq->liquidation_report_summary_balance ?? null)
            . '          Cash Returned Under OR#: ' . ($liq->liquidation_report_cash_returned_or_no ?? ''),
            ['size' => 10]
        );

        $section->addTextBreak(2);
        $this->wordSignature(
            $section,
            'Submitted By:',
            $liq->liquidation_report_submitted_by_signature ?? '',
            '(Name of employee)',
            $this->d($liq->liquidation_report_submitted_by_date ?? null)
        );
        $checkedName = is_string($liq->liquidation_report_checked_by_accountant ?? null)
            && ! str_starts_with((string) $liq->liquidation_report_checked_by_accountant, 'data:')
            ? $liq->liquidation_report_checked_by_accountant
            : '';
        $this->wordSignature(
            $section,
            'Checked By:',
            $checkedName,
            '(Accountant)',
            $this->d($liq->liquidation_report_checked_by_date ?? null)
        );
        $this->wordSignature(
            $section,
            'Indorsed By:',
            $liq->liquidation_report_indorsed_by_supervisor ?? '',
            '(Supervisor)',
            $this->d($liq->liquidation_report_indorsed_by_date ?? null)
        );
        $this->wordSignature(
            $section,
            'Recommending Approval:',
            $liq->liquidation_report_recommending_approval ?? '',
            '(Recommending Approval)',
            ''
        );

        $filename = ($liq->liquidation_report_form_number ?? 'liquidation') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'liq');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    private function excelSignatureBlock($sheet, int $row, string $title, $name, string $roleLabel, string $date, array $bottomBorder): void
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("E{$row}", 'Date');
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", $name ?? '');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($bottomBorder);
        $sheet->setCellValue("E{$row}", $date);
        $sheet->getStyle("E{$row}")->applyFromArray($bottomBorder);
        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $sheet->setCellValue("A{$row}", $roleLabel);
        $sheet->getStyle("A{$row}")->getFont()->setSize(9)->setItalic(true);
    }

    private function wordSignature($section, string $title, $name, string $roleLabel, string $date): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 40, 'unit' => TblWidth::TWIP, 'width' => 9500]);
        $table->addRow();
        $table->addCell(6500)->addText($title, ['bold' => true, 'size' => 10]);
        $table->addCell(3000)->addText($date !== '' ? 'Date: ' . $date : 'Date:', ['size' => 10]);

        $table->addRow();
        $nameCell = $table->addCell(6500, [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        $nameCell->addText((string) ($name ?? ''), ['size' => 10]);
        $table->addCell(3000)->addText('');

        $table->addRow();
        $table->addCell(6500)->addText($roleLabel, ['size' => 8, 'italic' => true]);
        $table->addCell(3000)->addText('');

        $section->addTextBreak(1);
    }

    private function wordPair($table, $l1, $v1, $l2, $v2): void
    {
        $table->addRow();
        $table->addCell(2400)->addText($l1 . ':', ['bold' => true, 'size' => 9]);
        $table->addCell(2600)->addText((string) $v1, ['size' => 9]);
        $table->addCell(2600)->addText($l2 !== '' ? $l2 . ':' : '', ['bold' => true, 'size' => 9]);
        $table->addCell(1900)->addText((string) $v2, ['size' => 9], ['alignment' => Jc::END]);
    }

    protected function d($value, string $format = 'd/m/Y'): string
    {
        return $this->formatDate($value, $format);
    }
}
