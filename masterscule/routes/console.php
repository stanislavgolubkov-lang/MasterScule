<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ProductLocalizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('masterscule:import-tristool-products {--king=100} {--m7=50}', function () {
    $targets = [
        [
            'brand_name' => 'King Tony',
            'brand_slug' => 'king-tony',
            'query' => 'King Tony',
            'limit' => max(0, (int) $this->option('king')),
        ],
        [
            'brand_name' => 'M7 / Mighty Seven',
            'brand_slug' => 'm7-mighty-seven',
            'query' => 'Mighty Seven',
            'limit' => max(0, (int) $this->option('m7')),
        ],
    ];

    $totalImported = 0;

    foreach ($targets as $target) {
        $brand = ensureTrisToolBrand($target['brand_name'], $target['brand_slug']);
        $current = Product::where('brand_id', $brand->id)->count();

        if ($current >= $target['limit']) {
            $this->info("{$target['brand_name']}: already has {$current} products, target is {$target['limit']}.");
            continue;
        }

        $page = 1;
        $seen = [];

        $this->info("{$target['brand_name']}: {$current}/{$target['limit']} products. Importing missing products...");

        while ($current < $target['limit'] && $page <= 160) {
            $url = 'https://tristool.md/ru/search?searchword='.rawurlencode($target['query']).'&p='.$page;
            $response = Http::withHeaders([
                'User-Agent' => 'MasterScule.ro product import/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(30)->retry(2, 500)->get($url);

            if (! $response->successful()) {
                $this->warn("Page {$page} failed with HTTP {$response->status()}.");
                $page++;
                continue;
            }

            $cards = parseTrisToolCards($response->body());

            if ($cards === []) {
                $page++;
                continue;
            }

            foreach ($cards as $card) {
                if ($current >= $target['limit']) {
                    break;
                }

                $sku = trim($card['sku']);
                if ($sku === '' || isset($seen[$sku]) || Product::where('sku', $sku)->exists()) {
                    $seen[$sku] = true;
                    continue;
                }

                $title = cleanTrisToolTitle($card['title']);
                if ($title === '') {
                    continue;
                }

                $seen[$sku] = true;
                $category = categoryForTrisToolTitle($title, $target['brand_slug']);
                $image = downloadTrisToolImage($card['image'], $sku, $target['brand_slug']);
                $price = convertMdlToRon($card['price']);
                $oldPrice = (($current + $page) % 7 === 0) ? round($price * 1.12) : null;

                $displayName = ProductLocalizer::name($title, $target['brand_name'], $sku);

                $product = Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'name' => normalizeProductName($title, $target['brand_name']),
                    'name_ro' => $displayName,
                    'slug' => uniqueProductSlug($title, $sku),
                    'sku' => $sku,
                    'short_description' => ProductLocalizer::shortDescription($displayName, $target['brand_name']),
                    'description' => ProductLocalizer::fullDescription($displayName, $target['brand_name'], $sku),
                    'description_ro' => ProductLocalizer::fullDescription($displayName, $target['brand_name'], $sku),
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'currency' => 'RON',
                    'stock_quantity' => 4 + ((crc32($sku) % 18)),
                    'stock_status' => 'in_stock',
                    'main_image' => $image,
                    'gallery' => [$image],
                    'attributes' => attributesForTrisToolTitle($title, $sku, $target['brand_name']),
                    'package_contents' => packageForTrisToolTitle($title),
                    'rating' => 4.5 + ((crc32($sku) % 5) / 10),
                    'reviews_count' => 6 + (crc32($sku) % 48),
                    'is_active' => true,
                    'is_featured' => $current < 16,
                    'is_bestseller' => $current % 6 === 0,
                    'is_new' => $current % 5 === 0,
                    'is_discounted' => $oldPrice !== null,
                    'warranty' => '24 luni',
                    'meta_title' => $displayName.' | MasterScule.ro',
                    'meta_description' => Str::limit(ProductLocalizer::shortDescription($displayName, $target['brand_name']), 150),
                ]);

                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'path' => $image],
                    ['alt' => $product->name, 'sort_order' => 1]
                );

                $current++;
                $totalImported++;
            }

            $this->line("Page {$page}: {$current}/{$target['limit']} {$target['brand_name']} products.");
            $page++;
        }

        if ($current < $target['limit']) {
            $this->warn("{$target['brand_name']}: target not reached. Current count: {$current}.");
        }
    }

    $this->info("Done. Imported {$totalImported} new products.");
})->purpose('Import King Tony and M7 products from TrisTool.md');

