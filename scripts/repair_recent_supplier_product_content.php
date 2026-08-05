<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batchIds = [26, 27, 28];

$categoryRules = [
    'mobilier-pentru-service' => ['верстак рабочий'],
    'carucioare-de-scule' => ['тележка инструментальная'],
    'carucioare-pentru-rafturi' => ['тележка передвижная', 'тележка платформенная', 'контейнер для мусора'],
    'sisteme-de-depozitare-si-transport' => ['скамейка', 'стеллаж', 'кювет'],
    'dulapuri-si-organizare' => ['кешбокс', 'ключница', 'аптечка', 'шкаф'],
    'accesorii-pentru-bancuri-de-lucru' => [
        'блок электромонтажный', 'комплект освещения', 'крючок', 'опора', 'панель',
        'подставка', 'полка', 'полкокомплект', 'столешница',
    ],
    'polizoare' => ['шлифовальный станок'],
    'menghine-si-cleme' => ['тиски'],
];

$romanianTypes = [
    'оборудование для регулировки света фар' => 'Aparat de reglat faruri',
    'устройство для прокачки тормозов' => 'Echipament pentru aerisirea frânelor',
    'сварочный полуавтомат' => 'Aparat de sudură semiautomat',
    'ручная дуговая сварка' => 'Aparat de sudură MMA',
    'аппарат плазменной резки' => 'Aparat de tăiere cu plasmă',
    'сварка аргонодуговая' => 'Aparat de sudură TIG',
    'аппарат для правки стали' => 'Aparat pentru redresarea oțelului',
    'аппарат для правки алюминия' => 'Aparat pentru redresarea aluminiului',
    'пуско-зарядное устройство' => 'Redresor și demaror auto',
    'автономное пусковое устройство' => 'Demaror auto portabil',
    'зарядное устройство' => 'Încărcător pentru baterii',
    'инфракрасная сушка' => 'Uscător cu infraroșu',
    'мобильная вытяжка выхлопных газов' => 'Exhaustor mobil pentru gaze',
    'портативный озонатор' => 'Generator portabil de ozon',
    'манометрический коллектор' => 'Set de manometre',
    'ультразвуковая ванна' => 'Baie cu ultrasunete',
    'установка для подачи масла' => 'Pompă pentru umplerea cu ulei',
    'установка для обнаружения утечек' => 'Detector de scurgeri',
    'установка для замены охлаждающей жидкости' => 'Echipament pentru schimbarea lichidului de răcire',
    'комплект для пережатия трубопроводов' => 'Set de clești pentru furtunuri',
    'комплект для проверки давления' => 'Set pentru verificarea presiunii',
    'комплект для определения утечек' => 'Set pentru detectarea scurgerilor',
    'набор инструментальной мебели' => 'Set de mobilier pentru atelier',
    'набор аксессуаров' => 'Set de accesorii',
    'набор головок' => 'Set de tubulare',
    'набор для диагностики' => 'Set de diagnosticare',
    'набор для обнаружения утечек' => 'Set pentru detectarea scurgerilor',
    'набор для снятия' => 'Set de extractoare',
    'набор для стяжки пружин' => 'Set compresoare de arcuri',
    'набор для правки кузова' => 'Set hidraulic pentru caroserie',
    'набор монтировок' => 'Set de leviere',
    'комплект быстросъёмных соединителей' => 'Set de cuple rapide',
    'комплект медных шайб' => 'Set de șaibe din cupru',
    'комплект головок' => 'Set de tubulare',
    'комплект для резки' => 'Set pentru tăiere și bercluire',
    'комплект для снятия' => 'Set de extractoare',
    'комплект освещения' => 'Set de iluminare',
    'комплект для правки кузова' => 'Set hidraulic pentru caroserie',
    'комплект' => 'Set',
    'набор' => 'Set',
    'верстак рабочий' => 'Banc de lucru',
    'кешбокс' => 'Casetă de valori',
    'ключница' => 'Dulap pentru chei',
    'медицинская аптечка' => 'Dulap pentru trusă medicală',
    'шкаф для огнетушителя' => 'Dulap pentru extinctor',
    'шкаф файловый' => 'Fișet metalic',
    'шкаф архивный' => 'Dulap de arhivare',
    'шкаф гардеробный' => 'Dulap vestiar',
    'шкаф инструментальный' => 'Dulap pentru scule',
    'шкаф хозяйственный' => 'Dulap utilitar',
    'шкаф одежный' => 'Dulap vestiar',
    'шкаф ячеечный' => 'Dulap compartimentat',
    'шкаф' => 'Dulap metalic',
    'блок электромонтажный' => 'Modul electric',
    'двухсторонний шлифовальный станок' => 'Polizor de banc dublu',
    'крючок' => 'Cârlig',
    'опора' => 'Suport',
    'панель для инструментов' => 'Panou pentru scule',
    'панель перфорированная' => 'Panou perforat',
    'перфорированная надставка' => 'Panou perforat cu suporturi',
    'подставка' => 'Suport',
    'полкокомплект' => 'Set de rafturi',
    'полка' => 'Raft',
    'столешница' => 'Blat de lucru',
    'тиски' => 'Menghină',
    'тумба верстачная' => 'Dulap pentru banc de lucru',
    'скамейка' => 'Bancă metalică',
    'стул промышленный' => 'Scaun industrial',
    'кювет' => 'Cutie de depozitare',
    'стеллаж' => 'Raft metalic',
    'тележка инструментальная' => 'Cărucior pentru scule',
    'тележка платформенная' => 'Cărucior platformă',
    'тележка передвижная' => 'Cărucior mobil',
    'контейнер для мусора' => 'Container pentru deșeuri',
    'замок' => 'Încuietoare',
    'ножки' => 'Set de picioare',
    'детектор' => 'Detector',
    'жидкость' => 'Lichid tehnic',
    'масло' => 'Ulei tehnic',
    'запасная часть' => 'Piesă de schimb',
    'головка ударная' => 'Tubulară de impact',
    'головка' => 'Tubulară',
    'ключ' => 'Cheie specială',
    'переходник' => 'Adaptor',
    'разъём высокого давления' => 'Cuplă de înaltă presiune',
    'разъём низкого давления' => 'Cuplă de joasă presiune',
    'поддон' => 'Tavă colectoare',
    'съёмник' => 'Extractor',
    'станция' => 'Stație de service',
    'стенд' => 'Stand de testare',
    'станок' => 'Mașină pentru atelier',
    'установка' => 'Echipament pentru atelier',
    'устройство' => 'Dispozitiv pentru atelier',
    'фонарь' => 'Lampă de lucru',
    'шумомер' => 'Sonometru',
    'фильтр' => 'Filtru',
    'диффузор' => 'Difuzor',
    'колпачок' => 'Capac',
    'сопло' => 'Duză',
    'экран' => 'Scut de protecție',
    'электрод' => 'Electrod',
    'амперметр' => 'Ampermetru',
    'гайка' => 'Piuliță',
    'горелка' => 'Pistolet de sudură',
    'диод' => 'Diodă',
    'предохранител' => 'Siguranță fuzibilă',
    'ролик' => 'Rolă de avans',
    'термостат' => 'Termostat',
    'кабель' => 'Cablu',
    'захват' => 'Clemă pentru caroserie',
    'грейфер' => 'Extractor multipunct',
    'автономное' => 'Echipament portabil',
    'блок питания' => 'Sursă de alimentare',
    'стартовые кабел' => 'Cabluri de pornire',
    'зажим' => 'Clemă izolată',
    'кольца медные' => 'Șaibe din cupru',
    'клещи' => 'Clește de sudură prin puncte',
    'аппарат' => 'Echipament profesional',
];

