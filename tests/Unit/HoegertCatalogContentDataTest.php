<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HoegertCatalogContentDataTest extends TestCase
{
    public function test_catalog_content_contains_only_valid_exact_sku_records(): void
    {
        $path = dirname(__DIR__, 2).'/database/data/hoegert-2025-catalog-content.json';
        $records = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(370, $records);

        foreach ($records as $sku => $content) {
            $this->assertMatchesRegularExpression('/^HT[A-Z0-9-]+$/', $sku);
            $this->assertStringContainsString($sku, $content['name_ru']);
            $this->assertStringContainsString($sku, $content['name_ro']);
            $this->assertStringContainsString($sku, $content['description_ru']);
            $this->assertStringContainsString($sku, $content['description_ro']);
            $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', $content['name_ro'].' '.$content['description_ro']);
            $this->assertGreaterThanOrEqual(20, mb_strlen($content['description_ru']));
            $this->assertGreaterThanOrEqual(20, mb_strlen($content['description_ro']));
            $this->assertSame('en.hoegert.com', parse_url($content['source_url'], PHP_URL_HOST));
            $this->assertSame(substr_count($content['name_ru'], '('), substr_count($content['name_ru'], ')'));
            $this->assertSame(substr_count($content['name_ro'], '('), substr_count($content['name_ro'], ')'));
        }
    }
}
