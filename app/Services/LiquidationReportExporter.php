<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiquidationReportExporter
{
    public function downloadExcel(array $data)
    {
        $liq = $data['liq'];
        $items = $data['items'];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liquidation');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'LIQUIDATION REPORT For CASH ADVANCES');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Employee Name:');
        $sheet->setCellValue('B3', $liq->liquidation_report_employee_name);
        $sheet->setCellValue('E3', 'Date Of Activity End:');
        $sheet->setCellValue('F3', $this->d($liq->liquidation_report_activity_end_date));

        $sheet->setCellValue('A4', 'Cheque Number:');
        $sheet->setCellValue('B4', $liq->liquidation_report_cheque_number);
        $sheet->setCellValue('E4', 'Deadline For The Liquidation Submissions:');
        $sheet->setCellValue('F4', $this->d($liq->liquidation_report_submission_deadline));

        $sheet->setCellValue('A5', 'Purpose:');
        $sheet->setCellValue('B5', $liq->liquidation_report_purpose);
        $sheet->setCellValue('E5', 'Date Submitted:');
        $sheet->setCellValue('F5', $this->d($liq->liquidation_report_date_submitted));

        $sheet->setCellValue('A6', 'Amount:');
        $sheet->setCellValue('B6', $this->n($liq->liquidation_report_amount_advance));
        $sheet->setCellValue('E6', 'No. Of Days Lapsed:');
        $sheet->setCellValue('F6', $liq->liquidation_report_days_lapse);

        $sheet->setCellValue('A7', 'Date Released:');
        $sheet->setCellValue('B7', $this->d($liq->liquidation_report_date_released));
        $sheet->setCellValue('E7', 'Other Income:');
        $sheet->setCellValue('F7', $this->n($liq->liquidation_report_other_income));

        $sheet->setCellValue('A8', 'Charge to Expense/Refundable Account:');
        $sheet->setCellValue('B8', $liq->liquidation_report_charge_to_account ?? '');

        $sheet->setCellValue('A10', 'PARTICULAR / Breakdown For Cash Advances');
        $sheet->setCellValue('B10', 'AMOUNT');
        $sheet->setCellValue('C10', 'ACTUAL EXPENSES Amount');
        $sheet->setCellValue('D10', 'Total Amount');
        $sheet->setCellValue('E10', 'Variance');
        $sheet->setCellValue('F10', 'REF.No.');
        $sheet->getStyle('A10:F10')->getFont()->setBold(true);
        $sheet->getStyle('A10:F10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $row = 11;
        $totalAdvance = 0;
        $totalActual = 0;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$row}", $item->liquidation_item_particulars);
            $sheet->setCellValue("B{$row}", (float) $item->liquidation_item_particulars_amount);
            $sheet->setCellValue("C{$row}", (float) $item->liquidation_item_actual_breakdown_amount);
            $sheet->setCellValue("D{$row}", (float) $item->liquidation_item_actual_total_amount);
            $sheet->setCellValue("E{$row}", (float) $item->liquidation_item_variance);
            $sheet->setCellValue("F{$row}", $item->liquidation_item_ref_no);
            $totalAdvance += (float) $item->liquidation_item_particulars_amount;
            $totalActual += (float) $item->liquidation_item_actual_total_amount;
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'Total Cash Advance');
        $sheet->setCellValue("B{$row}", $totalAdvance);
        $sheet->setCellValue("C{$row}", 'Total Actual Expense');
        $sheet->setCellValue("D{$row}", $totalActual);
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Note:');
        $row++;
        $sheet->setCellValue("A{$row}", 'Amount Advanced:');
        $sheet->setCellValue("B{$row}", $this->n($liq->liquidation_report_summary_amt_advanced));
        $row++;
        $sheet->setCellValue("A{$row}", 'Total Actual Expense:');
        $sheet->setCellValue("B{$row}", $this->n($liq->liquidation_report_summary_actual_expense));
        $row++;
        $sheet->setCellValue("A{$row}", 'Balance:');
        $sheet->setCellValue("B{$row}", $this->n($liq->liquidation_report_summary_balance));
        $sheet->setCellValue("C{$row}", 'Cash Returned Under-Off:');
        $sheet->setCellValue("D{$row}", $liq->liquidation_report_cash_returned_or_no);
        $row += 3;

        $sheet->setCellValue("A{$row}", 'Submitted By:');
        $sheet->setCellValue("B{$row}", $liq->liquidation_report_submitted_by_signature);
        $sheet->setCellValue("C{$row}", $this->d($liq->liquidation_report_submitted_by_date));
        $row++;
        $sheet->setCellValue("A{$row}", 'Checked By:');
        $sheet->setCellValue("B{$row}", $liq->liquidation_report_checked_by_accountant);
        $sheet->setCellValue("C{$row}", $this->d($liq->liquidation_report_checked_by_date));
        $row++;
        $sheet->setCellValue("A{$row}", 'Indorsed By:');
        $sheet->setCellValue("B{$row}", $liq->liquidation_report_indorsed_by_supervisor);
        $sheet->setCellValue("C{$row}", $this->d($liq->liquidation_report_indorsed_by_date));
        $row++;
        $sheet->setCellValue("A{$row}", 'Recommending Approval:');
        $sheet->setCellValue("B{$row}", $liq->liquidation_report_recommending_approval);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = ($liq->liquidation_report_form_number ?? 'liquidation') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadWord(array $data)
    {
        $liq = $data['liq'];
        $items = $data['items'];
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('LIQUIDATION REPORT For CASH ADVANCES', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        $meta = $section->addTable(['borderSize' => 0, 'cellMargin' => 40]);
        $this->wordPair($meta, 'Employee Name', $liq->liquidation_report_employee_name, 'Date Of Activity End', $this->d($liq->liquidation_report_activity_end_date));
        $this->wordPair($meta, 'Cheque Number', $liq->liquidation_report_cheque_number, 'Deadline For The Liquidation Submissions', $this->d($liq->liquidation_report_submission_deadline));
        $this->wordPair($meta, 'Purpose', $liq->liquidation_report_purpose, 'Date Submitted', $this->d($liq->liquidation_report_date_submitted));
        $this->wordPair($meta, 'Amount', $this->n($liq->liquidation_report_amount_advance), 'No. Of Days Lapsed', (string) ($liq->liquidation_report_days_lapse ?? ''));
        $this->wordPair($meta, 'Date Released', $this->d($liq->liquidation_report_date_released), 'Other Income', $this->n($liq->liquidation_report_other_income));
        $this->wordPair($meta, 'Charge to Expense/Refundable Account', $liq->liquidation_report_charge_to_account ?? '', '', '');

        $section->addTextBreak();
        $table = $section->addTable(['borderSize' => 6, 'cellMargin' => 50]);
        $table->addRow();
        foreach (['PARTICULAR / Breakdown', 'AMOUNT', 'Actual Amount', 'Total Amount', 'Variance', 'REF.No.'] as $h) {
            $table->addCell(1800)->addText($h, ['bold' => true, 'size' => 9]);
        }
        foreach ($items as $item) {
            $table->addRow();
            $table->addCell(1800)->addText((string) $item->liquidation_item_particulars, ['size' => 9]);
            $table->addCell(1800)->addText($this->n($item->liquidation_item_particulars_amount), ['size' => 9]);
            $table->addCell(1800)->addText($this->n($item->liquidation_item_actual_breakdown_amount), ['size' => 9]);
            $table->addCell(1800)->addText($this->n($item->liquidation_item_actual_total_amount), ['size' => 9]);
            $table->addCell(1800)->addText($this->n($item->liquidation_item_variance), ['size' => 9]);
            $table->addCell(1800)->addText((string) $item->liquidation_item_ref_no, ['size' => 9]);
        }

        $section->addTextBreak();
        $section->addText('Amount Advanced: ' . $this->n($liq->liquidation_report_summary_amt_advanced));
        $section->addText('Total Actual Expense: ' . $this->n($liq->liquidation_report_summary_actual_expense));
        $section->addText('Balance: ' . $this->n($liq->liquidation_report_summary_balance));
        $section->addText('Cash Returned Under-Off: ' . ($liq->liquidation_report_cash_returned_or_no ?? ''));
        $section->addTextBreak();
        $section->addText('Submitted By: ' . ($liq->liquidation_report_submitted_by_signature ?? '') . '  ' . $this->d($liq->liquidation_report_submitted_by_date));
        $section->addText('Checked By: ' . ($liq->liquidation_report_checked_by_accountant ?? '') . '  ' . $this->d($liq->liquidation_report_checked_by_date));
        $section->addText('Indorsed By: ' . ($liq->liquidation_report_indorsed_by_supervisor ?? '') . '  ' . $this->d($liq->liquidation_report_indorsed_by_date));
        $section->addText('Recommending Approval: ' . ($liq->liquidation_report_recommending_approval ?? ''));

        $filename = ($liq->liquidation_report_form_number ?? 'liquidation') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'liq');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    private function wordPair($table, $l1, $v1, $l2, $v2): void
    {
        $table->addRow();
        $table->addCell(2800)->addText($l1, ['size' => 9]);
        $table->addCell(2800)->addText((string) $v1, ['size' => 9]);
        $table->addCell(2800)->addText($l2, ['size' => 9]);
        $table->addCell(2800)->addText((string) $v2, ['size' => 9]);
    }

    private function d($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('m/d/Y') : '';
    }

    private function n($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return number_format((float) $value, 2);
    }
}