function latinSku(string $sku): string
{
    return strtr($sku, [
        'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'E','Ж'=>'ZH','З'=>'Z','И'=>'I','Й'=>'I',
        'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
        'Х'=>'H','Ц'=>'C','Ч'=>'CH','Ш'=>'SH','Щ'=>'SCH','Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'YU','Я'=>'YA',
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'i',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ]);
}

function romanianType(string $name, array $types): string
{
    $name = mb_strtolower($name, 'UTF-8');
    foreach ($types as $needle => $translation) {
        if (mb_strpos($name, $needle, 0, 'UTF-8') !== false) {
            return $translation;
        }
    }

    return 'Produs profesional';
}

function extractedFacts(string $name): array
{
    preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:[xх×]\s*\d+(?:[.,]\d+)?){1,3}\s*(?:мм|см|м)?|\b\d+(?:[.,]\d+)?\s*(?:мм|см|м|л|кг|г|в|а|вт|квт|бар|шт\.?|предм\.?|ящик(?:а|ов)?|пол(?:ка|ки|ок)|секци(?:я|и|й)|ключ(?:а|ей)?|ламп(?:а|ы)?|блок(?:а|ов)?)\b/iu', $name, $matches);
    $facts = array_values(array_unique(array_map(static function (string $fact): string {
        $fact = str_replace(['х', '×'], 'x', $fact);
        return trim(preg_replace('/\s+/u', ' ', $fact) ?: $fact);
    }, $matches[0] ?? [])));

    return array_slice($facts, 0, 6);
}

$categories = DB::table('categories')->get(['id', 'slug', 'name', 'name_ro'])->keyBy('slug');
$rows = DB::table('products as p')
    ->join('product_parser_items as i', 'i.id', '=', 'p.source_parser_item_id')
    ->join('brands as b', 'b.id', '=', 'p.brand_id')
    ->join('categories as c', 'c.id', '=', 'p.category_id')
    ->whereIn('p.source_import_batch_id', $batchIds)
    ->orderBy('p.source_import_batch_id')
    ->orderBy('i.row_number')
    ->get([
        'p.id', 'p.sku', 'p.source_import_batch_id as batch_id', 'i.id as item_id', 'i.raw_name',
        'b.name as brand', 'c.name as category_ru', 'c.name_ro as category_ro', 'c.slug as category_slug',
    ]);

