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
    private const MAX_REMOTE_IMAGE_BYTES = 16777216;

    private const ALLOWED_REMOTE_IMAGE_DOMAINS = [
        'kingtony.com',
        'mighty-seven.com',
        'jtc.com.tw',
        'jtcautotools.com',
        'hoegert.com',
        'clickoutil.com',
        'groupe-mlv-france.fr',
        'gys.com.ua',
        'gys-ukraine.com',
        'gysusa.com',
        'gysweldingusa.com',
        'torinjacks.com',
        'torin-usa.com',
        'tongrunjacks.com',
        'thefastimg.com',
        'images.prom.ua',
    ];

    public function __construct(
        private ProductParserSettings $settings,
        private ProductWatermarkService $watermark,
    ) {}

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

        $hasProcessedImages = $processed !== [];
        $errorMessage = trim((string) $item->error_message);
        if ($errorMessage === trim((string) __('ui.parser_images_failed'))) {
            $errorMessage = '';
        }

        $item->forceFill([
            'processed_images_json' => array_values(array_filter($processed)),
            'status' => $hasProcessedImages
                ? 'ready_for_review'
                : ($item->needs_category_review ? 'needs_category_review' : 'ready_for_review'),
            'needs_image_review' => ! $hasProcessedImages,
            'error_message' => $hasProcessedImages || $errorMessage === '' ? null : $errorMessage,
        ])->save();

        $item->batch?->addLog('Processed parser images', ['sku' => $item->sku, 'count' => count($processed)]);
    }

    private function processAsset(ProductParserImageAsset $asset, ProductParserItem $item, int $index): string
    {
        $isTrisToolsHost = $this->isFallbackRemoteImageHost(
            Str::lower((string) parse_url($asset->source_url, PHP_URL_HOST))
        );
        $isTrisToolsPrimary = $isTrisToolsHost && $item->image_source_type === 'tristools_primary';
        $isFallbackAsset = $isTrisToolsHost && ! $isTrisToolsPrimary;

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
        $previewSize = max(300, (int) $this->settings->get('preview_size', 600));
        $thumbSize = max(150, (int) $this->settings->get('thumb_size', 300));
        $quality = max(75, min(95, (int) $this->settings->get('webp_quality', 88)));
        $main = $this->squareCanvas($source, $size);
        $hasWatermark = $this->watermark->apply($main);
        $preview = $this->squareCanvas($source, $previewSize);
        $thumb = $index === 0 ? $this->squareCanvas($source, $thumbSize) : null;
        imagedestroy($source);

        $safeSku = Str::slug($item->sku) ?: 'sku';
        $brandDir = Str::slug($item->brand ?: 'unknown-brand') ?: 'unknown-brand';
        $sourceDir = $isFallbackAsset ? 'fallback' : ($isTrisToolsPrimary ? 'tristools' : 'official');
        $baseDir = "products/{$sourceDir}/{$brandDir}/{$safeSku}";
        $suffix = $index === 0 ? 'main' : 'gallery-'.$index;
        $processedPath = "{$baseDir}/{$safeSku}-{$suffix}.webp";
        $previewPath = "{$baseDir}/{$safeSku}-{$suffix}-preview.webp";
        $thumbPath = $index === 0 ? "{$baseDir}/{$safeSku}-thumb.webp" : null;

        Storage::disk('public')->put($processedPath, $this->encodeWebp($main, $quality));
        Storage::disk('public')->put($previewPath, $this->encodeWebp($preview, $quality));
        if ($thumbPath && $thumb instanceof GdImage) {
            Storage::disk('public')->put($thumbPath, $this->encodeWebp($thumb, $quality));
        }

        imagedestroy($main);
        imagedestroy($preview);
        if ($thumb instanceof GdImage) {
            imagedestroy($thumb);
        }

        $publicProcessedPath = Storage::url($processedPath);

        $asset->forceFill([
            'original_path' => null,
            'processed_path' => $publicProcessedPath,
            'preview_path' => Storage::url($previewPath),
            'thumb_path' => $thumbPath ? Storage::url($thumbPath) : null,
            'width' => $size,
            'height' => $size,
            'mime_type' => 'image/webp',
            'status' => 'processed',
            'has_watermark' => $hasWatermark,
            'background_removed' => false,
            'background_removal_failed' => false,
            'needs_review' => $isFallbackAsset,
            'error_message' => null,
        ])->save();

        return $publicProcessedPath;
    }

    private function readSource(string $sourceUrl): array
    {
        if (Str::startsWith($sourceUrl, '/storage/')) {
            return $this->readLocalSource(storage_path('app/public'), Str::after($sourceUrl, '/storage/'));
        }

        if (Str::startsWith($sourceUrl, '/')) {
            return $this->readLocalSource(public_path(), ltrim($sourceUrl, '/'));
        }

        $this->assertSafeRemoteImageUrl($sourceUrl);

        $response = $this->externalHttp()->timeout(25)->retry(1, 500)->get($sourceUrl);

        if (! $response->successful() || $response->body() === '') {
            throw new \RuntimeException('Remote image download failed.');
        }

        $contentLength = (int) ($response->header('Content-Length') ?: 0);
        if ($contentLength > self::MAX_REMOTE_IMAGE_BYTES || strlen($response->body()) > self::MAX_REMOTE_IMAGE_BYTES) {
            throw new \RuntimeException('Remote image is too large.');
        }

        $contentType = Str::lower((string) $response->header('Content-Type'));
        if (! Str::contains($contentType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            throw new \RuntimeException('Remote image content type is not allowed.');
        }

        return [$response->body(), $response->header('Content-Type') ?: 'application/octet-stream'];
    }

    private function readLocalSource(string $basePath, string $relativePath): array
    {
        $base = realpath($basePath);
        $path = realpath($basePath.DIRECTORY_SEPARATOR.$relativePath);

        if (! $base || ! $path || ! is_file($path)) {
            throw new \RuntimeException('Local image file not found.');
        }

        $normalizedBase = rtrim(str_replace('\\', '/', $base), '/').'/';
        $normalizedPath = str_replace('\\', '/', $path);

        if (! str_starts_with($normalizedPath, $normalizedBase)) {
            throw new \RuntimeException('Local image path is not allowed.');
        }

        return [file_get_contents($path), mime_content_type($path) ?: 'application/octet-stream'];
    }

    private function assertSafeRemoteImageUrl(string $sourceUrl): void
    {
        $parts = parse_url($sourceUrl);
        $scheme = Str::lower((string) ($parts['scheme'] ?? ''));
        $host = Str::lower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if ($scheme !== 'https' || $host === '') {
            throw new \RuntimeException('Remote image URL must use HTTPS.');
        }

        if (! $this->isAllowedRemoteImageHost($host)) {
            throw new \RuntimeException('Remote image host is not allowed.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            throw new \RuntimeException('Remote image host could not be resolved.');
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException('Remote image host resolved to a private address.');
            }
        }
    }

    private function isAllowedRemoteImageHost(string $host): bool
    {
        $official = collect(self::ALLOWED_REMOTE_IMAGE_DOMAINS)
            ->contains(fn (string $domain) => $host === $domain || Str::endsWith($host, '.'.$domain));

        return $official || $this->isFallbackRemoteImageHost($host);
    }

    private function isFallbackRemoteImageHost(string $host): bool
    {
        if (! $this->settings->get('tristools_fallback_enabled', false)) {
            return false;
        }

        $fallbackHost = Str::lower((string) parse_url(
            (string) $this->settings->get('tristools.base_url', 'https://tristool.md'),
            PHP_URL_HOST
        ));

        return $fallbackHost !== ''
            && ($host === $fallbackHost || Str::endsWith($host, '.'.$fallbackHost));
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
                $alpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

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
        ]);
    }
}
