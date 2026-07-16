<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ParserCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncBrands();
        $this->syncCategories();
    }

    private function syncBrands(): void
    {
        foreach ([
            ['King Tony', 'king-tony', '/images/brand/king-tony.png'],
            ['M7 / Mighty Seven', 'm7-mighty-seven', '/images/brand/m7.png'],
            ['JTC', 'jtc', '/images/brand/jtc.jpg'],
            ['Hoegert', 'hoegert', '/images/brand/hoegert.png'],
            ['Torin BIG RED', 'torin-big-red', '/images/brand/torin-big-red.png'],
        ] as [$name, $slug, $logo]) {
            Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => 'Brand importat pentru catalogul MasterScule.',
                    'logo' => $logo,
                    'is_featured' => in_array($slug, ['king-tony', 'm7-mighty-seven', 'torin-big-red'], true),
                    'is_active' => true,
                ]
            );
        }
    }

    private function syncCategories(): void
    {
        foreach ($this->categories() as $index => $category) {
            $parent = isset($category['parent'])
                ? Category::where('slug', $category['parent'])->first()
                : null;

            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'parent_id' => $parent?->id,
                    'name' => $category['name'],
                    'name_ro' => $category['name_ro'] ?? $category['name'],
                    'description' => $category['description'] ?? $category['name'],
                    'description_ro' => $category['description_ro'] ?? ($category['description'] ?? $category['name_ro'] ?? $category['name']),
                    'image' => $category['image'] ?? null,
                    'sort_order' => $category['sort_order'] ?? ($index + 20),
                    'is_active' => true,
                ]
            );
        }
    }

    private function categories(): array
    {
        return collect([
            ['prese-hidraulice', 'echipamente-pentru-service', 'Гидравлические прессы', 'Prese hidraulice'],
            ['cricuri-hidraulice', 'echipamente-pentru-service', 'Гидравлические домкраты', 'Cricuri hidraulice'],
            ['capre-auto-si-suporturi', 'echipamente-pentru-service', 'Автомобильные стойки и опоры', 'Capre auto si suporturi'],
            ['pompe-si-cilindri-hidraulici', 'echipamente-pentru-service', 'Насосы и гидроцилиндры', 'Pompe si cilindri hidraulici'],
            ['echipamente-schimb-ulei', 'echipamente-pentru-service', 'Оборудование для замены масла', 'Echipamente schimb ulei'],
            ['echipamente-spalare-piese', 'echipamente-pentru-service', 'Оборудование для мойки деталей', 'Echipamente spalare piese'],
            ['echipamente-depozitare-manipulare', 'echipamente-pentru-service', 'Хранение и перемещение', 'Echipamente depozitare si manipulare'],
            ['macarale-standuri-suporti-motor', 'echipamente-pentru-service', 'Краны, стенды и опоры двигателя', 'Macarale, standuri si suporti motor'],
            ['surubelnite-si-biti', 'instrument-manual', 'Отвертки и биты', 'Surubelnite si biti'],
            ['capete-tubulare-impact', 'instrument-manual', 'Ударные головки', 'Capete tubulare de impact'],
            ['biti-insertii-adaptoare', 'instrument-manual', 'Биты, вставки и адаптеры', 'Biti, insertii si adaptoare'],
            ['tarozi-filiere-filetare', 'instrument-manual', 'Метчики, плашки и резьба', 'Tarozi, filiere si filetare'],
            ['taiere-pilire-prelucrare', 'instrument-manual', 'Резка, опиловка и обработка', 'Taiere, pilire si prelucrare'],
            ['chei-pneumatice', 'scule-pneumatice', 'Пневмогайковерты', 'Chei pneumatice'],
            ['clichete-pneumatice', 'scule-pneumatice', 'Пневмотрещотки', 'Clichete pneumatice'],
            ['ciocane-pneumatice', 'scule-pneumatice', 'Пневмомолотки', 'Ciocane pneumatice'],
            ['furtunuri-cuple-accesorii', 'scule-pneumatice', 'Шланги, муфты и аксессуары', 'Furtunuri, cuple si accesorii'],
            ['consumabile-pentru-scule-pneumatice', 'scule-pneumatice', 'Расходники для пневмоинструмента', 'Consumabile pentru scule pneumatice'],
            ['masini-gaurit-insurubat', 'electroinstrumente', 'Дрели и шуруповерты', 'Masini de gaurit si insurubat'],
            ['polizoare', 'electroinstrumente', 'Болгарки и шлифмашины', 'Polizoare'],
            ['accesorii-scule-electrice', 'electroinstrumente', 'Оснастка для электроинструмента', 'Accesorii pentru scule electrice'],
            ['chei-cu-acumulator', 'instrumente-cu-acumulator', 'Аккумуляторные гайковерты', 'Chei cu acumulator'],
            ['baterii-incarcatoare', 'instrumente-cu-acumulator', 'Аккумуляторы и зарядные устройства', 'Baterii si incarcatoare'],
            ['scule-pentru-motor', 'scule-speciale-auto', 'Инструмент для двигателя', 'Scule pentru motor'],
            ['scule-pentru-frane', 'scule-speciale-auto', 'Инструмент для тормозной системы', 'Scule pentru frane'],
            ['scule-pentru-suspensie', 'scule-speciale-auto', 'Инструмент для подвески', 'Scule pentru suspensie'],
            ['extractoare-si-prese', 'scule-speciale-auto', 'Съемники и прессы', 'Extractoare si prese'],
            ['scule-pentru-filtre-ulei', 'scule-speciale-auto', 'Инструмент для масляных фильтров', 'Scule pentru filtre ulei'],
            ['scule-pentru-roti-vulcanizare', 'scule-speciale-auto', 'Инструмент для колес и шиномонтажа', 'Scule pentru roti si vulcanizare'],
            ['scule-vehicule-grele', 'scule-speciale-auto', 'Инструмент для грузового транспорта', 'Scule pentru vehicule grele'],
            ['diagnoza-auto', 'scule-speciale-auto', 'Автодиагностика', 'Diagnostic auto'],
            ['sublere-micrometre-comparatoare', 'instrumente-de-masurare', 'Штангенциркули, микрометры и индикаторы', 'Sublere, micrometre si comparatoare'],
            ['multimetre-testere', 'instrumente-de-masurare', 'Мультиметры и тестеры', 'Multimetre si testere'],
            ['rulete-nivele', 'instrumente-de-masurare', 'Рулетки и уровни', 'Rulete si nivele'],
            ['instrumente-control-verificare', 'instrumente-de-masurare', 'Контроль и проверка', 'Instrumente de control si verificare'],
            ['discuri-perii-abrazive', 'accesorii-si-consumabile', 'Диски, щетки и абразивы', 'Discuri, perii si abrazive'],
            ['burghie-freze', 'accesorii-si-consumabile', 'Сверла и фрезы', 'Burghie si freze'],
            ['biti-si-capete', 'accesorii-si-consumabile', 'Биты и насадки', 'Biti si capete'],
            ['cutite-lame-rezerve', 'accesorii-si-consumabile', 'Ножи, лезвия и запасные части', 'Cutite, lame si rezerve'],
            ['accesorii-universale', 'accesorii-si-consumabile', 'Универсальные аксессуары', 'Accesorii universale'],
            ['echipament-protectie', null, 'Средства защиты', 'Echipament de protectie'],
            ['manusi', 'echipament-protectie', 'Перчатки', 'Manusi'],
            ['ochelari-protectie-fata', 'echipament-protectie', 'Очки и защита лица', 'Ochelari si protectie fata'],
            ['imbracaminte-lucru', 'echipament-protectie', 'Рабочая одежда', 'Imbracaminte de lucru'],
            ['accesorii-protectie', 'echipament-protectie', 'Защитные аксессуары', 'Accesorii de protectie'],
        ])->map(fn ($row) => [
            'slug' => $row[0],
            'parent' => $row[1],
            'name' => $row[2],
            'name_ro' => $row[3],
            'description' => $row[2],
            'description_ro' => $row[3],
            'image' => '/images/categories/echipamente-service.svg',
        ])->all();
    }
}
