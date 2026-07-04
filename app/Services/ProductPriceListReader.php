<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ProductPriceListReader
{
    private const COLUMN_SYNONYMS = [
        'sku' => ['артикул', 'sku', 'код', 'код товара', 'part number', 'item code', 'product code'],
        'name' => ['наименование', 'название', 'товар', 'product name', 'name', 'description'],
        'price' => ['отпускцена', 'отпуск цена', 'цена', 'retail', 'retail price', 'розница', 'цена розница'],
        'stock' => ['остаток', 'stock', 'qty', 'quantity', 'наличие', 'количество'],
        'brand' => ['бренд', 'brand', 'производитель', 'manufacturer'],
        'group' => ['группа', 'group', 'category', 'категория'],
        'subgroup' => ['подгруппа', 'subgroup', 'subcategory', 'подкатегория'],
    ];

    public function read(string $path, string $extension): array
    {
        $worksheet = $this->worksheet($path, $extension);
        $matrix = $this->matrix($worksheet);

        if ($matrix === []) {
            throw new RuntimeException(__('ui.parser_price_file_empty'));
        }

        [$headerIndex, $mapping, $headers] = $this->detectHeader($matrix);
        $rows = [];

        foreach ($matrix as $index => $values) {
            if ($index <= $headerIndex) {
                continue;
            }

            $raw = [];
            foreach ($headers as $columnIndex => $header) {
                $raw[$header ?: 'column_'.$columnIndex] = $values[$columnIndex] ?? null;
            }

            $rows[] = [
                'row_number' => $index + 1,
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

        return [
            'sheet' => $worksheet->getTitle(),
            'headers' => $headers,
            'mapping' => $mapping,
            'total_rows' => max(0, count($matrix) - $headerIndex - 1),
            'rows' => $rows,
        ];
    }

    private function worksheet(string $path, string $extension): Worksheet
    {
        $reader = IOFactory::createReaderForFile($path);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if ($reader instanceof Csv) {
            $reader->setDelimiter($this->detectDelimiter($path));
            $reader->setEnclosure('"');
            $reader->setInputEncoding('UTF-8');
        }

        $spreadsheet = $reader->load($path);

        return $spreadsheet->getSheetByName('TDSheet') ?: $spreadsheet->getActiveSheet();
    }

    private function matrix(Worksheet $worksheet): array
    {
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        $highestRow = $worksheet->getHighestRow();
        $rows = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            $values = [];

            for ($column = 1; $column <= $highestColumn; $column++) {
                $value = $worksheet->getCell([$column, $row])->getFormattedValue();
                $values[$column - 1] = $this->cleanCell($value);
            }

            if (collect($values)->filter(fn ($value) => $value !== '')->isNotEmpty()) {
                $rows[] = $values;
            }
        }

        return $rows;
    }

    private function detectHeader(array $matrix): array
    {
        $best = ['score' => 0, 'index' => null, 'mapping' => [], 'headers' => []];

        foreach (array_slice($matrix, 0, 40, true) as $index => $row) {
            $mapping = [];

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
                    'index' => $index,
                    'mapping' => $mapping,
                    'headers' => array_map(fn ($value) => $value ?: 'column', $row),
                ];
            }
        }

        if ($best['score'] < 6 || ! isset($best['mapping']['sku'], $best['mapping']['name'])) {
            throw new RuntimeException(__('ui.parser_columns_not_found'));
        }

        return [$best['index'], $best['mapping'], $best['headers']];
    }

    private function value(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim((string) ($row[$index] ?? ''));

        return $value === '' ? null : $value;
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
        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $counts = [
            ';' => substr_count($sample, ';'),
            ',' => substr_count($sample, ','),
            "\t" => substr_count($sample, "\t"),
        ];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
