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

class RfcFormExporter
{
    use FormExporterHelpers;
    public static function blankData(): array
    {
        return [
            'rfc' => (object) [
                'request_check_form_number' => '',
                'request_check_date' => null,
                'request_check_payee' => '',
                'request_check_amount_figures' => null,
                'request_check_particulars_purpose' => '',
                'request_check_requested_by' => '',
                'request_check_approved_by_admin' => '',
                'request_check_approved_by_signature' => '',
            ],
        ];
    }

    /**
     * @param  array|object|null  $rfcOrData
     */
    public function downloadExcel($rfcOrData = null)
    {
        $rfc = $this->resolveExportDocument($rfcOrData, 'rfc', fn () => self::blankData());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Request for Check');
        $bottom = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]];

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'STI COLLEGE- ORMOC, INC.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'REQUEST FOR CHECK');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setItalic(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('C4', 'Date:');
        $sheet->setCellValue('D4', $this->d($rfc->request_check_date ?? null));
        $sheet->getStyle('D4')->applyFromArray($bottom);

        $sheet->setCellValue('A6', 'Payee:');
        $sheet->mergeCells('B6:D6');
        $sheet->setCellValue('B6', $rfc->request_check_payee ?? '');
        $sheet->getStyle('B6:D6')->applyFromArray($bottom);

        $sheet->setCellValue('A8', 'Amount:');
        $sheet->mergeCells('B8:D8');
        $amount = $rfc->request_check_amount_figures ?? null;
        if ($amount !== null && $amount !== '') {
            $sheet->setCellValue('B8', (float) $amount);
            $sheet->getStyle('B8')->getNumberFormat()->setFormatCode('"₱"#,##0.00');
        }
        $sheet->getStyle('B8:D8')->applyFromArray($bottom);

        $sheet->setCellValue('A10', 'For:');
        $sheet->mergeCells('B10:D11');
        $sheet->setCellValue('B10', $rfc->request_check_particulars_purpose ?? '');
        $sheet->getStyle('B10:D11')->applyFromArray($bottom)->getAlignment()->setWrapText(true);

        $sheet->setCellValue('A14', 'Requested by:');
        $sheet->setCellValue('C14', 'Approved by:');
        $sheet->getStyle('A14')->getFont()->setBold(true);
        $sheet->getStyle('C14')->getFont()->setBold(true);

        $sheet->mergeCells('A15:B15');
        $sheet->setCellValue('A15', $rfc->request_check_requested_by ?? '');
        $sheet->getStyle('A15:B15')->applyFromArray($bottom);

        $approved = $this->plainName($rfc->request_check_approved_by_signature ?? $rfc->request_check_approved_by_admin ?? '');
        $sheet->mergeCells('C15:D15');
        $sheet->setCellValue('C15', $approved);
        $sheet->getStyle('C15:D15')->applyFromArray($bottom);
        $sheet->setCellValue('C16', 'Administrator');

        foreach (['A' => 16, 'B' => 28, 'C' => 16, 'D' => 28] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = (($rfc->request_check_form_number ?? '') ?: 'blank-rfc') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @param  array|object|null  $rfcOrData
     */
    public function downloadWord($rfcOrData = null)
    {
        $rfc = $this->resolveExportDocument($rfcOrData, 'rfc', fn () => self::blankData());
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 800,
            'marginBottom' => 800,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);

        $section->addText('STI COLLEGE- ORMOC, INC.', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $section->addText('REQUEST FOR CHECK', ['bold' => true, 'italic' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 300]);

        $meta = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 12000]);
        $meta->addRow();
        $meta->addCell(8000)->addText('');
        $meta->addCell(1500)->addText('Date:', ['bold' => true, 'size' => 11]);
        $meta->addCell(2500, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->d($rfc->request_check_date ?? null), ['size' => 11]);

        $section->addTextBreak(2);
        foreach ([
            ['Payee:', $rfc->request_check_payee ?? ''],
            ['Amount:', ($rfc->request_check_amount_figures !== null && $rfc->request_check_amount_figures !== '')
                ? '₱' . number_format((float) $rfc->request_check_amount_figures, 2)
                : ''],
            ['For:', $rfc->request_check_particulars_purpose ?? ''],
        ] as [$label, $value]) {
            $row = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 12000]);
            $row->addRow();
            $row->addCell(1800)->addText($label, ['bold' => true, 'size' => 11]);
            $row->addCell(10200, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
                ->addText((string) $value, ['size' => 11]);
            $section->addTextBreak(1);
        }

        $section->addTextBreak(2);
        $sigs = $section->addTable(['borderSize' => 0, 'unit' => TblWidth::TWIP, 'width' => 12000]);
        $sigs->addRow();
        $sigs->addCell(6000)->addText('Requested by:', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $sigs->addCell(6000)->addText('Approved by:', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $sigs->addRow();
        $sigs->addCell(6000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText((string) ($rfc->request_check_requested_by ?? ''), ['size' => 11], ['alignment' => Jc::CENTER]);
        $sigs->addCell(6000, ['borderBottomSize' => 6, 'borderBottomColor' => '000000'])
            ->addText($this->plainName($rfc->request_check_approved_by_signature ?? $rfc->request_check_approved_by_admin ?? ''), ['size' => 11], ['alignment' => Jc::CENTER]);
        $sigs->addRow();
        $sigs->addCell(6000)->addText('');
        $sigs->addCell(6000)->addText('Administrator', ['size' => 10], ['alignment' => Jc::CENTER]);

        $filename = (($rfc->request_check_form_number ?? '') ?: 'blank-rfc') . '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'rfc');
        $phpWord->save($tmp, 'Word2007');

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }
}
