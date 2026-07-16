<?php

namespace App\Services;

use Generator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ProductPriceListReader
{
    private const HEADER_SCAN_NON_EMPTY_ROWS = 40;

    private const SPREADSHEET_CHUNK_SIZE = 1000;

    private const MAX_COLUMNS = 256;

    private const COLUMN_SYNONYMS = [
        'sku' => ['артикул', 'sku', 'код', 'код товара', 'part number', 'item code', 'product code'],
        'name' => ['наименование', 'название', 'товар', 'product name', 'name', 'description'],
        'price' => ['отпускцена', 'отпуск цена', 'цена', 'retail', 'retail price', 'розница', 'цена розница'],
        'stock' => ['остаток', 'stock', 'qty', 'quantity', 'наличие', 'количество'],
        'brand' => ['бренд', 'brand', 'производитель', 'manufacturer'],
        'group' => ['группа', 'group', 'category', 'категория'],
        'subgroup' => ['подгруппа', 'subgroup', 'subcategory', 'подкатегория'],
    ];

    /**
     * Backwards-compatible materialized read for small callers and tests.
     */
    public function read(string $path, string $extension): array
    {
        $result = $this->stream($path, $extension);
        $result['rows'] = iterator_to_array($result['rows'], false);

        return $result;
    }

    /**
     * Inspect the file and return a lazy row iterator. Only one spreadsheet
     * chunk is kept in memory at a time; CSV files are read record by record.
     */
    public function stream(string $path, string $extension): array
    {
        if (strtolower($extension) === 'csv') {
            return $this->streamCsv($path);
        }

        return $this->streamSpreadsheet($path);
    }

    private function streamCsv(string $path): array
    {
        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $candidates = [];
        $nonEmptyRows = 0;
        $physicalRow = 0;

        try {
            while (($values = fgetcsv($handle, null, $delimiter, '"', '\\')) !== false) {
                $physicalRow++;
                $values = $this->cleanCsvRow($values, $physicalRow === 1);

                if (! $this->hasValues($values)) {
                    continue;
                }

                $nonEmptyRows++;
                if (count($candidates) < self::HEADER_SCAN_NON_EMPTY_ROWS) {
                    $candidates[] = ['row_number' => $physicalRow, 'values' => $values];
                }
            }
        } finally {
            fclose($handle);
        }

        if ($nonEmptyRows === 0) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $header = $this->detectHeader($candidates);

        return [
            'sheet' => 'CSV',
            'headers' => $header['headers'],
            'mapping' => $header['mapping'],
            'total_rows' => max(0, $nonEmptyRows - $header['non_empty_index'] - 1),
            'rows' => $this->csvRows($path, $delimiter, $header),
        ];
    }

    private function csvRows(string $path, string $delimiter, array $header): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $physicalRow = 0;

        try {
            while (($values = fgetcsv($handle, null, $delimiter, '"', '\\')) !== false) {
                $physicalRow++;
                if ($physicalRow <= $header['row_number']) {
                    continue;
                }

                $values = $this->cleanCsvRow($values);
                if (! $this->hasValues($values)) {
                    continue;
                }

                yield $this->mappedRow($physicalRow, $values, $header['mapping'], $header['headers']);
            }
        } finally {
            fclose($handle);
        }
    }

    private function streamSpreadsheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $worksheetInfo = $reader->listWorksheetInfo($path);

        if ($worksheetInfo === []) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $sheetInfo = collect($worksheetInfo)->firstWhere('worksheetName', 'TDSheet') ?: $worksheetInfo[0];
        $sheetName = (string) $sheetInfo['worksheetName'];
        $totalPhysicalRows = (int) ($sheetInfo['totalRows'] ?? 0);
        $highestColumn = min(
            self::MAX_COLUMNS,
            (int) ($sheetInfo['lastColumnIndex'] ?? Coordinate::columnIndexFromString((string) ($sheetInfo['lastColumnLetter'] ?? 'A')))
        );

        if ($totalPhysicalRows < 1) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $candidates = [];
        $nonEmptyRows = 0;

        for ($start = 1; $start <= $totalPhysicalRows && count($candidates) < self::HEADER_SCAN_NON_EMPTY_ROWS; $start += self::SPREADSHEET_CHUNK_SIZE) {
            $end = min($totalPhysicalRows, $start + self::SPREADSHEET_CHUNK_SIZE - 1);
            $worksheet = $this->loadSpreadsheetChunk($path, $sheetName, $start, $end);

            for ($rowNumber = $start; $rowNumber <= $end; $rowNumber++) {
                $values = $this->spreadsheetRow($worksheet, $rowNumber, $highestColumn);
                if (! $this->hasValues($values)) {
                    continue;
                }

                $nonEmptyRows++;
                $candidates[] = ['row_number' => $rowNumber, 'values' => $values];

                if (count($candidates) >= self::HEADER_SCAN_NON_EMPTY_ROWS) {
                    break;
                }
            }

            $worksheet->getParent()?->disconnectWorksheets();
            unset($worksheet);
        }

        if ($candidates === []) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        $header = $this->detectHeader($candidates);

        return [
            'sheet' => $sheetName,
            'headers' => $header['headers'],
            'mapping' => $header['mapping'],
            // Spreadsheet metadata counts physical rows; the iterator itself still skips blank rows.
            'total_rows' => max(0, $totalPhysicalRows - $header['row_number']),
            'rows' => $this->spreadsheetRows($path, $sheetName, $totalPhysicalRows, $highestColumn, $header),
        ];
    }

    private function spreadsheetRows(string $path, string $sheetName, int $totalRows, int $highestColumn, array $header): Generator
    {
        $firstRow = $header['row_number'] + 1;

        for ($start = $firstRow; $start <= $totalRows; $start += self::SPREADSHEET_CHUNK_SIZE) {
            $end = min($totalRows, $start + self::SPREADSHEET_CHUNK_SIZE - 1);
            $worksheet = $this->loadSpreadsheetChunk($path, $sheetName, $start, $end);

            try {
                for ($rowNumber = $start; $rowNumber <= $end; $rowNumber++) {
                    $values = $this->spreadsheetRow($worksheet, $rowNumber, $highestColumn);
                    if (! $this->hasValues($values)) {
                        continue;
                    }

                    yield $this->mappedRow($rowNumber, $values, $header['mapping'], $header['headers']);
                }
            } finally {
                $worksheet->getParent()?->disconnectWorksheets();
                unset($worksheet);
            }
        }
    }

    private function loadSpreadsheetChunk(string $path, string $sheetName, int $startRow, int $endRow): Worksheet
    {
        $reader = IOFactory::createReaderForFile($path);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly($sheetName);
        }

        $reader->setReadFilter(new class($startRow, $endRow) implements IReadFilter
        {
            public function __construct(private int $startRow, private int $endRow) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row >= $this->startRow && $row <= $this->endRow;
            }
        });

        $spreadsheet = $reader->load($path);

        return $spreadsheet->getSheetByName($sheetName) ?: $spreadsheet->getActiveSheet();
    }

    private function spreadsheetRow($worksheet, int $rowNumber, int $highestColumn): array
    {
        $values = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $values[$column - 1] = $this->cleanCell($worksheet->getCell([$column, $rowNumber])->getFormattedValue());
        }

        return $values;
    }

    private function detectHeader(array $candidates): array
    {
        $best = ['score' => 0, 'row_number' => null, 'non_empty_index' => null, 'mapping' => [], 'headers' => []];

        foreach ($candidates as $nonEmptyIndex => $candidate) {
            $mapping = [];
            $row = $candidate['values'];

            foreach ($row as $columnIndex => $value) {
                $normalized = $this->normalizeHeader($value);

                foreach (self::COLUMN_SYNONYMS as $field => $synonyms) {
                    if (in_array($normalized, array_map(fn ($item) => $this->normalizeHeader($item), $synonyms), true)) {
                        $mapping[$field] = $columnIndex;
                    }
                }
            }

            $score = (isset($mapping['sku']) ? 3 : 0)
                + (isset($mapping['name']) ? 3 : 0)
                + (isset($mapping['price']) ? 2 : 0)
                + (isset($mapping['stock']) ? 2 : 0);

            if ($score > $best['score']) {
                $best = [
                    'score' => $score,
                    'row_number' => $candidate['row_number'],
                    'non_empty_index' => $nonEmptyIndex,
                    'mapping' => $mapping,
                    'headers' => array_map(fn ($value) => $value ?: 'column', $row),
                ];
            }
        }

        if ($best['score'] < 6 || ! isset($best['mapping']['sku'], $best['mapping']['name'])) {
            throw new RuntimeException(__('ui.parser_columns_not_found'));
        }

        return $best;
    }

    private function mappedRow(int $rowNumber, array $values, array $mapping, array $headers): array
    {
        $raw = [];
        foreach ($headers as $columnIndex => $header) {
            $key = $header ?: 'column_'.$columnIndex;
            if (array_key_exists($key, $raw)) {
                $key .= '_'.$columnIndex;
            }
            $raw[$key] = $values[$columnIndex] ?? null;
        }

        return [
            'row_number' => $rowNumber,
            'sku' => $this->value($values, $mapping['sku'] ?? null),
            'name' => $this->value($values, $mapping['name'] ?? null),
            'price' => $this->value($values, $mapping['price'] ?? null),
            'stock' => $this->value($values, $mapping['stock'] ?? null),
            'brand' => $this->value($values, $mapping['brand'] ?? null),
            'group' => $this->value($values, $mapping['group'] ?? null),
            'subgroup' => $this->value($values, $mapping['subgroup'] ?? null),
            'raw' => $raw,
            'raw_values' => $values,
        ];
    }

    private function value(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim((string) ($row[$index] ?? ''));

        return $value === '' ? null : $value;
    }

    private function cleanCsvRow(array $values, bool $firstRow = false): array
    {
        return array_map(function ($value, $index) use ($firstRow) {
            $value = (string) $value;
            if ($firstRow && $index === 0) {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?: $value;
            }

            if (! mb_check_encoding($value, 'UTF-8')) {
                $encoding = mb_detect_encoding($value, ['Windows-1251', 'ISO-8859-1'], true) ?: 'Windows-1251';
                $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            }

            return $this->cleanCell($value);
        }, $values, array_keys($values));
    }

    private function hasValues(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function cleanCell(mixed $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim($value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return preg_replace('/[\s_\-\.\/:]+/u', '', $value) ?: '';
    }

    private function detectDelimiter(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 8192);
        $counts = [
            ';' => substr_count($sample, ';'),
            ',' => substr_count($sample, ','),
            "\t" => substr_count($sample, "\t"),
        ];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
