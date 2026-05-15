<?php

namespace App\Support\Relatorios;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxExporter
{
    public function download(string $fileName, string $title, array $summaryRows, array $sections)
    {
        $spreadsheet = new Spreadsheet();

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');
        $this->populateSummarySheet($summarySheet, $title, $summaryRows);

        foreach ($sections as $section) {
            $sheet = new Worksheet($spreadsheet, $this->sanitizeSheetTitle((string) ($section['sheet_title'] ?? $section['title'] ?? 'Detalhes')));
            $spreadsheet->addSheet($sheet);
            $this->populateSectionSheet($sheet, $section);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function populateSummarySheet(Worksheet $sheet, string $title, array $summaryRows): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $row = 3;
        foreach ($summaryRows as [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:B{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($row % 2 === 0 ? 'FFF9FAFB' : 'FFFFFFFF');
            $row++;
        }

        $sheet->freezePane('A3');
        $this->autoSizeColumns($sheet, 2);
    }

    private function populateSectionSheet(Worksheet $sheet, array $section): void
    {
        $columns = Collection::make($section['columns'] ?? []);
        $rows = Collection::make($section['rows'] ?? []);
        $columnCount = max($columns->count(), 1);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $sheet->setCellValue('A1', (string) ($section['title'] ?? 'Detalhes'));
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headerRow = 3;
        foreach ($columns->values() as $index => $column) {
            $cell = Coordinate::stringFromColumnIndex($index + 1).$headerRow;
            $sheet->setCellValue($cell, (string) ($column['label'] ?? 'Coluna'));
        }

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF3F4F6');
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getAlignment()->setWrapText(true);

        $dataRow = 4;
        foreach ($rows as $rowValues) {
            foreach (array_values($rowValues) as $index => $value) {
                $cell = Coordinate::stringFromColumnIndex($index + 1).$dataRow;
                $sheet->setCellValue($cell, (string) $value);
            }
            $dataRow++;
        }

        if ($dataRow > 4) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}".($dataRow - 1));
            $sheet->getStyle("A4:{$lastColumn}".($dataRow - 1))->getAlignment()->setWrapText(true);
        }

        $sheet->freezePane('A4');
        $this->autoSizeColumns($sheet, $columnCount);
    }

    private function autoSizeColumns(Worksheet $sheet, int $columnCount): void
    {
        for ($index = 1; $index <= $columnCount; $index++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $clean = str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $title);
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? 'Detalhes');

        return mb_substr($clean !== '' ? $clean : 'Detalhes', 0, 31);
    }
}