<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            foreach (['name', 'name_ru', 'name_ro', 'meta_title'] as $attribute) {
                if (! $product->isDirty($attribute)) {
                    continue;
                }

                $value = $product->getAttribute($attribute);
                if (is_string($value)) {
                    $product->setAttribute($attribute, self::withoutSourceStoreName($value));
                }
            }
        });
    }

    private const ATTRIBUTE_KEYS_RU = [
        'Numar piese' => 'Количество предметов',
        'Număr piese' => 'Количество предметов',
        'Material' => 'Материал',
        'Utilizare' => 'Применение',
        'Greutate' => 'Вес',
        'Dimensiuni' => 'Размеры',
    ];

    private const ATTRIBUTE_KEYS_RO = [
        'Тип' => 'Tip',
        'Механизм' => 'Mecanism',
        'Угол поворота' => 'Unghi de rotație',
        'Применение' => 'Utilizare',
        'Количество секций' => 'Număr de secțiuni',
        'Комплектация' => 'Conținutul livrării',
        'Материал рукоятки' => 'Materialul mânerului',
        'Фиксация' => 'Fixare',
        'Входной квадрат' => 'Pătrat de intrare',
        'Выходной квадрат' => 'Pătrat de ieșire',
        'Резьба' => 'Filet',
        'Конструкция губок' => 'Configurația fălcilor',
        'Исполнение' => 'Execuție',
        'Номинальный ток' => 'Curent nominal',
        'Диапазон тока' => 'Interval de curent',
        'Сечение кабеля' => 'Secțiunea cablului',
        'Диаметр катушки' => 'Diametrul bobinei',
        'Диаметр проволоки' => 'Diametrul sârmei',
        'Материал проволоки' => 'Materialul sârmei',
        'Количество рядов' => 'Număr de rânduri',
        'Марка электрода' => 'Marca electrodului',
        'Диапазон диаметра проволоки' => 'Intervalul diametrului sârmei',
        'Длина кабеля' => 'Lungimea cablului',
        'Диаметр' => 'Diametru',
        'Диаметр штока' => 'Diametrul tijei',
        'Совместимость' => 'Compatibilitate',
        'Количество предметов' => 'Număr de piese',
        'Размер съёмника рулевой тяги' => 'Dimensiunea separatorului pentru bara de direcție',
        'Размер съёмника шаровой опоры' => 'Dimensiunea separatorului pentru articulația sferică',
        'Размер съёмника сошки Pitman' => 'Dimensiunea separatorului pentru brațul Pitman',
        'Максимальное давление' => 'Presiune maximă',
        'Расход воздуха при 100 PSI' => 'Consum de aer la 100 PSI',
        'Длина шланга' => 'Lungimea furtunului',
        'Тип наконечника' => 'Tipul duzei',
        'Объём' => 'Volum',
        'Испытательное давление' => 'Presiune de probă',
        'Производительность' => 'Debit',
        'Уровень звукового давления' => 'Nivel de presiune acustică',
        'Стандарт' => 'Standard',
        'Максимальное рабочее напряжение' => 'Tensiune maximă de lucru',
        'Размер ложемента' => 'Dimensiunea tăvii',
        'Размер ящика' => 'Dimensiunea sertarului',
        'Размеры ключей' => 'Dimensiunile cheilor',
        'Рабочий профиль' => 'Profil de lucru',
        'Грузоподъёмность' => 'Capacitate de ridicare',
        'Диапазон подъёма' => 'Interval de ridicare',
        'Размер платформы' => 'Dimensiunea platformei',
        'Размер упаковки' => 'Dimensiunea ambalajului',
        'Масса нетто' => 'Greutate netă',
        'Масса брутто' => 'Greutate brută',
        'Поворот монтажной плиты' => 'Rotirea plăcii de montare',
        'Количество колёс' => 'Număr de roți',
        'Управление подъёмом' => 'Acționarea ridicării',
        'Numar piese' => 'Număr de piese',
        'Număr piese' => 'Număr de piese',
        'Material' => 'Material',
        'Utilizare' => 'Utilizare',
        'Greutate' => 'Greutate',
        'Dimensiuni' => 'Dimensiuni',
        'Вес' => 'Greutate',
        'Подгруппа' => 'Subgrup',
        'Габариты (ШxВxД)' => 'Dimensiuni (L×Î×A)',
        'Габариты (ШxВxГ)' => 'Dimensiuni (L×Î×A)',
        'Габаритные размеры' => 'Dimensiuni',
        'Длина' => 'Lungime',
        'Рабочая длина' => 'Lungime de lucru',
        'Общая длина' => 'Lungime totală',
        'Посадочный квадрат' => 'Pătrat de antrenare',
        'Размер квадрата' => 'Dimensiunea pătratului',
        'Размер' => 'Dimensiune',
        'Материал' => 'Material',
        'Покрытие' => 'Acoperire',
        'Рабочее давление' => 'Presiune de lucru',
        'Материал ложемента' => 'Materialul inserției',
        'Размер ложемента (ШxД)' => 'Dimensiunea inserției (L×A)',
        'Уровень шума' => 'Nivel de zgomot',
        'Уровень вибрации' => 'Nivel de vibrații',
        'Количество зубцов' => 'Număr de dinți',
        'Количество граней' => 'Număr de laturi',
        'Размер для вставок' => 'Dimensiune pentru inserții',
        'Расход воздуха' => 'Consum de aer',
        'Среднее потребление воздуха' => 'Consum mediu de aer',
        'Тип системы' => 'Tip de sistem',
        'Макс. усилие на откручивание' => 'Cuplu maxim la desfacere',
        'Скорость свободного вращения' => 'Turație în gol',
        'Скорость вращения' => 'Turație',
        'Посадочное место' => 'Prindere',
        'Размер воздушного штуцера' => 'Dimensiunea racordului de aer',
        'Мощность' => 'Putere',
        'Диаметр шланга (рекомендуется)' => 'Diametrul recomandat al furtunului',
        'Диапазон крутящего момента' => 'Interval de cuplu',
        'Цвет' => 'Culoare',
        'Допустимая погрешность' => 'Toleranță admisă',
        'Цена деления' => 'Diviziune',
    ];

    private const ATTRIBUTE_VALUES_RU = [
        'Otel crom-vanadiu' => 'Хром-ванадиевая сталь',
        'Oțel crom-vanadiu' => 'Хром-ванадиевая сталь',
        'Profesional' => 'Профессиональное',
        'Service' => 'Сервис',
        '12 luni' => '12 месяцев',
    ];

    private const ATTRIBUTE_VALUES_RO = [
        'Смазочная муфта' => 'Cuplă de gresare',
        'Быстросъёмный' => 'Cu eliberare rapidă',
        'Поворотный' => 'Pivotant',
        'Набор точильных камней' => 'Set de pietre de șlefuit',
        'Пистолет-распылитель для очистителя' => 'Pistol de pulverizare pentru solvent',
        'Пневматическая угловая шлифмашина' => 'Polizor pneumatic unghiular',
        'Набор для снятия дизельных форсунок' => 'Set pentru demontarea injectoarelor diesel',
        'Пневматический обратный молоток' => 'Ciocan pneumatic invers',
        'Набор ударных адаптеров' => 'Set de adaptoare de impact',
        'Универсальный суппорт для коробок передач' => 'Suport universal pentru cutii de viteze',
        'Термообработанная легированная сталь' => 'Oțel aliat tratat termic',
        'Стандартная торцевая головка' => 'Cap tubular standard',
        'Глубокая торцевая головка' => 'Cap tubular lung',
        'Экстраглубокая торцевая головка' => 'Cap tubular extra-lung',
        'Набор отвёрток с храповым механизмом' => 'Set de șurubelnițe cu clichet',
        'Набор инструментов механика' => 'Set de scule pentru mecanic',
        'Рукоятка-вороток' => 'Mâner de antrenare',
        'Рукоятка привода' => 'Mâner de antrenare',
        'Набор отвёрток' => 'Set de șurubelnițe',
        'Напильник для восстановления резьбы' => 'Pilă pentru repararea filetului',
        'Пневматическая трещотка' => 'Clichet pneumatic',
        'Набор головок для снятия повреждённых гаек' => 'Set de capete pentru piulițe deteriorate',
        'Набор экстракторов роликовых шпилек' => 'Set de extractoare pentru știfturi',
        'Набор цифровых клейм' => 'Set de poansoane cu cifre',
        'Складной ящик для инструментов' => 'Cutie pliabilă pentru scule',
        'Храповый' => 'Cu clichet',
        'Сменные стержни' => 'Tije interschimbabile',
        'Инструментальная сумка' => 'Geantă pentru scule',
        'Восстановление наружной резьбы' => 'Repararea filetului exterior',
        'Цифровая маркировка' => 'Marcare numerică',
        'Торцевая насадка-бита' => 'Cap tubular cu bit',
        'Ударная бита' => 'Bit de impact',
        'Ударная вставка-бита' => 'Inserție bit de impact',
        'Магнитный держатель бит' => 'Suport magnetic pentru biți',
        'С фиксатором' => 'Cu sistem de fixare',
        'Карданная торцевая головка' => 'Cap tubular articulat',
        'Карданный шарнир' => 'Articulație cardanică',
        'Комбинированный ключ с трещоткой' => 'Cheie combinată cu clichet',
        'Комбинированный шарнирный ключ с трещоткой' => 'Cheie combinată articulată cu clichet',
        'Ударная торцевая головка' => 'Cap tubular de impact',
        'Глубокая ударная торцевая головка' => 'Cap tubular de impact lung',
        'Глубокая тонкостенная ударная головка' => 'Cap tubular de impact lung cu perete subțire',
        'Двусторонняя ударная головка' => 'Cap tubular de impact cu două capete',
        'Трещотка' => 'Clichet',
        'Трещотка для торцевых головок' => 'Clichet pentru capete tubulare',
        'Трещотка для бит' => 'Clichet pentru biți',
        'Т-образный вороток с карданом' => 'Mâner în T cu articulație cardanică',
        'Т-образный вороток' => 'Mâner în T',
        'Шарнирный вороток' => 'Mâner articulat',
        'Скользящий вороток' => 'Mâner culisant',
        'Универсальный шарнир' => 'Articulație universală',
        'Ударный карданный шарнир' => 'Articulație cardanică de impact',
        'Переходной адаптер' => 'Adaptor de trecere',
        'Шарнирная свечная головка' => 'Cap tubular articulat pentru bujii',
        'Удлинитель' => 'Prelungitor',
        'Кнопочная фиксация' => 'Fixare cu buton',
        'Дисковый переключатель' => 'Comutator tip disc',
        'Шарнирная головка' => 'Cap articulat',
        'Автореверс' => 'Inversare automată',
        'Бесшаговый' => 'Fără trepte',
        'С шаром' => 'Cu bilă',
        'Резина' => 'Cauciuc',
        'Для торцевых головок и бит' => 'Pentru capete tubulare și biți',
        'Зажим для подкачки шин' => 'Clemă pentru umflarea anvelopelor',
        'Разводной ключ с двусторонними губками' => 'Cheie reglabilă cu fălci reversibile',
        'Набор телескопических отвёрток с трещоткой' => 'Set de șurubelnițe telescopice cu clichet',
        'Набор Т-образных баллонных ключей' => 'Set de chei pentru roți cu mâner în T',
        'Разводной ключ с трещоткой' => 'Cheie reglabilă cu clichet',
        'Двусторонний саморегулирующийся ключ' => 'Cheie auto-reglabilă cu două fețe',
        'Комплект для измерения компрессии дизельного двигателя' => 'Set pentru măsurarea compresiei motorului diesel',
        'Универсальный резак' => 'Cutter universal',
        'Т-образный торцевой ключ' => 'Cheie tubulară în T',
        'Двусторонний грузовой ключ' => 'Cheie tubulară dublă pentru camion',
        'Рычаг для грузового ключа' => 'Bară pentru cheie de camion',
        'Двусторонние губки' => 'Fălci reversibile',
        'Саморегулирующийся' => 'Auto-reglabil',
        'Двусторонний' => 'Cu două fețe',
        'Измерение компрессии дизельного двигателя' => 'Măsurarea compresiei motorului diesel',
        'Магнитный угольник для сварки' => 'Echer magnetic pentru sudură',
        'Регулируемый магнитный угольник для сварки' => 'Echer magnetic reglabil pentru sudură',
        'Зажим массы' => 'Clemă de masă',
        'Катушка сварочной проволоки' => 'Bobină de sârmă pentru sudură',
        'Молоток для удаления шлака' => 'Ciocan pentru îndepărtarea zgurii',
        'Щётка-молоток для удаления шлака' => 'Perie-ciocan pentru îndepărtarea zgurii',
        'Проволочная щётка' => 'Perie de sârmă',
        'Жёсткий прямой провод' => 'Conductor drept rigid',
        'Плетёный провод для индуктора' => 'Conductor împletit pentru inductor',
        'Спиральный провод для индуктора' => 'Conductor spiralat pentru inductor',
        'Пластиковые ремонтные стержни' => 'Tije din plastic pentru reparații',
        'Бронзовый зажим' => 'Clemă din bronz',
        'Изолированный зажим' => 'Clemă izolată',
        'Алюминиевая присадка TIG' => 'Baghetă de adaos TIG din aluminiu',
        'Стальная присадка TIG' => 'Baghetă de adaos TIG din oțel',
        'Вольфрамовый электрод' => 'Electrod de tungsten',
        'Контактный наконечник' => 'Duză de contact',
        'Плазменная горелка' => 'Pistolet pentru tăiere cu plasmă',
        'Направляющий канал для проволоки' => 'Ghidaj pentru sârmă',
        'Зажимы для зарядного устройства' => 'Cleme pentru încărcător',
        'Сварочные работы' => 'Lucrări de sudură',
        'Удаление шлака' => 'Îndepărtarea zgurii',
        'Индукционный нагрев' => 'Încălzire prin inducție',
        'Стальная проволока' => 'Sârmă de oțel',
        'Кованая сталь' => 'Oțel forjat',
        'Сталь' => 'Oțel',
        'Дерево' => 'Lemn',
        'Пластик' => 'Plastic',
        'Бронза' => 'Bronz',
        'Алюминий' => 'Aluminiu',
        'Нелегированная сталь' => 'Oțel nealiat',
        'Красный' => 'Roșu',
        'Чёрный' => 'Negru',
        'Регулируемый' => 'Reglabil',
        'Треугольный профиль' => 'Profil triunghiular',
        'Зачистной диск' => 'Disc de curățare',
        'Набор съёмников' => 'Set de separatoare',
        'Пистолет для накачки шин' => 'Pistol pentru umflarea anvelopelor',
        'Ручной/пневматический' => 'Manual/pneumatic',
        'Импульсная подача' => 'Alimentare în impulsuri',
        'Непрерывная подача' => 'Alimentare continuă',
        'Набор изолированных инструментов' => 'Set de scule izolate',
        'Набор рожковых ключей' => 'Set de chei fixe',
        'Стенд для двигателя' => 'Suport pentru motor',
        'Гидравлический подъёмный стол' => 'Masă hidraulică de ridicare',
        'Зажим для вентиля шины' => 'Clemă pentru ventilul anvelopei',
        'Рожковый' => 'Cheie fixă',
        'Ножная гидравлическая педаль' => 'Pedală hidraulică',
        'есть' => 'da',
        'Да' => 'Da',
        'Нет' => 'Nu',
        'Хром-ванадиевая сталь' => 'Oțel crom-vanadiu',
        'Профессиональное' => 'Profesional',
        'Набор Г-образных шестигранных ключей' => 'Set de chei hexagonale în L',
        'Набор Г-образных шестигранных ключей с шаром' => 'Set de chei hexagonale în L cu cap sferic',
        'Набор складных ключей TORX' => 'Set de chei TORX pliabile',
        'Набор удлинённых ключей TORX с отверстием' => 'Set de chei TORX lungi cu orificiu',
        'Легированная сталь SNCM-V' => 'Oțel aliat SNCM-V',
        'Хромированное' => 'Cromat',
        'Экстрадлинное' => 'Extra-lung',
        'Стандартное' => 'Standard',
        'Складное' => 'Pliabil',
        'Экстрадлинное, с отверстием' => 'Extra-lung, cu orificiu',
        'Инструмент для извлечения автомобильных клемм' => 'Unealtă pentru extragerea terminalelor auto',
        'Автомобильные электрические разъёмы' => 'Conectori electrici auto',
        'Серия KING TONY 9DT11' => 'Seria KING TONY 9DT11',
        'Круглый ABS' => 'Rotund ABS',
        'Круглый' => 'Rotund',
        'Стандартный' => 'Standard',
        'Микростандартный' => 'Micro-standard',
    ];

    protected $fillable = [
        'brand_id', 'category_id', 'name', 'name_ru', 'name_ro', 'slug', 'sku', 'short_description',
        'short_description_ru', 'short_description_ro', 'description', 'description_ru', 'description_ro',
        'price', 'old_price', 'currency', 'stock_quantity', 'stock_status', 'status',
        'parser_confidence', 'parser_source_urls', 'main_image', 'gallery', 'attributes', 'package_contents',
        'rating', 'reviews_count', 'is_active', 'is_featured', 'is_bestseller', 'is_new', 'is_discounted',
        'warranty', 'weight', 'dimensions', 'approval_status', 'needs_review', 'needs_stock_review',
        'needs_image_review', 'needs_category_review', 'needs_translation_review', 'needs_price_review',
        'source_import_batch_id', 'source_parser_item_id', 'vehicle_application',
        'meta_title', 'meta_description',
        'source_url', 'source_domain', 'source_type', 'fallback_source_used',
        'needs_source_review', 'needs_content_review', 'generated_content', 'source_reviewed_at',
    ];

    protected $casts = [
        'gallery' => 'array',
        'parser_source_urls' => 'array',
        'attributes' => 'array',
        'package_contents' => 'array',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_new' => 'boolean',
        'is_discounted' => 'boolean',
        'needs_review' => 'boolean',
        'needs_stock_review' => 'boolean',
        'needs_image_review' => 'boolean',
        'needs_category_review' => 'boolean',
        'needs_translation_review' => 'boolean',
        'needs_price_review' => 'boolean',
        'source_import_batch_id' => 'integer',
        'source_parser_item_id' => 'integer',
        'fallback_source_used' => 'boolean',
        'needs_source_review' => 'boolean',
        'needs_content_review' => 'boolean',
        'generated_content' => 'boolean',
        'source_reviewed_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'source', 'confidence'])
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function parserItem()
    {
        return $this->belongsTo(ProductParserItem::class, 'source_parser_item_id');
    }

    public function categoryDecisions()
    {
        return $this->hasMany(ProductCategoryDecision::class);
    }

    public function scopeAvailableForSale($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->where('needs_review', false)
            ->where('needs_category_review', false)
            ->where('needs_translation_review', false)
            ->where('needs_price_review', false);
    }

    public function scopePurchasable($query)
    {
        return $query
            ->availableForSale()
            ->where('stock_status', 'in_stock')
            ->where('stock_quantity', '>', 0);
    }

    public function getIsPurchasableAttribute(): bool
    {
        return $this->stock_status === 'in_stock' && (int) $this->stock_quantity > 0;
    }

    public function scopeInCatalogCategories($query, array $categoryIds)
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if ($categoryIds === []) {
            return $query;
        }

        return $query->where(function ($inner) use ($categoryIds) {
            $inner
                ->whereIn('category_id', $categoryIds)
                ->orWhereHas('categories', fn ($categories) => $categories->whereIn('categories.id', $categoryIds));
        });
    }

    public function syncCategoryLinks(array $categoryIds, ?int $primaryCategoryId = null, string $source = 'admin', array $confidenceById = []): void
    {
        $categoryIds = collect($categoryIds)
            ->push($primaryCategoryId)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $sync = $categoryIds->mapWithKeys(function (int $categoryId) use ($primaryCategoryId, $source, $confidenceById) {
            return [
                $categoryId => [
                    'is_primary' => $primaryCategoryId ? $categoryId === (int) $primaryCategoryId : false,
                    'source' => $source,
                    'confidence' => max(0, min(100, (int) ($confidenceById[$categoryId] ?? 100))),
                ],
            ];
        })->all();

        if ($primaryCategoryId && (int) $this->category_id !== (int) $primaryCategoryId) {
            $this->forceFill(['category_id' => (int) $primaryCategoryId])->saveQuietly();
        }

        $this->categories()->sync($sync);
    }

    public function getBadgeAttribute(): ?string
    {
        return $this->is_discounted
            ? discountPercent($this->old_price, $this->price)
            : ($this->is_new ? mb_strtoupper(__('ui.new')) : ($this->is_bestseller ? mb_strtoupper(__('ui.top')) : null));
    }

    public function getDisplayNameAttribute(): string
    {
        if (app()->isLocale('ru')) {
            foreach ([$this->name_ru, $this->name] as $candidate) {
                $name = self::withoutSourceStoreName((string) $candidate);

                if ($name !== '' && preg_match('/\p{Cyrillic}/u', $name) === 1) {
                    return $name;
                }
            }

            return __('ui.product_name_fallback', ['sku' => $this->sku]);
        }

        $name = self::withoutSourceStoreName((string) $this->name_ro);

        return $name !== '' ? $name : __('ui.product_name_fallback', ['sku' => $this->sku]);
    }

    private static function withoutSourceStoreName(string $value): string
    {
        $value = preg_replace(
            '/\b(?:https?:\/\/)?(?:www\.)?(?:tristool(?:\.md)?|maximum\.md|maxim\.md)\b\s*(?:[-–—:|]\s*)?/iu',
            '',
            $value,
        ) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value, " \t\n\r\0\x0B-–—:|");
    }

    public function getDisplayDescriptionAttribute(): string
    {
        $candidates = app()->isLocale('ru')
            ? [$this->description_ru, $this->description, $this->short_description_ru, $this->short_description]
            : [$this->description_ro, $this->short_description_ro];

        foreach ($candidates as $candidate) {
            $description = trim((string) $candidate);

            if ($description !== '' && (! app()->isLocale('ru') || preg_match('/\p{Cyrillic}/u', $description) === 1)) {
                return $description;
            }
        }

        return __('ui.product_description_fallback', [
            'name' => $this->display_name,
            'sku' => $this->sku,
        ]);
    }

    public function getDisplayAttributesAttribute(): array
    {
        $attributes = collect($this->getAttributeValue('attributes') ?? [])
            ->filter(fn ($value, $key) => is_scalar($value)
                && trim((string) $key) !== ''
                && trim((string) $value) !== ''
                && ! $this->isHiddenDisplayAttribute((string) $key))
            ->mapWithKeys(function ($value, $key) {
                $localizedKey = $this->localizedAttributeKey(trim((string) $key));
                $localizedValue = $this->localizedAttributeValue(trim((string) $value));

                return $localizedKey !== null && $localizedValue !== null
                    ? [$localizedKey => $localizedValue]
                    : [];
            });

        $attributes = $attributes->all();

        foreach ([
            app()->isLocale('ru') ? 'Вес' : 'Greutate' => $this->weight,
            app()->isLocale('ru') ? 'Габариты' : 'Dimensiuni' => $this->dimensions,
        ] as $key => $value) {
            if (filled($value) && ! array_key_exists($key, $attributes)) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    private function isHiddenDisplayAttribute(string $key): bool
    {
        $key = mb_strtolower(trim((string) preg_replace('/[\s:_-]+/u', ' ', $key)));

        return in_array($key, [
            'brand',
            'бренд',
            'marca',
            'sku',
            'артикул',
            'cod produs',
            'group',
            'группа',
            'grup',
            'retail price',
            'price retail',
            'розничная цена',
            'цена розничная',
            'pret retail',
            'preț retail',
            'price source',
            'источник цены',
            'sursa pretului',
            'sursa prețului',
            'select all',
            'выбрать все',
            'selecteaza tot',
            'selectează tot',
            'warranty',
            'гарантия',
            'garantie',
            'garanție',
        ], true);
    }

    public function getDisplayPackageContentsAttribute(): array
    {
        return collect($this->getAttributeValue('package_contents') ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== ''
                && ! preg_match('/draft parser preview|lorem ipsum|\btodo\b|\btbd\b/i', $value)
                && (! app()->isLocale('ro') || preg_match('/\p{Cyrillic}/u', $value) !== 1)
                && (preg_match('/\p{Cyrillic}/u', $value) === 1
                    || preg_match('/\b[a-z]{3,}\b/', $value) !== 1))
            ->values()
            ->all();
    }

    public function getDisplayWarrantyAttribute(): string
    {
        $warranty = trim((string) ($this->warranty ?: '12 luni'));

        if (app()->isLocale('ru')) {
            return str_replace(['luni', 'luna'], ['мес.', 'мес.'], $warranty);
        }

        return $warranty;
    }

    private function localizedAttributeKey(string $key): ?string
    {
        if (app()->isLocale('ru')) {
            if (preg_match('/\p{Cyrillic}/u', $key) === 1) {
                return $key;
            }

            return self::ATTRIBUTE_KEYS_RU[$key] ?? null;
        }

        return self::ATTRIBUTE_KEYS_RO[$key] ?? null;
    }

    private function localizedAttributeValue(string $value): ?string
    {
        if (app()->isLocale('ru')) {
            return self::ATTRIBUTE_VALUES_RU[$value] ?? $value;
        }

        $value = self::ATTRIBUTE_VALUES_RO[$value] ?? $value;
        $value = strtr($value, [
            'об/мин' => 'rot/min',
            'ход/мин' => 'curse/min',
            'л/мин' => 'l/min',
            'мм' => 'mm',
            'см' => 'cm',
            'кг' => 'kg',
            'литр' => 'litri',
            'бар' => 'bar',
        ]);

        return preg_match('/\p{Cyrillic}/u', $value) === 1 ? null : $value;
    }
}
