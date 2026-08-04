<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\Catalog\ProductContentLanguage;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$published = Product::query()
    ->where('status', 'published')
    ->orderBy('sku')
    ->get(['sku', 'name_ru', 'name_ro', 'description_ru', 'description_ro']);

$language = app(ProductContentLanguage::class);

$englishNames = $published->filter(
    fn (Product $product): bool => $language->isLikelyEnglish((string) $product->name_ro),
)->map(fn (Product $product): array => [
    'sku' => $product->sku,
    'name_ru' => $product->name_ru,
    'name_ro' => $product->name_ro,
])->values();

$englishDescriptions = $published->filter(
    fn (Product $product): bool => $language->isLikelyEnglish((string) $product->description_ro),
)->map(fn (Product $product): array => [
    'sku' => $product->sku,
    'description_ru' => $product->description_ru,
    'description_ro' => $product->description_ro,
])->values();

$malformedNames = $published->filter(static function (Product $product): bool {
    $value = (string) $product->name_ro;

    return preg_match('/(?:\s\.\s)|(?:,\s*["“”]\s*["“”])|(?:\.\s*\.)|(?:\bMBX c$)|(?:,\s*c$)|(?:\b5Ah\s*-\s*2$)/u', $value) === 1;
})->map(fn (Product $product): array => [
    'sku' => $product->sku,
    'name_ru' => $product->name_ru,
    'name_ro' => $product->name_ro,
])->values();

$russianNameTypos = $published->filter(static function (Product $product): bool {
    return preg_match('/(?:фрейзер|синея|\bдля\s+для\b)/iu', (string) $product->name_ru) === 1;
})->map(fn (Product $product): array => [
    'sku' => $product->sku,
    'name_ru' => $product->name_ru,
    'name_ro' => $product->name_ro,
])->values();

$semanticPatterns = [
    'impact_socket_mistranslation' => '/\bpriz(?:ă|a)\s+(?:adâncă\s+)?de impact\b/iu',
    'impact_bit_mistranslation' => '/\bliliac de impact\b/iu',
    'profile_mistranslation' => '/\b\d+\s+(?:cereale|boabe)\b/iu',
    'bit_command_form' => '/\b(?:introduceți|inserați)\s*\(bit\)/iu',
    'tap_mistranslation' => '/(?:\b(?:atingeți|măsura)\s+M\d|\bcontor\s+M\d)/iu',
    'known_literal_translation' => '/\b(?:lanterna de sudur[ăa]|clemă de corp|pod care trag corpul|cap muf[ăa]|set prize de capăt|sarma solid|aliei de aluminiu|instrumente de îndrire|l(?:e)?erka|suport pentru haine|placa este)\b/iu',
    'generic_machine_title' => '/^(?:bit sau adaptor|bit sau accesoriu|consumabil pentru scule pneumatice|accesoriu universal|clește sau sculă de tăiere|sculă pentru electrician)\b/iu',
    'malformed_unit_tail' => '/,\s*mm(?:\s+\d+)?\s*$/u',
    'duplicate_word_ro' => '/\b([\p{L}]{4,})\s+\1\b/iu',
];

$semanticDefects = [];
foreach ($semanticPatterns as $name => $pattern) {
    $nameOnly = in_array($name, ['malformed_unit_tail'], true);
    $rows = $published->filter(static fn (Product $product): bool => preg_match(
        $pattern,
        $nameOnly
            ? trim((string) $product->name_ro)
            : trim((string) $product->name_ro.' '.(string) $product->description_ro),
    ) === 1)->map(fn (Product $product): array => [
        'sku' => $product->sku,
        'name_ru' => $product->name_ru,
        'name_ro' => $product->name_ro,
    ])->values();
    $semanticDefects[$name] = ['count' => $rows->count(), 'rows' => $rows];
}

$oilFilterFacetErrors = [
    'ro' => $published->filter(static fn (Product $product): bool => preg_match(
        '/filtr\p{L}*(?:\s+de)?\s+ulei.*\b\d+\s*g\./iu',
        (string) $product->name_ro,
    ) === 1)->values(),
    'ru' => $published->filter(static fn (Product $product): bool => preg_match(
        '/маслян(?:ого|ых|ый|ые)\s+фильтр.*\b\d+\s*гр\./iu',
        (string) $product->name_ru,
    ) === 1)->values(),
];

$attributeCounts = [];
foreach (['ru', 'ro'] as $locale) {
    App::setLocale($locale);
    $counts = Product::query()->where('status', 'published')->with(['brand', 'category'])->get()
        ->map(fn (Product $product): int => count($product->display_attributes));
    $attributeCounts[$locale] = [
        'zero' => $counts->filter(fn (int $count): bool => $count === 0)->count(),
        'one' => $counts->filter(fn (int $count): bool => $count === 1)->count(),
    ];
}

$thinDescriptions = [
    'ru' => $published->filter(fn (Product $product): bool => mb_strlen(trim(strip_tags((string) $product->description_ru))) < 80)->count(),
    'ro' => $published->filter(fn (Product $product): bool => mb_strlen(trim(strip_tags((string) $product->description_ro))) < 80)->count(),
];

$duplicateNames = [
    'ru' => DB::table('products')->where('status', 'published')->groupBy('name_ru')->havingRaw('COUNT(*) > 1')->get()->count(),
    'ro' => DB::table('products')->where('status', 'published')->groupBy('name_ro')->havingRaw('COUNT(*) > 1')->get()->count(),
];

$report = [
    'english_ro_names' => ['count' => $englishNames->count(), 'rows' => $englishNames],
    'english_ro_descriptions' => ['count' => $englishDescriptions->count(), 'rows' => $englishDescriptions],
    'malformed_ro_names' => ['count' => $malformedNames->count(), 'rows' => $malformedNames],
    'russian_name_typos' => ['count' => $russianNameTypos->count(), 'rows' => $russianNameTypos],
    'semantic_defects' => $semanticDefects,
    'oil_filter_facets_as_grams' => [
        'ro' => $oilFilterFacetErrors['ro']->count(),
        'ru' => $oilFilterFacetErrors['ru']->count(),
    ],
    'visible_attribute_counts' => $attributeCounts,
    'thin_descriptions' => $thinDescriptions,
    'duplicate_name_groups' => $duplicateNames,
];

$section = $argv[1] ?? null;
if (is_string($section) && isset($report[$section])) {
    $report = [$section => $report[$section]];
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
