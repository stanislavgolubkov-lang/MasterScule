<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Str;

class ProductWatermarkService
{
    public function __construct(private ProductParserSettings $settings)
    {
    }

    public function apply(GdImage $image): bool
    {
        $config = $this->settings->get('watermark', []);

        if (! ($config['enabled'] ?? true)) {
            return false;
        }

        $opacity = max(8, min(20, (int) ($config['opacity'] ?? 14)));
        $sizePercent = max(12, min(25, (int) ($config['size_percent'] ?? 18)));
        $position = (string) ($config['position'] ?? 'bottom_right');
        $logoPath = public_path(ltrim((string) ($config['file'] ?? '/images/brand/master-scule-logo.png'), '/'));

        if (is_file($logoPath) && ($logo = @imagecreatefrompng($logoPath))) {
            $this->applyLogo($image, $logo, $position, $opacity, $sizePercent);
            imagedestroy($logo);

            return true;
        }

        $this->applyText($image, $position, $opacity);

        return true;
    }

    private function applyLogo(GdImage $image, GdImage $logo, string $position, int $opacity, int $sizePercent): void
    {
        $targetWidth = (int) (imagesx($image) * ($sizePercent / 100));
        $ratio = imagesy($logo) / max(1, imagesx($logo));
        $targetHeight = max(1, (int) ($targetWidth * $ratio));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $logo, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($logo), imagesy($logo));

        [$x, $y] = $this->position(imagesx($image), imagesy($image), $targetWidth, $targetHeight, $position);
        imagecopymerge($image, $resized, $x, $y, 0, 0, $targetWidth, $targetHeight, $opacity);
        imagedestroy($resized);
    }

    private function applyText(GdImage $image, string $position, int $opacity): void
    {
        $text = 'MasterScule.md';
        $font = 5;
        $width = imagefontwidth($font) * strlen($text);
        $height = imagefontheight($font);
        [$x, $y] = $this->position(imagesx($image), imagesy($image), $width, $height, $position);
        $alpha = 127 - (int) round(127 * ($opacity / 100));
        $color = imagecolorallocatealpha($image, 0, 87, 217, $alpha);

        imagestring($image, $font, $x, $y, $text, $color);
    }

    private function position(int $imageWidth, int $imageHeight, int $markWidth, int $markHeight, string $position): array
    {
        $margin = max(24, (int) ($imageWidth * 0.035));

        return match (Str::lower($position)) {
            'center' => [(int) (($imageWidth - $markWidth) / 2), (int) (($imageHeight - $markHeight) / 2)],
            'bottom_left' => [$margin, $imageHeight - $markHeight - $margin],
            default => [$imageWidth - $markWidth - $margin, $imageHeight - $markHeight - $margin],
        };
    }
}
