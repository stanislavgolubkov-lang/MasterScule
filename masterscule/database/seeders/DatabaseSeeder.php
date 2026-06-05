<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $userRole = Role::create(['name' => 'user', 'label' => 'Client']);

        $admin = User::create([
            'name' => 'MasterScule Admin',
            'email' => 'admin@masterscule.ro',
            'phone' => '0724 123 456',
            'role' => 'admin',
            'customer_type' => 'company',
            'company_name' => 'MasterScule.ro',
            'country' => 'Romania',
            'city' => 'Voluntari',
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach($adminRole);

        $customer = User::create([
            'name' => 'Andrei Popescu',
            'email' => 'andrei.popescu@example.com',
            'phone' => '0724 123 456',
            'role' => 'user',
            'customer_type' => 'service',
            'company_name' => 'Popescu Service SRL',
            'country' => 'Romania',
            'city' => 'Voluntari',
            'password' => Hash::make('password'),
        ]);
        $customer->roles()->attach($userRole);

        $categories = collect([
            ['Seturi de scule', 'Seturi de scule', 'seturi-de-scule', 'Truse complete pentru service, garaj si atelier.'],
            ['Tubulare si clichete', 'Tubulare si clichete', 'tubulare-si-clichete', 'Tubulare, clichete, prelungitoare si accesorii pentru lucrari mecanice.'],
            ['Chei si surubelnite', 'Chei si surubelnite', 'chei-si-surubelnite', 'Chei, surubelnite si biti pentru lucrari de intretinere si reparatie.'],
            ['Scule pneumatice', 'Scule pneumatice', 'scule-pneumatice', 'Pistoale pneumatice, accesorii si scule cu aer comprimat pentru service.'],
            ['Chei dinamometrice', 'Chei dinamometrice', 'chei-dinamometrice', 'Chei dinamometrice pentru strangere controlata si lucrari precise.'],
            ['Cricuri si ridicare', 'Cricuri si ridicare', 'cricuri-si-ridicare', 'Cricuri, suporti si echipamente pentru ridicare.'],
            ['Dulapuri si organizare', 'Dulapuri si organizare', 'dulapuri-si-organizare', 'Dulapuri, carucioare si sisteme de organizare pentru atelier.'],
            ['Compresoare', 'Compresoare', 'compresoare', 'Compresoare si echipamente de aer comprimat pentru garaj si service.'],
            ['Echipamente service', 'Echipamente service', 'echipamente-service', 'Echipamente profesionale pentru service auto.'],
        ])->mapWithKeys(function ($item, $index) {
            [$nameRo, $name, $slug, $description] = $item;

            return [$slug => Category::create([
                'name' => $name,
                'name_ro' => $nameRo,
                'slug' => $slug,
                'description' => $description,
                'description_ro' => $description,
                'sort_order' => $index + 1,
                'is_active' => true,
            ])];
        });

        $kingTony = Brand::create([
            'name' => 'King Tony',
            'slug' => 'king-tony',
            'description' => 'Brand profesional de scule pentru service-uri auto si ateliere.',
            'logo' => '/images/brand/king-tony.png',
            'is_featured' => true,
        ]);

        $m7 = Brand::create([
            'name' => 'M7 / Mighty Seven',
            'slug' => 'm7-mighty-seven',
            'description' => 'Scule pneumatice si echipamente profesionale pentru lucrari intensive.',
            'logo' => '/images/brand/m7.png',
            'is_featured' => true,
        ]);

        foreach ($this->products($kingTony, $m7, $categories) as $data) {
            Product::create($data);
        }

        Banner::create([
            'title' => 'Scule profesionale pentru service si garaj',
            'subtitle' => 'Alege scule, seturi, echipamente si accesorii pentru atelier, service auto si pasionati.',
            'image' => '/images/products/king-tony-7596mr.jpg',
            'button_label' => 'Vezi catalogul',
            'button_url' => '/catalog',
            'is_active' => true,
        ]);

        foreach ([
            ['about', 'Despre noi', 'MasterScule.ro este creat pentru mecanici, service-uri auto si profesionisti care au nevoie de scule fiabile si usor de ales.'],
            ['delivery-payment', 'Livrare si plata', 'Livram produse in toata Romania. Plata se poate face la livrare, prin transfer bancar sau online cand integrarea va fi activata.'],
            ['warranty', 'Garantie', 'Produsele beneficiaza de garantie conform conditiilor producatorului si legislatiei in vigoare.'],
            ['returns', 'Retur si rambursare', 'Ai 14 zile pentru retur conform politicii comerciale si legislatiei aplicabile.'],
            ['contacts', 'Contact', 'Telefon: 0724 123 456. Email: contact@masterscule.ro. Program: luni-vineri 08:00 - 17:00.'],
            ['privacy-policy', 'Politica de confidentialitate', 'Datele clientilor sunt folosite pentru procesarea comenzilor, livrare si comunicari comerciale acceptate.'],
            ['terms', 'Termeni si conditii', 'Utilizarea site-ului si plasarea comenzilor presupun acceptarea termenilor comerciali MasterScule.ro.'],
            ['cookie-policy', 'Cookie Policy', 'Site-ul poate folosi cookie-uri pentru functionalitate, analiza si imbunatatirea experientei de cumparare.'],
        ] as [$slug, $title, $content]) {
            Page::create(compact('slug', 'title', 'content'));
        }
    }

    private function products(Brand $kingTony, Brand $m7, $categories): array
    {
        $items = [
            [$kingTony, 'seturi-de-scule', 'Set de scule King Tony 7596MR', 'king-tony-7596mr', '7596MR', 'Trusa profesionala cu 96 piese pentru service si garaj.', 'Set profesional King Tony pentru lucrari mecanice generale. Include tubulare, clichete, extensii si accesorii uzuale pentru interventii rapide in atelier sau garaj.', 1999, 2399, '/images/products/king-tony-7596mr.jpg', true, true, false, ['Numar piese' => '96', 'Material' => 'Otel crom-vanadiu', 'Utilizare' => 'Profesional', 'Greutate' => '7,40 kg']],
            [$m7, 'scule-pneumatice', 'Pistol pneumatic M7 NC-4233', 'm7-nc-4233', 'NC-4233', 'Pistol pneumatic compact pentru roti si lucrari de service.', 'Pistol pneumatic M7 pentru operatiuni de insurubare si desurubare in service auto. Recomandat pentru utilizare cu compresor si accesorii compatibile.', 799, 889, '/images/products/product-placeholder-m7.svg', false, false, true, ['Tip' => 'Pistol pneumatic', 'Utilizare' => 'Service auto', 'Presiune de lucru' => '6,3 bar', 'Antrenare' => '1/2 inch']],
            [$kingTony, 'chei-dinamometrice', 'Cheie dinamometrica King Tony 34262-1DG', 'king-tony-34262-1dg', '34262-1DG', 'Cheie dinamometrica pentru strangere controlata.', 'Cheie dinamometrica King Tony pentru lucrari unde cuplul de strangere trebuie controlat precis. Potrivita pentru service, roti si interventii mecanice regulate.', 599, null, '/images/products/king-tony-34262-1dg.jpg', false, false, true, ['Tip' => 'Cheie dinamometrica', 'Utilizare' => 'Strangere controlata', 'Material' => 'Otel', 'Garantie' => '24 luni']],
            [$kingTony, 'dulapuri-si-organizare', 'Carucior scule King Tony 87G07-7B', 'king-tony-87g07-7b', '87G07-7B', 'Carucior cu sertare pentru organizarea atelierului.', 'Carucior de scule King Tony pentru organizarea eficienta a sculelor in service sau atelier. Constructie robusta, sertare multiple si spatiu clar pentru lucru repetat.', 2999, null, '/images/products/product-placeholder-toolbox.svg', false, false, false, ['Tip' => 'Carucior scule', 'Sertare' => '7', 'Utilizare' => 'Atelier', 'Structura' => 'Metalica']],
            [$m7, 'compresoare', 'Compresor M7 AC-100/3M', 'm7-ac-100-3m', 'AC-100/3M', 'Compresor pentru alimentarea sculelor pneumatice.', 'Compresor M7 pentru service si garaj, pregatit pentru lucrari cu scule pneumatice si alimentare constanta cu aer comprimat.', 1599, null, '/images/products/product-placeholder-compressor.svg', false, true, false, ['Tip' => 'Compresor', 'Utilizare' => 'Scule pneumatice', 'Alimentare' => '230V', 'Garantie' => '24 luni']],
            [$kingTony, 'seturi-de-scule', 'Set reparatie filet King Tony 11311MQ02', 'king-tony-11311mq02', '11311MQ02', 'Set pentru refacerea filetelor M5-M10.', 'Set King Tony pentru refacerea filetelor uzuale in atelier auto. Potrivit pentru interventii rapide pe componente mecanice si lucrari de mentenanta.', 599, null, '/images/products/king-tony-11311mq02.jpg', true, false, true, ['Tip' => 'Set reparatie filet', 'Dimensiuni' => 'M5, M6, M8, M10', 'Utilizare' => 'Service auto', 'Brand' => 'King Tony']],
            [$kingTony, 'tubulare-si-clichete', 'Set tubulare antifurt VAG King Tony 9BW0120', 'king-tony-9bw0120', '9BW0120', 'Set 20 piese pentru suruburi si piulite antifurt VAG.', 'Set special King Tony pentru lucrari pe roti cu suruburi antifurt. Recomandat pentru service-uri care lucreaza des cu grupul VAG.', 705, 779, '/images/products/king-tony-9bw0120.jpg', true, false, false, ['Numar piese' => '20', 'Utilizare' => 'VAG', 'Tip' => 'Tubulare speciale', 'Material' => 'Otel aliat']],
            [$kingTony, 'tubulare-si-clichete', 'Set tubulare antifurt BMW King Tony 9BW0220', 'king-tony-9bw0220', '9BW0220', 'Set 20 piese pentru suruburi si piulite antifurt BMW.', 'Set de tubulare speciale King Tony pentru roti BMW, gandit pentru service-uri auto si vulcanizari cu volum mare de lucru.', 659, null, '/images/products/king-tony-9bw0220.jpg', false, true, false, ['Numar piese' => '20', 'Utilizare' => 'BMW', 'Tip' => 'Tubulare speciale', 'Material' => 'Otel aliat']],
            [$kingTony, 'chei-dinamometrice', 'Cheie dinamometrica King Tony 34362-2DG', 'king-tony-34362-2dg', '34362-2DG', 'Cheie dinamometrica 3/8 inch, 20-100 Nm.', 'Cheie dinamometrica King Tony din seria Exact pentru strangere controlata in intervalul 20-100 Nm. Utila in service, mecanica generala si interventii de precizie.', 416, null, '/images/products/king-tony-34362-2dg.jpg', true, false, true, ['Antrenare' => '3/8 inch', 'Cuplu' => '20-100 Nm', 'Serie' => 'Exact', 'Garantie' => '24 luni']],
            [$kingTony, 'chei-si-surubelnite', 'Subler digital King Tony 77142-061-1', 'king-tony-77142-061-1', '77142-061-1', 'Instrument digital pentru masuratori rapide in atelier.', 'Subler digital King Tony pentru masuratori precise ale pieselor, componentelor si consumabilelor din service.', 134, null, '/images/products/king-tony-77142-061-1.jpg', false, false, true, ['Tip' => 'Subler digital', 'Utilizare' => 'Masurare', 'Afisaj' => 'Digital', 'Brand' => 'King Tony']],
            [$kingTony, 'chei-si-surubelnite', 'Extractor filtru ulei lant King Tony 3209-220', 'king-tony-3209-220', '3209-220', 'Extractor cu lant pentru filtre de ulei 60-220 mm.', 'Extractor King Tony pentru demontarea filtrelor de ulei cu diametre variate. Constructie simpla, robusta si potrivita pentru service auto.', 75, null, '/images/products/king-tony-3209-220.jpg', false, false, false, ['Tip' => 'Extractor filtru ulei', 'Interval' => '60-220 mm', 'Antrenare' => '1/2 inch', 'Utilizare' => 'Service auto']],
            [$kingTony, 'chei-si-surubelnite', 'Extractor filtru ulei King Tony 9AE433', 'king-tony-9ae433', '9AE433', 'Extractor cu trei brate pentru filtre de ulei.', 'Extractor King Tony cu trei brate pentru demontarea filtrelor de ulei in spatii de lucru variate.', 48, null, '/images/products/king-tony-9ae433.jpg', false, false, false, ['Tip' => 'Extractor filtru', 'Interval' => '65-120 mm', 'Utilizare' => 'Intretinere auto', 'Brand' => 'King Tony']],
            [$kingTony, 'tubulare-si-clichete', 'Set tubulare KM Locknut King Tony 7K09MP', 'king-tony-7k09mp', '7K09MP', 'Set 9 piese pentru piulite KM Locknut.', 'Set King Tony pentru lucrari speciale la piulite KM Locknut, potrivit pentru ateliere care au nevoie de scule dedicate.', 1341, null, '/images/products/king-tony-7k09mp.jpg', false, true, false, ['Numar piese' => '9', 'Tip' => 'Tubulare speciale', 'Utilizare' => 'KM Locknut', 'Brand' => 'King Tony']],
            [$m7, 'scule-pneumatice', 'Pistol pneumatic M7 NC-4255Q', 'm7-nc-4255q', 'NC-4255Q', 'Pistol pneumatic de impact 1/2 inch, 1627 Nm.', 'Pistol pneumatic M7 cu cuplu ridicat pentru service-uri auto, vulcanizari si lucrari intense de desfacere a piulitelor.', 750, 829, '/images/products/m7-nc-4255q.jpg', true, true, true, ['Antrenare' => '1/2 inch', 'Cuplu maxim' => '1627 Nm', 'Tip' => 'Pistol pneumatic', 'Utilizare' => 'Service auto']],
            [$m7, 'scule-pneumatice', 'Impact acumulator M7 DS-203A', 'm7-ds-203a', 'DS-203A', 'Impact 18V cu incarcator si acumulator 5Ah.', 'Impact M7 pe acumulator pentru lucrari mobile in service si atelier. Configuratie pregatita pentru lucru fara compresor.', 1295, null, '/images/products/m7-ds-203a.jpg', true, false, true, ['Tensiune' => '18V', 'Cuplu' => '270 Nm', 'Acumulator' => '5Ah', 'Tip' => 'Impact acumulator']],
            [$m7, 'scule-pneumatice', 'Pistol impact acumulator M7 DW-404', 'm7-dw-404', 'DW-404', 'Pistol impact de impact 1/2 inch, 900 Nm.', 'Pistol de impact M7 pentru utilizare profesionala in service. Recomandat pentru operatiuni rapide unde mobilitatea conteaza.', 329, null, '/images/products/m7-dw-404.jpg', false, false, true, ['Antrenare' => '1/2 inch', 'Cuplu' => '900 Nm', 'Tensiune' => '18V', 'Tip' => 'Pistol impact acumulator']],
            [$m7, 'scule-pneumatice', 'Pistol impact acumulator M7 DW-406', 'm7-dw-406', 'DW-406', 'Pistol impact de impact 1/2 inch, 1600 Nm.', 'Model M7 cu cuplu mare pentru lucrari solicitante in service auto si vulcanizare.', 648, 699, '/images/products/m7-dw-406.jpg', true, true, false, ['Antrenare' => '1/2 inch', 'Cuplu' => '1600 Nm', 'Tensiune' => '18V', 'Tip' => 'Pistol impact acumulator']],
            [$m7, 'scule-pneumatice', 'Mini polizor pneumatic M7 QT-102', 'm7-qt-102', 'QT-102', 'Mini masina pneumatica 7000 rpm.', 'Mini polizor pneumatic M7 pentru finisare, debitare usoara si lucrari de atelier in spatii inguste.', 357, null, '/images/products/m7-qt-102.jpg', false, false, true, ['Turatie' => '7000 rpm', 'Tip' => 'Polizor pneumatic', 'Utilizare' => 'Atelier', 'Brand' => 'M7']],
            [$m7, 'scule-pneumatice', 'Pistol pneumatic silicon M7 SK-1010', 'm7-sk-1010', 'SK-1010', 'Pistol pneumatic pentru silicon 215 x 50 mm.', 'Pistol pneumatic M7 pentru aplicarea controlata a siliconului si materialelor similare in atelier.', 134, null, '/images/products/m7-sk-1010.jpg', false, false, false, ['Tip' => 'Pistol pneumatic silicon', 'Dimensiune' => '215 x 50 mm', 'Utilizare' => 'Atelier', 'Brand' => 'M7']],
            [$m7, 'scule-pneumatice', 'Pistol pneumatic gresare M7 SG-501', 'm7-sg-501', 'SG-501', 'Pistol pneumatic pentru vaselina 500 ml.', 'Pistol pneumatic M7 pentru gresare cu alimentare constanta, util pentru intretinere si lucrari repetitive.', 432, null, '/images/products/m7-sg-501.jpg', false, false, false, ['Volum' => '500 ml', 'Tip' => 'Pistol gresare', 'Alimentare' => 'Pneumatic', 'Brand' => 'M7']],
            [$m7, 'echipamente-service', 'Dispozitiv aerisire frane M7 SM-0503', 'm7-sm-0503', 'SM-0503', 'Dispozitiv pentru aerisirea sistemului de franare.', 'Dispozitiv M7 pentru lucrari la sistemul de franare, util in service-uri care executa mentenanta periodica.', 659, null, '/images/products/m7-sm-0503.jpg', false, true, false, ['Tip' => 'Dispozitiv frane', 'Capacitate' => '3L / 1L', 'Utilizare' => 'Sistem franare', 'Brand' => 'M7']],
        ];

        return array_map(function ($product) use ($categories) {
            [$brand, $categorySlug, $name, $slug, $sku, $short, $description, $price, $oldPrice, $image, $featured, $bestseller, $new, $attributes] = $product;

            return [
                'brand_id' => $brand->id,
                'category_id' => $categories[$categorySlug]->id,
                'name' => $name,
                'name_ro' => $name,
                'slug' => $slug,
                'sku' => $sku,
                'short_description' => $short,
                'description' => $description,
                'description_ro' => $description,
                'price' => $price,
                'old_price' => $oldPrice,
                'currency' => 'RON',
                'stock_quantity' => 12,
                'stock_status' => 'in_stock',
                'main_image' => $image,
                'gallery' => [$image],
                'attributes' => $attributes,
                'package_contents' => ['Produs principal', 'Ambalaj / cutie', 'Documentatie tehnica'],
                'rating' => 4.8,
                'reviews_count' => random_int(11, 45),
                'is_active' => true,
                'is_featured' => $featured,
                'is_bestseller' => $bestseller,
                'is_new' => $new,
                'is_discounted' => $oldPrice !== null,
                'warranty' => '24 luni',
                'meta_title' => $name.' | MasterScule.ro',
                'meta_description' => Str::limit($short, 150),
            ];
        }, $items);
    }
}

