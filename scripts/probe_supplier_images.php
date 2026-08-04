<?php

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

function fetchUrl(string $url): Illuminate\Http\Client\Response
{
    return Http::withOptions(['proxy' => ''])
        ->withHeaders(['User-Agent' => 'MasterSculeImageProbe/1.0'])
        ->timeout(30)
        ->get($url);
}

$spin = fetchUrl('https://www.spinsrl.it/prodotti/?swoof=1&woof_sku=05.090.66');
echo "SPIN {$spin->status()} ".strlen($spin->body()).PHP_EOL;
preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>([\s\S]*?)<\/a>/iu', $spin->body(), $links, PREG_SET_ORDER);
foreach ($links as $link) {
    $url = html_entity_decode((string) ($link[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(strip_tags((string) ($link[2] ?? '')));
    if (str_contains($url, 'spinsrl.it') && (str_contains($url, '/product') || str_contains($url, '/prodot'))) {
        echo "LINK {$url} | ".mb_substr($text, 0, 180).PHP_EOL;
    }
}
preg_match_all('/(?:href|src)=["\']([^"\']+\.pdf(?:\?[^"\']*)?)["\']/iu', $spin->body(), $pdfLinks);
foreach (array_unique($pdfLinks[1] ?? []) as $pdfLink) {
    echo "SPIN_PDF {$pdfLink}".PHP_EOL;
}

foreach (['https://www.spinsrl.it/prodotti/', 'https://www.spinsrl.it/cataloghi/'] as $catalogUrl) {
    $catalog = fetchUrl($catalogUrl);
    echo "SPIN_CATALOG {$catalog->status()} ".strlen($catalog->body())." {$catalogUrl}".PHP_EOL;
    preg_match_all('/(?:href|src)=["\']([^"\']+\.pdf(?:\?[^"\']*)?)["\']/iu', $catalog->body(), $catalogPdfs);
    foreach (array_unique($catalogPdfs[1] ?? []) as $pdfLink) {
        echo "SPIN_PDF {$pdfLink}".PHP_EOL;
    }
}

$telwin = fetchUrl('https://www.telwin.com/intl/en/generate-pdf/816169');
echo "TELWIN {$telwin->status()} ".strlen($telwin->body()).' '.$telwin->header('Content-Type').PHP_EOL;
echo substr($telwin->body(), 0, 120).PHP_EOL;
if ($telwin->successful()) {
    $directory = dirname(__DIR__).'/tmp/pdfs';
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    file_put_contents($directory.'/telwin-816169.pdf', $telwin->body());
}

foreach ([
    'https://www.telwin.com/intl/en/search?query=816169',
    'https://www.telwin.com/intl/en/products?search=816169',
    'https://www.telwin.com/intl/en/product/816169',
] as $url) {
    $response = fetchUrl($url);
    echo "TRY {$response->status()} ".strlen($response->body())." {$url}".PHP_EOL;
    preg_match_all('/https?:\\?\/\\?\/[^"\'\s<>]+\.(?:jpe?g|png|webp)/iu', $response->body(), $images);
    foreach (array_slice(array_unique($images[0] ?? []), 0, 5) as $image) {
        echo "IMAGE ".str_replace('\\/', '/', $image).PHP_EOL;
    }
}
