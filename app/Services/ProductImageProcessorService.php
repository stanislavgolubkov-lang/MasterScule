<?php

namespace App\Services;

use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use GdImage;
use Illuminate\Http\Client\PendingRequest;
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
        if (Str::contains(Str::lower($asset->source_url), 'tristool.')) {
            throw new \RuntimeException('TrisTool media is not allowed in the official image pipeline.');
        }

        [$bytes, $mime] = $this->readSource($asset->source_url);
        $source = @imagecreatefromstring($bytes);

        if (! $source instanceof GdImage) {
            throw new \RuntimeException('Unsupported or broken image source.');
        }

        if (imagesx($source) < 220 || imagesy($source) < 220) {
            imagedestroy($source);

            throw new \RuntimeException('Official image source is too small for a product card.');
        }

        $size = max(600, (int) $this->settings->get('image_size', 1200));
        $thumbSize = max(150, (int) $this->settings->get('thumb_size', 300));
        $quality = max(75, min(95, (int) $this->settings->get('webp_quality', 88)));
        $main = $this->squareCanvas($source, $size);
        $hasWatermark = $this->watermark->apply($main);
        $thumb = $index === 0 ? $this->squareCanvas($source, $thumbSize) : null;
        imagedestroy($source);

        $safeSku = Str::slug($item->sku) ?: 'sku';
        $brandDir = Str::slug($item->brand ?: 'unknown-brand') ?: 'unknown-brand';
        $baseDir = 'products/official/'.$brandDir.'/'.$safeSku;
        $suffix = $index === 0 ? 'main' : 'gallery-'.$index;
        $processedPath = "{$baseDir}/{$safeSku}-{$suffix}.webp";
        $thumbPath = $index === 0 ? "{$baseDir}/{$safeSku}-thumb.webp" : null;

        Storage::disk('public')->put($processedPath, $this->encodeWebp($main, $quality));
        if ($thumbPath && $thumb instanceof GdImage) {
            Storage::disk('public')->put($thumbPath, $this->encodeWebp($thumb, $quality));
        }

        imagedestroy($main);
        if ($thumb instanceof GdImage) {
            imagedestroy($thumb);
        }

        $publicProcessedPath = Storage::url($processedPath);

        $asset->forceFill([
            'original_path' => null,
            'processed_path' => $publicProcessedPath,
            'thumb_path' => $thumbPath ? Storage::url($thumbPath) : null,
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

        $response = $this->externalHttp()->timeout(20)->retry(1, 350)->get($sourceUrl);

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

        [$sourceX, $sourceY, $sourceWidth, $sourceHeight] = $this->contentBounds($source);
        $targetMax = (int) ($size * 0.84);
        $scale = min($targetMax / max(1, $sourceWidth), $targetMax / max(1, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $x = (int) (($size - $targetWidth) / 2);
        $y = (int) (($size - $targetHeight) / 2);

        imagecopyresampled($canvas, $source, $x, $y, $sourceX, $sourceY, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $canvas;
    }

    private function contentBounds(GdImage $source): array
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $step = max(1, (int) floor(max($width, $height) / 500));
        $left = $width;
        $top = $height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba >> 24) & 0x7f;
                $red = ($rgba >> 16) & 0xff;
                $green = ($rgba >> 8) & 0xff;
                $blue = $rgba & 0xff;

                if ($alpha >= 118 || ($red >= 246 && $green >= 246 && $blue >= 246)) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        if ($right < $left || $bottom < $top) {
            return [0, 0, $width, $height];
        }

        $detectedWidth = $right - $left + 1;
        $detectedHeight = $bottom - $top + 1;

        if (($detectedWidth * $detectedHeight) < ($width * $height * 0.015)) {
            return [0, 0, $width, $height];
        }

        $padding = max(4, (int) round(max($detectedWidth, $detectedHeight) * 0.06));
        $left = max(0, $left - $padding);
        $top = max(0, $top - $padding);
        $right = min($width - 1, $right + $padding);
        $bottom = min($height - 1, $bottom + $padding);

        return [$left, $top, $right - $left + 1, $bottom - $top + 1];
    }

    private function encodeWebp(GdImage $image, int $quality): string
    {
        ob_start();
        imagewebp($image, null, $quality);

        return (string) ob_get_clean();
    }

    private function externalHttp(): PendingRequest
    {
        return Http::withOptions([
            'proxy' => '',
            'verify' => false,
        ]);
    }
}
