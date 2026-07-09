<?php

namespace App\Services;

use App\Models\Product;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFallbackImageService
{
    public function __construct(private ProductWatermarkService $watermark)
    {
    }

    public function generate(Product $product, int $needed): array
    {
        $needed = max(0, min(3, $needed));

        if ($needed === 0) {
            return [];
        }

        $paths = [];

        for ($index = 1; $index <= $needed; $index++) {
            $image = $this->canvas();
            $this->drawFrame($image, $index);
            $this->drawVisual($image, $product, $index);
            $this->drawMeta($image, $product, $index);
            $this->watermark->apply($image);

            $safeSku = Str::slug($product->sku) ?: 'product-'.$product->id;
            $path = "products/fallback/{$safeSku}/{$safeSku}-fallback-{$index}.webp";
            Storage::disk('public')->put($path, $this->encode($image));
            imagedestroy($image);

            $paths[] = Storage::url($path);
        }

        return $paths;
    }

    private function canvas(): GdImage
    {
        $image = imagecreatetruecolor(1200, 1200);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        return $image;
    }

    private function drawFrame(GdImage $image, int $variant): void
    {
        $blue = imagecolorallocate($image, 0, 87, 217);
        $navy = imagecolorallocate($image, 7, 24, 42);
        $orange = imagecolorallocate($image, 255, 106, 0);
        $pale = imagecolorallocate($image, 241, 246, 253);
        $line = imagecolorallocate($image, 214, 226, 242);

        imagefilledrectangle($image, 0, 0, 1200, 1200, $pale);
        imagefilledrectangle($image, 72, 72, 1128, 1128, imagecolorallocate($image, 255, 255, 255));
        imagerectangle($image, 72, 72, 1128, 1128, $line);

        if ($variant === 1) {
            imagefilledrectangle($image, 72, 72, 1128, 108, $blue);
            imagefilledrectangle($image, 72, 1092, 360, 1128, $orange);
        } elseif ($variant === 2) {
            imagefilledrectangle($image, 72, 72, 108, 1128, $navy);
            imagefilledrectangle($image, 1092, 72, 1128, 420, $orange);
        } else {
            imagefilledrectangle($image, 72, 72, 1128, 108, $navy);
            imagefilledrectangle($image, 840, 1092, 1128, 1128, $blue);
        }
    }

    private function drawVisual(GdImage $image, Product $product, int $variant): void
    {
        $source = $variant === 2
            ? $this->imageFromPath($product->brand?->logo)
            : $this->imageFromPath($product->category?->image);

        if (! $source) {
            $source = $this->imageFromPath('/images/products/product-placeholder-toolbox.svg');
        }

        if (! $source) {
            $this->drawToolShape($image, $variant);

            return;
        }

        $maxWidth = $variant === 2 ? 560 : 700;
        $maxHeight = $variant === 2 ? 360 : 620;
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($maxWidth / max(1, $sourceWidth), $maxHeight / max(1, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $x = (int) ((1200 - $targetWidth) / 2);
        $y = $variant === 2 ? 250 : 180;

        imagecopyresampled($image, $source, $x, $y, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($source);
    }

    private function drawToolShape(GdImage $image, int $variant): void
    {
        $blue = imagecolorallocate($image, 0, 87, 217);
        $navy = imagecolorallocate($image, 20, 34, 49);
        $orange = imagecolorallocate($image, 255, 106, 0);
        imagefilledrectangle($image, 330, 380, 870, 520, $navy);
        imagefilledrectangle($image, 430, 520, 570, 780, $navy);
        imagefilledellipse($image, 840, 450, 120, 120, $blue);
        imagefilledrectangle($image, 780, 410, 915, 490, $orange);

        if ($variant === 3) {
            imagefilledellipse($image, 600, 820, 520, 70, imagecolorallocate($image, 223, 231, 242));
        }
    }

    private function drawMeta(GdImage $image, Product $product, int $variant): void
    {
        $navy = imagecolorallocate($image, 7, 24, 42);
        $blue = imagecolorallocate($image, 0, 87, 217);
        $gray = imagecolorallocate($image, 91, 105, 122);
        $orange = imagecolorallocate($image, 255, 106, 0);
        $brand = $this->ascii($product->brand?->name ?: 'MasterScule');
        $sku = $this->ascii($product->sku);
        $category = $this->ascii($product->category?->slug ?: 'catalog');
        $title = $variant === 1 ? 'PRODUCT PHOTO PENDING' : ($variant === 2 ? 'SKU '.$sku : 'MASTER SCULE CATALOG');

        imagestring($image, 5, 112, 910, $title, $navy);
        imagestring($image, 5, 112, 952, $brand, $blue);
        imagestring($image, 5, 112, 994, 'SKU: '.$sku, $orange);
        imagestring($image, 4, 112, 1034, 'Category: '.$category, $gray);
        imagestring($image, 3, 112, 1072, 'Temporary image. Review before final publication.', $gray);
    }

    private function imageFromPath(?string $path): ?GdImage
    {
        if (! $path || Str::endsWith(Str::lower($path), '.svg')) {
            return null;
        }

        $fullPath = Str::startsWith($path, '/storage/')
            ? storage_path('app/public/'.Str::after($path, '/storage/'))
            : public_path(ltrim($path, '/'));

        if (! is_file($fullPath)) {
            return null;
        }

        return match (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION))) {
            'png' => @imagecreatefrompng($fullPath) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($fullPath) ?: null) : null,
            'jpg', 'jpeg' => @imagecreatefromjpeg($fullPath) ?: null,
            default => null,
        };
    }

    private function ascii(string $value): string
    {
        $value = trim(preg_replace('/[^\x20-\x7E]/', '', $value) ?: '');

        return Str::limit($value !== '' ? $value : 'MasterScule', 46, '');
    }

    private function encode(GdImage $image): string
    {
        ob_start();
        imagewebp($image, null, 88);

        return (string) ob_get_clean();
    }
}