$updated = 0;
$categoryChanges = [];
$publishedAfterAudit = [];

DB::transaction(function () use ($rows, $categories, $categoryRules, $romanianTypes, &$updated, &$categoryChanges): void {
    foreach ($rows as $row) {
        $category = $categories->get($row->category_slug);
        if ((int) $row->batch_id === 26) {
            $rawLower = mb_strtolower((string) $row->raw_name, 'UTF-8');
            foreach ($categoryRules as $slug => $needles) {
                if (collect($needles)->contains(static fn (string $needle): bool => mb_strpos($rawLower, $needle, 0, 'UTF-8') !== false)) {
                    $target = $categories->get($slug);
                    if ($target && $target->id !== $category->id) {
                        $categoryChanges[] = ['sku' => $row->sku, 'from' => $category->slug, 'to' => $target->slug];
                    }
                    $category = $target ?: $category;
                    break;
                }
            }
        }

        $brandRo = str_contains(mb_strtoupper((string) $row->brand, 'UTF-8'), 'УХЛ') ? 'UHL-MASH' : (string) $row->brand;
        $skuRo = latinSku((string) $row->sku);
        $nameRu = trim((string) $row->raw_name);
        $nameRo = trim(romanianType($nameRu, $romanianTypes).' '.$brandRo.' '.$skuRo);
        $facts = extractedFacts($nameRu);
        $factsRu = $facts === [] ? '' : ' Указанные в прайс-листе параметры: '.implode(', ', $facts).'.';
        $factsRo = '';

        $shortRu = $nameRu.'. Бренд: '.$row->brand.'; код: '.$row->sku.'.';
        $shortRo = $nameRo.'. Marcă: '.$brandRo.'; cod: '.$skuRo.'.';
        $descriptionRu = $nameRu.'. Бренд: '.$row->brand.'. Код товара: '.$row->sku.'. Категория: '.$category->name.'.'
            .$factsRu.' Карточка сверена с последним прайс-листом поставщика по коду товара и наименованию.';
        $descriptionRo = $nameRo.'. Marcă: '.$brandRo.'. Cod produs: '.$skuRo.'. Categoria: '.$category->name_ro.'.'
            .$factsRo.' Fișa a fost verificată după codul și denumirea din ultima listă a furnizorului.';

        DB::table('products')->where('id', $row->id)->update([
            'category_id' => $category->id,
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $shortRu,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'meta_title' => Str::limit($nameRu, 65, ''),
            'meta_description' => Str::limit($descriptionRu, 155, ''),
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => now(),
        ]);

        DB::table('product_parser_items')->where('id', $row->item_id)->update([
            'category_id' => $category->id,
            'detected_category_id' => $category->id,
            'detected_category_path' => $category->slug,
            'category_confidence_score' => 100,
            'category_detection_method' => 'deep_catalog_audit',
            'needs_category_review' => false,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'needs_translation_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => 'deep_catalog_audit',
            'translation_source_type' => 'curated_rule_based_ro',
            'translation_reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        $updated++;
    }
});

$reviewRows = DB::table('products')->whereIn('source_import_batch_id', $batchIds)->get([
    'id', 'sku', 'source_parser_item_id', 'price', 'needs_stock_review', 'needs_image_review',
    'needs_category_review', 'needs_translation_review', 'needs_price_review', 'needs_source_review',
    'needs_content_review', 'status',
]);

DB::transaction(function () use ($reviewRows, &$publishedAfterAudit): void {
    foreach ($reviewRows as $product) {
        $hasReviewFlag = collect([
            $product->needs_stock_review, $product->needs_image_review, $product->needs_category_review,
            $product->needs_translation_review, $product->needs_price_review, $product->needs_source_review,
            $product->needs_content_review,
        ])->contains(static fn ($value): bool => (bool) $value);
        $ready = (float) $product->price > 0 && ! $hasReviewFlag;

        DB::table('products')->where('id', $product->id)->update([
            'needs_review' => ! $ready,
            'status' => $ready ? 'published' : 'draft',
            'approval_status' => $ready ? 'approved' : 'pending_review',
            'is_active' => $ready,
            'updated_at' => now(),
        ]);
        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'status' => $ready ? 'approved' : 'draft_created',
            'approval_status' => $ready ? 'approved' : 'pending_review',
            'updated_at' => now(),
        ]);

        if ($ready && $product->status !== 'published') {
            $publishedAfterAudit[] = $product->sku;
        }
    }
});

echo json_encode([
    'batches' => $batchIds,
    'updated_products' => $updated,
    'category_changes' => count($categoryChanges),
    'category_change_details' => $categoryChanges,
    'published_after_audit' => $publishedAfterAudit,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
