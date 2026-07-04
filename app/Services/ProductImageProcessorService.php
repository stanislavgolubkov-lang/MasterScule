<?php

namespace App\Services;

use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductImageProcessorService
{
    public function __construct(
        private ProductParserSettings $settings,
        private ProductWatermarkService $watermark,
    ) {
    }

    public function processSelected(ProductParserItem $item): void
    {
        $item->forceFill(['status' => 'processing_images'])->save();
        $processed = [];

        foreach ($item->imageAssets()->where('is_selected', true)->orderByDesc('is_main')->orderBy('id')->get() as $index => $asset) {
            try {
                $processed[] = $this->processAsset($asset, $item, $index);
            } catch (Throwable $e) {
                $asset->forceFill([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ])->save();
            }
        }

        $item->forceFill([
            'processed_images_json' => array_values(array_filter($processed)),
            'status' => $processed ? 'ready_for_review' : 'failed',
            'error_message' => $processed ? null : __('ui.parser_images_failed'),
        ])->save();

        $item->batch?->addLog('Processed parser images', ['sku' => $item->sku, 'count' => count($processed)]);
    }

    private function processAsset(ProductParserImageAsset $asset, ProductParserItem $item, int $index): string
    {
        [$bytes, $mime] = $this->readSource($asset->source_url);
        $source = @imagecreatefromstring($bytes);

        if (! $source instanceof GdImage) {
            throw new \RuntimeException('Unsupported or broken image source.');
        }

        $size = max(600, (int) $this->settings->get('image_size', 1200));
        $thumbSize = max(150, (int) $this->settings->get('thumb_size', 300));
        $quality = max(75, min(95, (int) $this->settings->get('webp_quality', 88)));
        $main = $this->squareCanvas($source, $size);
        $hasWatermark = $this->watermark->apply($main);
        $thumb = $this->squareCanvas($source, $thumbSize);
        imagedestroy($source);

        $safeSku = Str::slug($item->sku) ?: 'sku';
        $baseDir = 'parser/imports/'.($item->batch_id ?: 'manual').'/'.$safeSku;
        $suffix = $index === 0 ? 'main' : 'gallery-'.$index;
        $originalPath = "{$baseDir}/original/{$safeSku}-original-{$index}.bin";
        $processedPath = "{$baseDir}/processed/{$safeSku}-{$suffix}.webp";
        $thumbPath = "{$baseDir}/processed/{$safeSku}-thumb".($index === 0 ? '' : '-'.$index).".webp";

        Storage::disk('public')->put($originalPath, $bytes);
        Storage::disk('public')->put($processedPath, $this->encodeWebp($main, $quality));
        Storage::disk('public')->put($thumbPath, $this->encodeWebp($thumb, $quality));

        imagedestroy($main);
        imagedestroy($thumb);

        $publicProcessedPath = Storage::url($processedPath);

        $asset->forceFill([
            'original_path' => Storage::url($originalPath),
            'processed_path' => $publicProcessedPath,
            'thumb_path' => Storage::url($thumbPath),
            'width' => $size,
            'height' => $size,
            'mime_type' => 'image/webp',
            'status' => 'processed',
            'has_watermark' => $hasWatermark,
            'background_removed' => false,
            'background_removal_failed' => false,
            'needs_review' => false,
            'error_message' => null,
        ])->save();

        return $publicProcessedPath;
    }

    private function readSource(string $sourceUrl): array
    {
        if (Str::startsWith($sourceUrl, '/storage/')) {
            $path = storage_path('app/public/'.Str::after($sourceUrl, '/storage/'));

            if (! is_file($path)) {
                throw new \RuntimeException('Local storage image file not found.');
            }

            return [file_get_contents($path), mime_content_type($path) ?: 'application/octet-stream'];
        }

        if (Str::startsWith($sourceUrl, '/')) {
            $path = public_path(ltrim($sourceUrl, '/'));

            if (! is_file($path)) {
                throw new \RuntimeException('Local image file not found.');
            }

            return [file_get_contents($path), mime_content_type($path) ?: 'application/octet-stream'];
        }

        $response = Http::timeout(20)->retry(1, 350)->get($sourceUrl);

        if (! $response->successful() || $response->body() === '') {
            throw new \RuntimeException('Remote image download failed.');
        }

        return [$response->body(), $response->header('Content-Type') ?: 'application/octet-stream'];
    }

    private function squareCanvas(GdImage $source, int $size): GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetMax = (int) ($size * 0.84);
        $scale = min($targetMax / max(1, $sourceWidth), $targetMax / max(1, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $x = (int) (($size - $targetWidth) / 2);
        $y = (int) (($size - $targetHeight) / 2);

        imagecopyresampled($canvas, $source, $x, $y, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $canvas;
    }

    private function encodeWebp(GdImage $image, int $quality): string
    {
        ob_start();
        imagewebp($image, null, $quality);

        return (string) ob_get_clean();
    }
}