Artisan::command('masterscule:localize-products', function () {
    $updated = 0;

    Product::with('brand')->chunkById(100, function ($products) use (&$updated) {
        foreach ($products as $product) {
            $displayName = ProductLocalizer::name($product->name, $product->brand?->name ?? '', $product->sku);
            $description = ProductLocalizer::fullDescription($displayName, $product->brand?->name ?? '', $product->sku);

            $product->forceFill([
                'name_ro' => $displayName,
                'short_description' => ProductLocalizer::shortDescription($displayName, $product->brand?->name ?? ''),
                'description_ro' => $description,
                'description' => $description,
                'meta_title' => $displayName.' | MasterScule.ro',
                'meta_description' => Str::limit(ProductLocalizer::shortDescription($displayName, $product->brand?->name ?? ''), 150),
            ])->save();

            $updated++;
        }
    });

    $this->info("Updated {$updated} product texts in Romanian.");
})->purpose('Normalize product display text to Romanian');

if (! function_exists('ensureTrisToolBrand')) {
function ensureTrisToolBrand(string $name, string $slug): Brand
{
    $logo = $slug === 'king-tony' ? '/images/brand/king-tony.png' : '/images/brand/m7.png';

    return Brand::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => $name,
            'description' => 'Brand profesional de scule si echipamente pentru service auto.',
            'logo' => $logo,
            'is_featured' => true,
            'is_active' => true,
        ]
    );
}

