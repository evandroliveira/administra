<?php

namespace Tests\Concerns;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

trait InteractsWithGeneratedSpreadsheets
{
    protected function openSpreadsheetFromBinary(string $binary): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx_');

        if ($path === false) {
            throw new RuntimeException('Não foi possível criar arquivo temporário para a planilha.');
        }

        file_put_contents($path, $binary);

        try {
            return IOFactory::load($path);
        } finally {
            @unlink($path);
        }
    }
}