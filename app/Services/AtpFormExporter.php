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

class AtpFormExporter
{
    use FormExporterHelpers;
    public static function blankData(): array
    {
        return [
            'atp' => (object) [
                'authority_purchase_form_number' => '',
                'authority_purchase_date' => null,
                'supplier_display_name' => '',
                'company_name' => '',
                'shop_name' => '',
                'supplier_store_type' => '',
                'authority_purchase_received_by_name' => '',
                'authority_purchase_reference_po_no' => '',
                'authority_purchase_authorized_by_signature' => '',
            ],
            'items' => collect(),
        ];
    }

    /**
     * @param  array|object|null  $atpOrData
     * @param  mixed  $items
     */
    public function downloadExcel($atpOrData = null, $items = null)
    {
        $data = $this->resolveExportData($atpOrData, $items, 'atp', fn () => self::blankData());
        $atp = $data['atp'];
        $items = collect($data['items'] ?? []);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Authority to Purchase');
        $borderAll = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $bottom = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]];

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'STI COLLEGE ORMOC, INC.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Centrum Mall, Aviles Street, Ormoc City');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'AUTHORITY TO PURCHASE');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('D5', 'No.');
        $sheet->setCellValue('E5', $atp->authority_purchase_form_number ?? '');
        $sheet->getStyle('E5')->applyFromArray($bottom);
        $sheet->setCellValue('D6', 'Date');
        $sheet->setCellValue('E6', $this->d($atp->authority_purchase_date ?? null));
        $sheet->getStyle('E6')->applyFromArray($bottom);

        $sheet->setCellValue('A8', 'To:');
        $sheet->mergeCells('B8:E8');
        $sheet->setCellValue('B8', $this->supplierName($atp));
        $sheet->getStyle('B8:E8')->applyFromArray($bottom);

        $sheet->mergeCells('A9:E9');
        $sheet->setCellValue('A9', 'Please deliver to bearer the following items chargeable to our account.');

        $sheet->fromArray(['Quantity', 'Unit', 'Description', 'Unit Price', 'Amount'], null, 'A11');
        $sheet->getStyle('A11:E11')->getFont()->setBold(true);
        $sheet->getStyle('A11:E11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A11:E11')->applyFromArray($borderAll);

        $row = 12;
        $total = 0.0;
        foreach ($items as $item) {
            $amount = (float) ($item->atp_amount ?? 0);
            $total += $amount;
            $sheet->setCellValue("A{$row}", $item->atp_quantity ?? '');
            $sheet->setCellValue("B{$row}", $item->atp_unit ?? '');
            $sheet->setCellValue("C{$row}", $item->atp_description ?? '');
            $sheet->setCellValue("D{$row}", isset($item->atp_unit_price) ? (float) $item->atp_unit_price : null);
            $sheet->setCellValue("E{$row}", $amount);
            $sheet->getStyle("D{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        $minRows = max(8, $items->count());
        while ($row < 12 + $minRows) {
            $row++;
        }
        $sheet->getStyle('A12:E' . ($row - 1))->applyFromArray($borderAll);

        if ($items->isNotEmpty()) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("E{$row}", $total);
            $sheet->getStyle("E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($borderAll);
            $row += 2;
        } else {
            $row += 1;
        }

        $sheet->setCellValue("A{$row}", 'RECEIVED BY:');
        $sheet->setCellValue("D{$row}", 'Authorized by');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", $atp->authority_purchase_received_by_name ?? '');
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($bottom);
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("D{$row}", $this->plainName($atp->authority_purchase_authorized_by_signature ?? ''));
        $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($bottom);
        $row++;
        $sheet->setCellValue("A{$row}", 'Signature over Printed Name');
        $sheet->getStyle("A{$row}")->getFont()->setSize(8)->setItalic(true);
        $row += 2;
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", $atp->authority_purchase_reference_po_no ?? '');
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($bottom);
        $row++;
        $sheet->setCellValue("A{$row}", 'Reference P.O. No.');
        $sheet->getStyle("A{$row}")->getFont()->setSize(8)->setItalic(true);

        foreach (['A' => 14, 'B' => 12, 'C' => 36, 'D' => 14, 'E' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = (($atp->authority_purchase_form_number ?? '') ?: 'blank-atp') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @param  array|object|null  $atpOrData
     * @param  mixed  $items
     */
    public function downloadWord($atpOrData = null, $items = null)
    {
        $data = $this->resolveExportData($atpOrData, $items, 'atp', fn () => self::blankData());
        $atp = $data['atp'];
        $items = collect($data['items'] ?? []);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 700,
            'marginBottom' => 700,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $section->addText('STI COLLEGE ORMOC, INC.', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addText('Centrum Mall, Aviles Street, Ormoc City', ['size' => 9], ['alignment' => Jc::CENTER]);
        $section->addText('AUTHORITY TO PURCHASE', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        $meta = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 9000]);
        $meta->addRow();
        $meta->addCell(5500)->addText('');
        $meta->addCell(1500)->addText('No.', ['bold' => true, 'size' => 10]);
        $meta->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText((string) ($atp->authority_purchase_form_number ?? ''), ['size' => 10]);
        $meta->addRow();
        $meta->addCell(5500)->addText('');
        $meta->addCell(1500)->addText('Date', ['bold' => true, 'size' => 10]);
        $meta->addCell(2000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->d($atp->authority_purchase_date ?? null), ['size' => 10]);

        $section->addTextBreak(1);
        $to = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 9000]);
        $to->addRow();
        $to->addCell(800)->addText('To:', ['bold' => true, 'size' => 10]);
        $to->addCell(8200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->supplierName($atp), ['size' => 10]);
        $section->addTextBreak(1);
        $section->addText('Please deliver to bearer the following items chargeable to our account.', ['size' => 10]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => 9000,
        ]);
        $h = ['bold' => true, 'size' => 9];
        $c = ['alignment' => Jc::CENTER];
        $right = ['alignment' => Jc::END];
        $table->addRow();
        foreach ([['Quantity', 1200], ['Unit', 1200], ['Description', 3600], ['Unit Price', 1500], ['Amount', 1500]] as [$label, $w]) {
            $table->addCell($w)->addText($label, $h, $c);
        }

        $body = ['size' => 9];
        $total = 0.0;
        foreach ($items as $item) {
            $amount = (float) ($item->atp_amount ?? 0);
            $total += $amount;
            $table->addRow();
            $table->addCell(1200)->addText((string) ($item->atp_quantity ?? ''), $body, $c);
            $table->addCell(1200)->addText((string) ($item->atp_unit ?? ''), $body, $c);
            $table->addCell(3600)->addText((string) ($item->atp_description ?? ''), $body);
            $table->addCell(1500)->addText(isset($item->atp_unit_price) ? number_format((float) $item->atp_unit_price, 2) : '', $body, $right);
            $table->addCell(1500)->addText(number_format($amount, 2), $body, $right);
        }
        for ($i = $items->count(); $i < 8; $i++) {
            $table->addRow();
            foreach ([1200, 1200, 3600, 1500, 1500] as $w) {
                $table->addCell($w)->addText('', $body);
            }
        }
        if ($items->isNotEmpty()) {
            $table->addRow();
            $table->addCell(7500, ['gridSpan' => 4])->addText('TOTAL', ['bold' => true, 'size' => 9], $right);
            $table->addCell(1500)->addText(number_format($total, 2), ['bold' => true, 'size' => 9], $right);
        }

        $section->addTextBreak(2);
        $sigs = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 9000]);
        $sigs->addRow();
        $sigs->addCell(5000)->addText('RECEIVED BY:', ['bold' => true, 'size' => 10]);
        $sigs->addCell(4000)->addText('Authorized by', ['size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addRow();
        $sigs->addCell(5000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText((string) ($atp->authority_purchase_received_by_name ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->plainName($atp->authority_purchase_authorized_by_signature ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addRow();
        $sigs->addCell(5000)->addText('Signature over Printed Name', ['size' => 8, 'italic' => true], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4000)->addText('');
        $sigs->addRow();
        $sigs->addCell(5000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText((string) ($atp->authority_purchase_reference_po_no ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4000)->addText('');
        $sigs->addRow();
        $sigs->addCell(5000)->addText('Reference P.O. No.', ['size' => 8, 'italic' => true], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4000)->addText('');

        $filename = (($atp->authority_purchase_form_number ?? '') ?: 'blank-atp') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'atp');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    private function supplierName(object $atp): string
    {
        if (!empty($atp->supplier_display_name)) {
            return (string) $atp->supplier_display_name;
        }
        if (($atp->supplier_store_type ?? '') === 'Physical Store') {
            return (string) ($atp->company_name ?? '');
        }

        return (string) ($atp->shop_name ?? $atp->company_name ?? '');
    }
}
