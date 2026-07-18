<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogStructureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tree() as $index => $node) {
            $this->syncNode($node, null, $index + 1);
        }

        $this->moveProducts();
    }

    private function syncNode(array $node, ?int $parentId, int $sortOrder): Category
    {
        $category = Category::updateOrCreate(
            ['slug' => $node['slug']],
            [
                'parent_id' => $parentId,
                'name' => $node['name'],
                'name_ro' => $node['name'],
                'description' => $node['description'] ?? null,
                'description_ro' => $node['description'] ?? null,
                'image' => $node['image'] ?? null,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );

        foreach (($node['children'] ?? []) as $index => $child) {
            $this->syncNode($child, $category->id, $index + 1);
        }

        return $category;
    }

    private function moveProducts(): void
    {
        $rules = [
            'tinichigerie-si-richtuire' => ['рихтов', 'кузов', 'tinichig', 'vopsire', '9cf', 'сварочных точек'],
            'lipire-si-consumabile' => ['паяльник', 'припой', 'жало', 'олово', '6bc', '6bf', '6bd'],
            'testere-electrice-si-indicatoare' => ['индикатор напряжения', 'тестер цепей', 'бесконтактный', '6cb31', '9dc'],
            'clesti-electrician-si-cabluri' => ['кримпер', 'стриппер', 'каблерез', 'обжим', 'клемм', 'изоляц', 'провод', 'ножницы электрика', '6ab', '67f', '67b', '675', '676'],
            'instrumente-de-masurare' => ['subler', 'masuratori', 'измер', 'глубиномер', 'штанген', '77142', '9bm'],
            'scule-motor-frane-suspensie' => ['тормоз', 'суппорт', 'шланг', 'хомут', 'шрус', 'топливного насоса', 'поршневых колец', 'распредвала', 'шкива', 'выхлопной', '9bc', '9aa', '9bb', '9ag', '9ac', '9at'],
            'extractoare-si-scule-speciale' => ['extractor', 'съемник', 'съёмник', 'форсунк', 'подшип', 'шаровых', 'filtru ulei', '3209', '9ae', '9ba', '9be', '796'],
            'pistoale-impact-cu-acumulator' => ['аккумулятор', '18в', 'dw-', 'ds-', 'dh-'],
            'burghie-pneumatice' => ['дрель пневмат', 'дрель для сверления', 'qe-'],
            'surubelnite-pneumatice' => ['отвёртка пневмат', 'отвертка пневмат', 'ra-'],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['ножовка пневм', 'ножницы пневмат', 'машинка отрезная', 'qg-', 'qd-', 'qc-'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['заклёпоч', 'заклепоч', 'степлер', 'гвоздезабив', 'pa-', 'sj-', 'su-'],
            'pistoale-suflat-si-sablare' => ['продувоч', 'пескоструй', 'jc-', 'sx-'],
            'accesorii-pneumatice' => ['быстроразъём', 'фиксатор для зубил', 'набор зубил', 'сменная подошва', 'лента абразив', 'диск наждачный', 'диск полировочный', 'круг отрезной', 'пила сменная', 'qb-91', 'qb-93', 'qb-94', 'sc-2', 'qd-9'],
            'pistoale-pneumatice-si-impact' => ['pistol pneumatic', 'pistol impact', 'пневмогайковерт', 'пистолет пневматический', 'impact', 'nc-'],
            'polizoare-si-slefuitoare-pneumatice' => ['polizor', 'slefuitor', 'шлифмашинка', 'шлифовальная', 'полировочная', 'углошлифовальная', 'qt-', 'qb-'],
            'pistoale-pentru-silicon-si-gresare' => ['silicon', 'gresare', 'vaselina', 'смазк', 'силикон', 'sk-', 'sg-'],
            'dispozitive-pneumatice-service' => ['aerisire', 'frane', 'sm-'],
            'compresoare' => ['compresor', 'компрессор', 'ac-'],
            'dulapuri-si-organizare' => ['carucior', 'тележка', 'dulap', 'шкаф', 'организац'],
            'seturi-de-scule' => ['set de scule', 'trusa', 'truse', 'набор инструментов'],
            'chei-dinamometrice' => ['dinamometric', 'динамометр'],
            'tubulare-si-clichete' => ['tubulare', 'clichet', 'трещотка', 'головка', 'вороток', 'насадка бита', 'свечная', 'locknut'],
            'chei-si-surubelnite' => ['surubelnita', 'отвертка', 'отвёртка', 'рукоятка отвертки', 'наконечник', 'намагнич'],
            'clesti-si-instrumente-taiere' => ['клещи', 'кусачки', 'ножницы универсальные', 'шабер'],
            'accesorii-si-consumabile' => ['аксессуар', 'расход', 'лезвие запасное'],
        ];

        foreach ($rules as $slug => $needles) {
            $category = Category::where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            Product::query()
                ->where(function ($query) use ($needles) {
                    foreach ($needles as $needle) {
                        $query->orWhere('name', 'like', "%{$needle}%")
                            ->orWhere('sku', 'like', "%{$needle}%")
                            ->orWhere('short_description', 'like', "%{$needle}%")
                            ->orWhere('description', 'like', "%{$needle}%");
                    }
                })
                ->update(['category_id' => $category->id]);
        }

        $pneumaticParent = Category::where('slug', 'scule-pneumatice')->first();
        $otherPneumatic = Category::where('slug', 'alte-scule-pneumatice')->first();

        if ($pneumaticParent && $otherPneumatic) {
            Product::where('category_id', $pneumaticParent->id)
                ->update(['category_id' => $otherPneumatic->id]);
        }
    }

    private function tree(): array
    {
        return [
            [
                'slug' => 'echipamente-pentru-service',
                'name' => 'Echipamente pentru service',
                'description' => 'Utilaje si echipamente pentru service auto.',
                'image' => '/images/categories/echipamente-pentru-service.png',
                'children' => [
                    [
                        'slug' => 'echipamente-service',
                        'name' => 'Echipamente service',
                        'description' => 'Echipamente profesionale pentru service auto.',
                        'image' => '/images/categories/echipamente-service.svg',
                    ],
                    [
                        'slug' => 'cricuri-si-ridicare',
                        'name' => 'Cricuri si ridicare',
                        'description' => 'Cricuri, suporti si echipamente pentru ridicare.',
                        'image' => '/images/categories/cric-ridicare.svg',
                    ],
                ],
            ],
            [
                'slug' => 'instrumente-si-mobilier',
                'name' => 'Instrumente si mobilier',
                'description' => 'Scule, instrumente si mobilier pentru atelier.',
                'image' => '/images/categories/seturi-scule.svg',
                'children' => [
                    [
                        'slug' => 'mobilier-pentru-service',
                        'name' => 'Mobilier pentru service si sisteme de stocare',
                        'description' => 'Dulapuri, bancuri, carucioare si organizare atelier.',
                        'image' => '/images/categories/mobilier-pentru-service.png',
                        'children' => [
                            [
                                'slug' => 'dulapuri-si-organizare',
                                'name' => 'Dulapuri si organizare',
                                'description' => 'Dulapuri, carucioare si sisteme de organizare pentru atelier.',
                                'image' => '/images/categories/dulapuri-organizare.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'scule-speciale-auto',
                        'name' => 'Scule speciale auto',
                        'description' => 'Scule dedicate pentru lucrari auto si interventii speciale.',
                        'image' => '/images/categories/scule-speciale-auto.png',
                        'children' => [
                            [
                                'slug' => 'extractoare-si-scule-speciale',
                                'name' => 'Extractoare si scule speciale',
                                'description' => 'Extractoare, dispozitive pentru filtre si scule dedicate.',
                                'image' => '/images/categories/echipamente-service.svg',
                            ],
                            [
                                'slug' => 'scule-motor-frane-suspensie',
                                'name' => 'Scule motor, frane si suspensie',
                                'description' => 'Scule pentru motor, frane, suspensie, coliere si interventii auto speciale.',
                                'image' => '/images/categories/echipamente-service.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'instrument-manual',
                        'name' => 'Instrument manual',
                        'description' => 'Scule manuale pentru mecanica, service si garaj.',
                        'image' => '/images/categories/instrument-manual.png',
                        'children' => [
                            [
                                'slug' => 'seturi-de-scule',
                                'name' => 'Seturi de scule',
                                'description' => 'Truse complete pentru service, garaj si atelier.',
                                'image' => '/images/categories/seturi-scule.svg',
                            ],
                            [
                                'slug' => 'tubulare-si-clichete',
                                'name' => 'Tubulare si clichete',
                                'description' => 'Tubulare, clichete, prelungitoare si accesorii pentru lucrari mecanice.',
                                'image' => '/images/categories/tubulare-clichete.svg',
                            ],
                            [
                                'slug' => 'chei-si-surubelnite',
                                'name' => 'Chei si surubelnite',
                                'description' => 'Chei, surubelnite si biti pentru lucrari de intretinere si reparatie.',
                                'image' => '/images/categories/chei-surubelnite.svg',
                            ],
                            [
                                'slug' => 'clesti-si-instrumente-taiere',
                                'name' => 'Clesti si instrumente de taiere',
                                'description' => 'Clesti, patent, cuttere si scule manuale pentru taiere si prindere.',
                                'image' => '/images/categories/chei-surubelnite.svg',
                            ],
                            [
                                'slug' => 'chei-dinamometrice',
                                'name' => 'Chei dinamometrice',
                                'description' => 'Chei dinamometrice pentru strangere controlata si lucrari precise.',
                                'image' => '/images/categories/cheie-dinamometrica.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'scule-pneumatice',
                        'name' => 'Instrument pneumatic',
                        'description' => 'Scule pneumatice si accesorii pentru aer comprimat.',
                        'image' => '/images/categories/scule-pneumatice.png',
                        'children' => [
                            [
                                'slug' => 'pistoale-pneumatice-si-impact',
                                'name' => 'Pistoale pneumatice si impact',
                                'description' => 'Pistoale pneumatice si de impact pentru service auto.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'polizoare-si-slefuitoare-pneumatice',
                                'name' => 'Polizoare si slefuitoare pneumatice',
                                'description' => 'Scule pneumatice pentru finisare, debitare si slefuire.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'pistoale-pentru-silicon-si-gresare',
                                'name' => 'Pistoale pentru silicon si gresare',
                                'description' => 'Pistoale pneumatice pentru silicon, vaselina si lubrifiere.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'burghie-pneumatice',
                                'name' => 'Burghie pneumatice',
                                'description' => 'Masini de gaurit pneumatice si accesorii dedicate.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'surubelnite-pneumatice',
                                'name' => 'Surubelnite pneumatice',
                                'description' => 'Surubelnite pneumatice pentru montaj si lucru repetitiv.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'foarfeci-ferastraie-si-debitare-pneumatice',
                                'name' => 'Taiere pneumatica',
                                'description' => 'Foarfeci, ferastraie, masini de debitat si scule pneumatice pentru taiere.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'nituitoare-capsatoare-si-cuie-pneumatice',
                                'name' => 'Nituitoare, capsatoare si cuie',
                                'description' => 'Nituitoare, capsatoare, gvozdezabivatoare si scule pneumatice de fixare.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'pistoale-suflat-si-sablare',
                                'name' => 'Pistoale de suflat si sablare',
                                'description' => 'Pistoale de suflat, sablare si curatare cu aer comprimat.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'accesorii-pneumatice',
                                'name' => 'Accesorii pneumatice',
                                'description' => 'Consumabile, discuri, benzi, talpi si accesorii pentru scule pneumatice.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'dispozitive-pneumatice-service',
                                'name' => 'Dispozitive pneumatice service',
                                'description' => 'Dispozitive cu aer comprimat pentru operatiuni de service.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'alte-scule-pneumatice',
                                'name' => 'Alte scule pneumatice',
                                'description' => 'Accesorii si scule pneumatice diverse pentru atelier.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                            [
                                'slug' => 'compresoare',
                                'name' => 'Compresoare',
                                'description' => 'Compresoare si echipamente de aer comprimat pentru garaj si service.',
                                'image' => '/images/categories/compresor-atelier.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'electroinstrumente',
                        'name' => 'Electroinstrumente',
                        'description' => 'Scule electrice pentru atelier si interventii rapide.',
                        'image' => '/images/categories/electroinstrumente.png',
                    ],
                    [
                        'slug' => 'instrumente-cu-acumulator',
                        'name' => 'Instrumente cu acumulator',
                        'description' => 'Scule mobile cu acumulator pentru service.',
                        'image' => '/images/categories/instrumente-cu-acumulator.png',
                        'children' => [
                            [
                                'slug' => 'pistoale-impact-cu-acumulator',
                                'name' => 'Pistoale impact cu acumulator',
                                'description' => 'Pistoale de impact pe acumulator pentru service mobil.',
                                'image' => '/images/categories/scule-pneumatice.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'instrumente-electromontaj',
                        'name' => 'Instrumente electromontaj',
                        'description' => 'Scule pentru lucrari electrice si montaj.',
                        'image' => '/images/categories/instrumente-electromontaj.png',
                        'children' => [
                            [
                                'slug' => 'testere-electrice-si-indicatoare',
                                'name' => 'Testere electrice si indicatoare',
                                'description' => 'Indicatoare de tensiune, testere de circuite si instrumente de verificare electrica.',
                                'image' => '/images/categories/chei-surubelnite.svg',
                            ],
                            [
                                'slug' => 'clesti-electrician-si-cabluri',
                                'name' => 'Clesti electrician si cabluri',
                                'description' => 'Clesti, stripere, crimpere, cabloreze si scule pentru cabluri.',
                                'image' => '/images/categories/chei-surubelnite.svg',
                            ],
                            [
                                'slug' => 'lipire-si-consumabile',
                                'name' => 'Lipire si consumabile',
                                'description' => 'Ciocane de lipit, varfuri, cositor si accesorii pentru lipire.',
                                'image' => '/images/categories/chei-surubelnite.svg',
                            ],
                        ],
                    ],
                    [
                        'slug' => 'instrumente-de-masurare',
                        'name' => 'Instrumente de masurare',
                        'description' => 'Instrumente de masura si control pentru atelier.',
                        'image' => '/images/categories/instrumente-de-masurare.png',
                    ],
                    [
                        'slug' => 'accesorii-si-consumabile',
                        'name' => 'Accesorii si consumabile',
                        'description' => 'Accesorii si consumabile pentru scule si atelier.',
                        'image' => '/images/categories/accesorii-si-consumabile.png',
                    ],
                ],
            ],
            [
                'slug' => 'sudura-richtuire-vopsire',
                'name' => 'Sudura, tinichigerie si vopsire',
                'description' => 'Echipamente pentru sudura, tinichigerie, pregatire si vopsire.',
                'image' => '/images/categories/sudura-richtuire-vopsire.png',
                'children' => [
                    [
                        'slug' => 'tinichigerie-si-richtuire',
                        'name' => 'Tinichigerie si richtuire',
                        'description' => 'Ciocane de richtuire, scule pentru caroserie si pregatire tinichigerie.',
                        'image' => '/images/categories/echipamente-service.svg',
                    ],
                ],
            ],
            [
                'slug' => 'vulcanizare',
                'name' => 'Vulcanizare',
                'description' => 'Scule si echipamente pentru roti, anvelope si vulcanizare.',
                'image' => '/images/categories/vulcanizare.png',
            ],
        ];
    }
}
