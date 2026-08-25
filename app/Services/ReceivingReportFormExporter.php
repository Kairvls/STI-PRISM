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

class ReceivingReportFormExporter
{
    use FormExporterHelpers;
    public static function blankData(): array
    {
        return [
            'rr' => (object) [
                'receiving_report_form_number' => '',
                'receiving_report_date' => null,
                'receiving_report_received_from' => '',
                'receiving_report_supplier_address_override' => '',
                'receiving_report_invoice_no' => '',
                'receiving_report_dr_no' => '',
                'receiving_report_delivery_date' => null,
                'receiving_report_received_by_signature' => '',
                'receiving_report_second_count_signature' => '',
                'receiving_report_second_count_by' => '',
            ],
            'items' => collect(),
        ];
    }

    /**
     * @param  array|object|null  $rrOrData
     * @param  mixed  $items
     */
    public function downloadExcel($rrOrData = null, $items = null)
    {
        $data = $this->resolveExportData($rrOrData, $items, 'rr', fn () => self::blankData());
        $rr = $data['rr'];
        $items = collect($data['items'] ?? []);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Receiving Report');
        $borderAll = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $bottom = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]];

        $sheet->setCellValue('A1', '№ ' . (($rr->receiving_report_form_number ?? '') ?: '______'));
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'STI-College - ORMOC, INC.');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Ormoc City');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A4:C4');
        $sheet->setCellValue('A4', 'RECEIVING REPORT');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setUnderline(true)->setSize(12);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('C5', 'Date: ' . $this->d($rr->receiving_report_date ?? null));

        $sheet->setCellValue('A7', 'Received from:');
        $sheet->setCellValue('B7', $rr->receiving_report_received_from ?? '');
        $sheet->getStyle('B7')->applyFromArray($bottom);
        $sheet->setCellValue('A8', 'Address:');
        $sheet->setCellValue('B8', $rr->receiving_report_supplier_address_override ?? '');
        $sheet->getStyle('B8')->applyFromArray($bottom);

        $sheet->setCellValue('A9', 'Refer Invoice No.:');
        $sheet->setCellValue('B9', $rr->receiving_report_invoice_no ?? '');
        $sheet->getStyle('B9')->applyFromArray($bottom);
        $sheet->setCellValue('A10', 'D.R. No.:');
        $sheet->setCellValue('B10', $rr->receiving_report_dr_no ?? '');
        $sheet->getStyle('B10')->applyFromArray($bottom);
        $sheet->setCellValue('A11', 'Delivery Date:');
        $sheet->setCellValue('B11', $this->d($rr->receiving_report_delivery_date ?? null));
        $sheet->getStyle('B11')->applyFromArray($bottom);

        $sheet->setCellValue('A13', 'Received the following items:');
        $sheet->setCellValue('A14', 'QUANTITY');
        $sheet->setCellValue('B14', 'UNIT');
        $sheet->setCellValue('C14', 'ARTICLE');
        $sheet->getStyle('A14:C14')->getFont()->setBold(true);
        $sheet->getStyle('A14:C14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A14:C14')->applyFromArray($borderAll);

        $row = 15;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$row}", $item->receiving_report_item_quantity ?? '');
            $sheet->setCellValue("B{$row}", $item->receiving_report_item_unit ?? '');
            $sheet->setCellValue("C{$row}", $item->receiving_report_item_article ?? '');
            $row++;
        }
        $minRows = max(10, $items->count());
        while ($row < 15 + $minRows) {
            $row++;
        }
        $sheet->getStyle('A15:C' . ($row - 1))->applyFromArray($borderAll);

        $row += 2;
        $sheet->setCellValue("A{$row}", 'Second Count:');
        $sheet->setCellValue("C{$row}", 'Received by:');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", $this->plainName($rr->receiving_report_second_count_signature ?? $rr->receiving_report_second_count_by ?? ''));
        $sheet->getStyle("A{$row}")->applyFromArray($bottom);
        $sheet->setCellValue("C{$row}", $rr->receiving_report_received_by_signature ?? '');
        $sheet->getStyle("C{$row}")->applyFromArray($bottom);

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(40);

        $filename = (($rr->receiving_report_form_number ?? '') ?: 'blank-rr') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @param  array|object|null  $rrOrData
     * @param  mixed  $items
     */
    public function downloadWord($rrOrData = null, $items = null)
    {
        $data = $this->resolveExportData($rrOrData, $items, 'rr', fn () => self::blankData());
        $rr = $data['rr'];
        $items = collect($data['items'] ?? []);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 700,
            'marginBottom' => 700,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $section->addText('№ ' . (($rr->receiving_report_form_number ?? '') ?: '______'), ['bold' => true, 'size' => 10, 'color' => '990000']);
        $section->addText('STI-College - ORMOC, INC.', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addText('Ormoc City', ['size' => 10], ['alignment' => Jc::CENTER]);
        $section->addText('RECEIVING REPORT', ['bold' => true, 'underline' => 'single', 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $section->addText('Date: ' . $this->d($rr->receiving_report_date ?? null), ['size' => 10], ['alignment' => Jc::END]);

        foreach ([
            ['Received from:', $rr->receiving_report_received_from ?? ''],
            ['Address:', $rr->receiving_report_supplier_address_override ?? ''],
            ['Refer Invoice No.:', $rr->receiving_report_invoice_no ?? ''],
            ['D.R. No.:', $rr->receiving_report_dr_no ?? ''],
            ['Delivery Date:', $this->d($rr->receiving_report_delivery_date ?? null)],
        ] as [$label, $value]) {
            $t = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 9000]);
            $t->addRow();
            $t->addCell(2500)->addText($label, ['size' => 10]);
            $t->addCell(6500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])->addText((string) $value, ['size' => 10]);
        }

        $section->addTextBreak(1);
        $section->addText('Received the following items:', ['size' => 10]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'unit' => TblWidth::TWIP,
            'width' => 9000,
        ]);
        $h = ['bold' => true, 'size' => 9];
        $c = ['alignment' => Jc::CENTER];
        $table->addRow();
        $table->addCell(1800)->addText('QUANTITY', $h, $c);
        $table->addCell(1800)->addText('UNIT', $h, $c);
        $table->addCell(5400)->addText('ARTICLE', $h, $c);

        $body = ['size' => 9];
        foreach ($items as $item) {
            $table->addRow();
            $table->addCell(1800)->addText((string) ($item->receiving_report_item_quantity ?? ''), $body, $c);
            $table->addCell(1800)->addText((string) ($item->receiving_report_item_unit ?? ''), $body, $c);
            $table->addCell(5400)->addText((string) ($item->receiving_report_item_article ?? ''), $body);
        }
        for ($i = $items->count(); $i < 10; $i++) {
            $table->addRow();
            $table->addCell(1800)->addText('', $body);
            $table->addCell(1800)->addText('', $body);
            $table->addCell(5400)->addText('', $body);
        }

        $section->addTextBreak(2);
        $sigs = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 9000]);
        $sigs->addRow();
        $sigs->addCell(4500)->addText('Second Count:', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4500)->addText('Received by:', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addRow();
        $sigs->addCell(4500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->plainName($rr->receiving_report_second_count_signature ?? $rr->receiving_report_second_count_by ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
        $sigs->addCell(4500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText((string) ($rr->receiving_report_received_by_signature ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);

        $filename = (($rr->receiving_report_form_number ?? '') ?: 'blank-rr') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'rr');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    protected function d($value, string $format = 'M d, Y'): string
    {
        return $this->formatDate($value, $format);
    }
}