function parseTrisToolCards(string $html): array
{
    preg_match_all(
        '/<a class="cl-item[\s\S]*?href="(?<href>[^"]+)"[\s\S]*?<img[^>]+src="(?<img>[^"]+)"[\s\S]*?<h6[^>]*>(?<title>[\s\S]*?)<\/h6>[\s\S]*?<span class="article"[^>]*>(?<sku>[\s\S]*?)<\/span>[\s\S]*?<span class="item-price"[^>]*>[\s\S]*?(?<price>[0-9 ]+,[0-9]{2}) MDL/i',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    return array_map(fn ($match) => [
        'href' => $match['href'],
        'image' => $match['img'],
        'title' => html_entity_decode(strip_tags($match['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'sku' => html_entity_decode(strip_tags($match['sku']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'price' => $match['price'],
    ], $matches);
}

function cleanTrisToolTitle(string $title): string
{
    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function normalizeProductName(string $title, string $brandName): string
{
    $brand = str_contains($brandName, 'M7') ? 'M7' : 'King Tony';
    $name = trim($title);

    if (! Str::contains(Str::lower($name), Str::lower($brand))) {
        $name .= ' '.$brand;
    }

    return Str::limit($name, 130, '');
}

function uniqueProductSlug(string $title, string $sku): string
{
    $base = Str::slug(Str::limit($title, 70, '').'-'.$sku);

    if ($base === '') {
        $base = Str::slug('produs-'.$sku);
    }

    $slug = $base;
    $index = 2;

    while (Product::where('slug', $slug)->exists()) {
        $slug = $base.'-'.$index++;
    }

    return $slug;
}

function convertMdlToRon(string $price): float
{
    $mdl = (float) str_replace([' ', ','], ['', '.'], $price);

    return max(19, round($mdl * 0.23));
}

function categoryForTrisToolTitle(string $title, string $brandSlug): Category
{
    $lower = mb_strtolower($title, 'UTF-8');
    $slug = match (true) {
        str_contains($lower, 'компресс') => 'compresoare',
        str_contains($lower, 'динамометр') => 'chei-dinamometrice',
        str_contains($lower, 'домкрат') || str_contains($lower, 'подъем') || str_contains($lower, 'подъём') || str_contains($lower, 'стойка') => 'cricuri-si-ridicare',
        str_contains($lower, 'тележ') || str_contains($lower, 'шкаф') || str_contains($lower, 'держател') || str_contains($lower, 'органайзер') => 'dulapuri-si-organizare',
        str_contains($lower, 'пневмат') || str_contains($lower, 'гайков') || str_contains($lower, 'шлиф') || str_contains($lower, 'дрель') || str_contains($lower, 'пила') || $brandSlug === 'm7-mighty-seven' => 'scule-pneumatice',
        str_contains($lower, 'голов') || str_contains($lower, 'насад') || str_contains($lower, 'бит') || str_contains($lower, 'трещ') || str_contains($lower, 'вороток') || str_contains($lower, 'удлин') => 'tubulare-si-clichete',
        str_contains($lower, 'набор') || str_contains($lower, 'комплект') || str_contains($lower, 'кейс') => 'seturi-de-scule',
        default => 'chei-si-surubelnite',
    };

    return Category::where('slug', $slug)->first() ?? Category::firstOrFail();
}

function downloadTrisToolImage(string $source, string $sku, string $brandSlug): string
{
    $extension = strtolower(pathinfo(parse_url($source, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg');
    $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
    $filename = Str::slug($brandSlug.'-'.$sku).'.'.$extension;
    $relative = '/images/products/tristool/'.$brandSlug.'/'.$filename;
    $path = public_path(ltrim($relative, '/'));

    File::ensureDirectoryExists(dirname($path));

    if (! File::exists($path)) {
        $response = Http::timeout(30)->retry(2, 500)->get(tristoolAssetUrl($source));

        if ($response->successful() && $response->body() !== '') {
            File::put($path, $response->body());
        }
    }

    return File::exists($path) ? $relative : '/images/products/product-placeholder-toolbox.svg';
}

function tristoolAssetUrl(string $source): string
{
    $url = Str::startsWith($source, ['http://', 'https://']) ? $source : 'https://tristool.md/'.ltrim($source, '/');
    $parts = parse_url($url);

    if (! isset($parts['scheme'], $parts['host'])) {
        return $url;
    }

    $path = implode('/', array_map('rawurlencode', explode('/', $parts['path'] ?? '')));

    return $parts['scheme'].'://'.$parts['host'].$path.(isset($parts['query']) ? '?'.$parts['query'] : '');
}

function shortProductDescription(string $title, string $brandName): string
{
    $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

    return "{$brand}: produs profesional pentru service auto, atelier si garaj. Model: {$title}.";
}

function fullProductDescription(string $title, string $brandName, string $sku): string
{
    $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

    return "Produs {$brand}, cod {$sku}, adaugat in catalogul MasterScule.ro pentru service-uri auto, ateliere si clienti care cauta scule fiabile. Cardul include denumire, cod produs, pret in RON, imagine, stoc disponibil, garantie si caracteristici tehnice de baza. Potrivit pentru utilizare profesionala si pentru garaje bine echipate.";
}

function attributesForTrisToolTitle(string $title, string $sku, string $brandName): array
{
    $attributes = [
        'Brand' => str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony',
        'Cod produs' => $sku,
        'Utilizare' => 'Service auto / atelier / garaj',
        'Garantie' => '24 luni',
    ];

    if (preg_match('/([0-9]+)\s*(?:Nm|Нм)/iu', $title, $match)) {
        $attributes['Cuplu maxim'] = $match[1].' Nm';
    }

    if (preg_match('/([0-9]+)\s*(?:предмет|piese|шт|pcs)/iu', $title, $match)) {
        $attributes['Numar piese'] = $match[1];
    }

    if (preg_match('/(1\/4|3\/8|1\/2|3\/4|1")/u', $title, $match)) {
        $attributes['Antrenare'] = $match[1];
    }

    if (preg_match('/([0-9]+)\s*(?:мм|mm)/iu', $title, $match)) {
        $attributes['Dimensiune'] = $match[1].' mm';
    }

    if (preg_match('/([0-9]+)\s*(?:V|В)/u', $title, $match)) {
        $attributes['Tensiune'] = $match[1].' V';
    }

    return $attributes;
}

function packageForTrisToolTitle(string $title): array
{
    $lower = mb_strtolower($title, 'UTF-8');

    if (str_contains($lower, 'набор') || str_contains($lower, 'комплект')) {
        return ['Set scule', 'Cutie / organizator', 'Documentatie tehnica'];
    }

    if (str_contains($lower, 'аккумулятор') || str_contains($lower, '18в') || str_contains($lower, '18 v')) {
        return ['Scula principala', 'Ambalaj', 'Documentatie tehnica'];
    }

    return ['Produs principal', 'Ambalaj', 'Documentatie tehnica'];
}
}
