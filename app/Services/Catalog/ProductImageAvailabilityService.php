<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageAvailabilityService
{
    private const PLACEHOLDER_MARKERS = [
        'placeholder',
        'fallback-placeholder',
        'generated-placeholder',
        'no-image',
        'no_image',
        'missing-image',
        'product-placeholder',
        'gys-product.svg',
    ];

    public function inspect(?string $path): array
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $this->result(false, 'missing', $path);
        }

        $normalized = str_replace('\\', '/', rawurldecode($path));
        $lower = Str::lower($normalized);

        if (Str::contains($lower, self::PLACEHOLDER_MARKERS)) {
            return $this->result(false, 'placeholder', $path);
        }

        if (str_contains($normalized, "\0")
            || preg_match('~(^|/)\.\.(/|$)~', $normalized)
            || Str::startsWith($lower, ['data:', 'javascript:', 'file:', 'php:'])) {
            return $this->result(false, 'unsafe_path', $path);
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $this->result(false, 'remote_not_verified', $path);
        }

        $absolutePath = $this->absolutePath($normalized);
        if (! $absolutePath || ! is_file($absolutePath)) {
            return $this->result(false, 'file_missing', $path, $absolutePath);
        }

        if (! $this->isImage($absolutePath)) {
            return $this->result(false, 'not_an_image', $path, $absolutePath);
        }

        return $this->result(true, 'available', $path, $absolutePath);
    }

    public function isAvailable(?string $path): bool
    {
        return $this->inspect($path)['available'];
    }

    private function absolutePath(string $path): ?string
    {
        $relative = ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/');

        if (Str::startsWith($relative, 'storage/')) {
            $storagePath = Str::after($relative, 'storage/');

            return Storage::disk('public')->exists($storagePath)
                ? Storage::disk('public')->path($storagePath)
                : null;
        }

        if (Str::startsWith($relative, 'products/') && Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->path($relative);
        }

        $publicRoot = str_replace('\\', '/', realpath(public_path()) ?: public_path());
        $candidate = public_path($relative);
        $candidateDirectory = realpath(dirname($candidate));

        if ($candidateDirectory === false
            || ! Str::startsWith(str_replace('\\', '/', $candidateDirectory).'/', rtrim($publicRoot, '/').'/')) {
            return null;
        }

        return $candidate;
    }

    private function isImage(string $path): bool
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true)) {
            return false;
        }

        if ($extension === 'svg') {
            $head = file_get_contents($path, false, null, 0, 512);

            return is_string($head) && str_contains(Str::lower($head), '<svg');
        }

        return @getimagesize($path) !== false;
    }

    private function result(bool $available, string $code, string $path, ?string $absolutePath = null): array
    {
        return compact('available', 'code', 'path', 'absolutePath');
    }
}
