<?php

declare(strict_types=1);

use App\Services\ProductWatermarkService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dataset = $argv[1] ?? 'king-tony';
if (! preg_match('/^[a-z0-9-]+$/', $dataset)) {
    throw new InvalidArgumentException('Dataset name may contain only lowercase letters, numbers, and hyphens.');
}

$dataFile = database_path("data/reviewed-{$dataset}-page-crops.php");
if (! is_file($dataFile)) {
    throw new RuntimeException("Reviewed page-crop dataset not found: {$dataset}");
}

$records = require $dataFile;
$outputRoot = public_path("images/catalog-reviewed/{$dataset}-page-crops");
$temporaryRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR."masterscule-reviewed-{$dataset}-page-crops";
@mkdir($temporaryRoot, 0777, true);

function pageCropSquareCanvas(GdImage $source, int $size): GdImage
{
    $canvas = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    $scale = min(($size * 0.88) / imagesx($source), ($size * 0.88) / imagesy($source));
    $width = max(1, (int) round(imagesx($source) * $scale));
    $height = max(1, (int) round(imagesy($source) * $scale));
    imagecopyresampled($canvas, $source, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, imagesx($source), imagesy($source));

    return $canvas;
}

$watermark = app(ProductWatermarkService::class);
$results = [];

foreach ($records as $sku => $record) {
    $slug = Str::slug($sku);
    $temporaryFile = $temporaryRoot.DIRECTORY_SEPARATOR.$slug.'.source';
    if (! is_file($temporaryFile) || filesize($temporaryFile) === 0) {
        $process = proc_open(
            ['curl.exe', '-L', '--fail', '--silent', '--show-error', '--max-time', '45', '--output', $temporaryFile, $record['source_url']],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || ! is_file($temporaryFile)) {
            throw new RuntimeException("{$sku}: download failed: ".trim($stderr ?: $stdout));
        }
    }

    $bytes = file_get_contents($temporaryFile);
    $page = $bytes === false ? false : @imagecreatefromstring($bytes);
    if (! $page instanceof GdImage) {
        throw new RuntimeException("{$sku}: source page is not a decodable image.");
    }

    [$x, $y, $width, $height] = $record['crop'];
    $crop = imagecrop($page, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
    imagedestroy($page);
    if (! $crop instanceof GdImage || imagesx($crop) < 180 || imagesy($crop) < 90) {
        throw new RuntimeException("{$sku}: configured crop is invalid or too small.");
    }

    $directory = $outputRoot.DIRECTORY_SEPARATOR.$slug;
    @mkdir($directory, 0777, true);
    $main = pageCropSquareCanvas($crop, 1200);
    $watermark->apply($main);
    $preview = pageCropSquareCanvas($crop, 600);
    $thumb = pageCropSquareCanvas($crop, 300);
    imagedestroy($crop);

    imagewebp($main, $directory.DIRECTORY_SEPARATOR.$slug.'-main.webp', 88);
    imagewebp($preview, $directory.DIRECTORY_SEPARATOR.$slug.'-preview.webp', 88);
    imagewebp($thumb, $directory.DIRECTORY_SEPARATOR.$slug.'-thumb.webp', 88);
    imagedestroy($main);
    imagedestroy($preview);
    imagedestroy($thumb);

    $results[$sku] = ['source' => $record['source_url'], 'crop' => $record['crop']];
}

echo json_encode(['processed' => count($results), 'items' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
