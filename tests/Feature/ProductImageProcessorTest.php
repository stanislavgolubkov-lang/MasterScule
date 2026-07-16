<?php

namespace Tests\Feature;

use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\ProductImageProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_image_creates_webp_main_preview_thumb_and_watermark(): void
    {
        UploadedFile::fake()->image('source.png', 800, 600)
            ->storeAs('parser-fixtures', 'source.png', 'public');

        $batch = ProductParserBatch::create(['title' => 'Image processor test']);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'TEST-1',
            'brand' => 'Test Brand',
        ]);
        $asset = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => '/storage/parser-fixtures/source.png',
            'source_domain' => 'local-test',
            'is_selected' => true,
            'is_main' => true,
        ]);

        app(ProductImageProcessorService::class)->processSelected($item);

        $asset->refresh();
        $this->assertSame('processed', $asset->status);
        $this->assertSame('image/webp', $asset->mime_type);
        $this->assertTrue($asset->has_watermark);
        $this->assertImageDimensions($asset->processed_path, 1200, 1200);
        $this->assertImageDimensions($asset->preview_path, 600, 600);
        $this->assertImageDimensions($asset->thumb_path, 300, 300);

        Storage::disk('public')->deleteDirectory('parser-fixtures');
        Storage::disk('public')->deleteDirectory('products/official/test-brand/test-1');
    }

    public function test_broken_selected_image_requires_review_without_failing_the_item(): void
    {
        $batch = ProductParserBatch::create(['title' => 'Broken image test']);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'BROKEN-1',
            'brand' => 'Test Brand',
            'status' => 'searching',
            'needs_image_review' => true,
        ]);
        $asset = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => '/storage/parser-fixtures/missing.png',
            'source_domain' => 'local-test',
            'is_selected' => true,
            'is_main' => true,
        ]);

        app(ProductImageProcessorService::class)->processSelected($item);

        $this->assertSame('failed', $asset->fresh()->status);
        $this->assertSame('ready_for_review', $item->fresh()->status);
        $this->assertTrue($item->fresh()->needs_image_review);
        $this->assertNull($item->fresh()->error_message);
    }

    private function assertImageDimensions(?string $publicPath, int $width, int $height): void
    {
        $this->assertNotNull($publicPath);
        $path = storage_path('app/public/'.ltrim(str_replace('/storage/', '', $publicPath), '/'));
        $this->assertFileExists($path);
        [$actualWidth, $actualHeight] = getimagesize($path);
        $this->assertSame($width, $actualWidth);
        $this->assertSame($height, $actualHeight);
    }
}
