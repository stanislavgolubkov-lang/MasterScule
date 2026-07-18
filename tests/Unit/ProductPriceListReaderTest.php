<?php

namespace Tests\Unit;

use App\Services\ProductPriceListReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PHPUnit\Framework\TestCase;

class ProductPriceListReaderTest extends TestCase
{
    public function test_reader_keeps_the_last_stock_column_in_legacy_xls_files(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'price-list-').'.xls';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('TDSheet');
        $sheet->fromArray([
            ['Артикул', 'Наименование', 'ОтпускЦена', 'Остаток'],
            ['SKU-1', 'Товар', 100, 7],
        ]);
        (new Xls($spreadsheet))->save($path);

        try {
            $result = (new ProductPriceListReader)->read($path, 'xls');

            $this->assertSame(3, $result['mapping']['stock']);
            $this->assertSame('7', $result['rows'][0]['stock']);
            $this->assertSame('7', $result['rows'][0]['raw']['Остаток']);
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($path);
        }
    }
}
