<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roTitles = json_decode(<<<'JSON'
{
  "11311MQ02": "Set pentru repararea filetelor KING TONY 11311MQ02, M5×0,8, M6×1,0, M8×1,25, M10×1,5",
  "12939MQ-A26": "Filieră KING TONY 12939MQ-A26, M4×0,75",
  "12939MQ-A29": "Filieră KING TONY 12939MQ-A29, M6×0,75",
  "12939MQ-A31": "Filieră KING TONY 12939MQ-A31, M7×0,75",
  "12939MQ-A34": "Filieră KING TONY 12939MQ-A34, M8×1,25",
  "12939MQ-A35": "Filieră KING TONY 12939MQ-A35, M10×1,25",
  "12939MQ-A38": "Filieră KING TONY 12939MQ-A38, M12×1,75",
  "8572-25": "Antrenor culisant KING TONY 8572-25, pătrat 1″",
  "8779-32F": "Clichet KING TONY 8779-32F, pătrat 1″, 32 dinți",
  "JTC-1044": "Set de inele de etanșare JTC-1044, 3–35 mm, 401 piese",
  "JTC-1117": "Set de capete pentru bușoane de golire a uleiului JTC-1117, 13 piese",
  "JTC-1144": "Kit de oprire pentru JTC-9035 JTC-1144",
  "JTC-1234": "Jojă pentru verificarea nivelului uleiului de motor JTC-1234, 930 mm, MB 112/113/646/647/648",
  "JTC-1253": "Set cârlige de eliberare a țevii, 2 buc. JTC-1253",
  "JTC-1260": "Set de dispozitive pentru deconectarea conductelor JTC-1260, 2 piese",
  "JTC-1321S": "Set de capete pentru extragerea șuruburilor și piulițelor deteriorate JTC-1321S, 15 piese",
  "JTC-1347": "Cleste pentru scoaterea furtunurilor si firelor, 2 bucati JTC-1347",
  "JTC-1358": "Kit deconectare conductă, 9 bucăți JTC-1358",
  "JTC-1361": "Kit pentru refacerea fileturilor fitingurilor sistemului de aer condiționat (9 articole) JTC-1361",
  "JTC-1409A": "Kit de spălare a sistemului de aer condiționat, 1 l JTC-1409A",
  "JTC-1411": "Con pentru montarea burdufului articulației planetare JTC-1411, pentru JTC-1410",
  "JTC-1413": "Dorn pentru montarea burdufului casetei de direcție JTC-1413, pentru JTC-1412",
  "JTC-1435A": "Set de dispozitive de blocare pentru arborele cu came BMW N62,N73 (OEM 119461,119462,119436,1191190) JTC-1435A",
  "JTC-1448": "Set extractoare pentru filtru de ulei, 9 buc JTC-1448",
  "JTC-1450": "Arc pentru montarea burdufului articulației planetare JTC-1450, pentru JTC-1410",
  "JTC-1452A": "Kit pentru instalarea pistoanelor etrierului de frână cu disc (21 unități) JTC-1452A",
  "JTC-1528": "Kit de diagnosticare a sistemului de racire, 19 bucati JTC-1528",
  "JTC-1560": "Cap tubular pentru butuc JTC-1560, pătrat 1″",
  "JTC-1561": "Cap tubular pentru butuc JTC-1561, pătrat 1″",
  "JTC-1611": "Set dornuri pentru montarea rulmenților și a etanșărilor de ulei (8 bucăți) JTC-1611",
  "JTC-1613A": "Kit pentru montarea pistoanelor etrierului de frână cu disc (15 unități) JTC-1613A",
  "JTC-1614": "Cap tubular pentru butuc spate JTC-1614, MB 107/114/115/116/126",
  "JTC-1712": "Extractor pentru știfturile de ghidare ale chiulasei JTC-1712, MB OM 601/602/603/615/616/617",
  "JTC-1804E": "Kit pentru înlocuirea silentblocurilor MB 210 JTC-1804E",
  "JTC-1827": "Kit deconectare conductă, 7 bucăți JTC-1827",
  "JTC-20191": "Set șaibe din cupru 500 buc JTC-20191",
  "JTC-2019B": "Set șaibe din aluminiu 500 buc JTC-2019B",
  "JTC-2021": "Set sigurante auto 267 buc JTC-2021",
  "JTC-2022": "Set știfturi 500 buc JTC-2022",
  "JTC-2042": "Set tuburi termocontractabile (5 dimensiuni) JTC-2042",
  "JTC-2120": "Kit de instrumente de aliniere a ambreiajului în cazul 10 articole JTC-2120",
  "JTC-2522": "Sârmă cu mânere pentru tăierea adezivului parbrizului JTC-2522, 1,8 m",
  "JTC-2522C": "Sârmă împletită pentru demontarea parbrizului JTC-2522C, 50 m",
  "JTC-2522D": "Sârmă pătrată pentru demontarea parbrizului JTC-2522D, 50 m",
  "JTC-2522E": "Sârmă triunghiulară pentru demontarea parbrizului JTC-2522E, 50 m",
  "JTC-2554": "Set tije de indreptat cauciucate, 7 bucati JTC-2554",
  "JTC-2570": "Set pene pentru îndreptare fără dezlipire vopsea, 12 bucăți JTC-2570",
  "JTC-2587": "Levier scurt pentru tinichigerie JTC-2587, cu două capete curbate",
  "JTC-3122": "Menghină de banc rotativă 5 JTC-3122",
  "JTC-3124": "Menghină de banc rotativă 8 JTC-3124",
  "JTC-3125": "Menghină de banc rotativă 10 JTC-3125",
  "JTC-3322": "Set extractoare pentru demontarea panourilor de placare 5 unități JTC-3322",
  "JTC-3340": "Ciocan pneumatic cu dalti, 15 bucati JTC-3340",
  "JTC-3408": "Ciocan de îndreptat cu percutor rotund 370mm, 710g JTC-3408",
  "JTC-3409": "Ciocan de îndreptat cu percutor rotund 370mm, 990g JTC-3409",
  "JTC-3461": "Set dălți, 5 bucăți JTC-3461",
  "JTC-3527": "Pânză de pilă pentru tinichigerie JTC-3527, granulație 12",
  "JTC-3528": "Pânză de pilă pentru tinichigerie JTC-3528, granulație 9, grosieră",
  "JTC-3533": "Clești pentru îndepărtarea clemelor (unghi 35 grade) JTC-3533",
  "JTC-3611": "Set de dornuri JTC-3611, 8 piese",
  "JTC-3701": "Șurubelniță cu un set de biți cu un adaptor înclinat la 90 de grade JTC-3701",
  "JTC-3917": "Set pentru refacerea filetului axei articulației homocinetice (8 unități) JTC-3917",
  "JTC-4034": "Set de dispozitive de blocare a distribuției JTC-4034, pentru VW/Audi 4,2",
  "JTC-4050": "Set pentru șlefuirea scaunelor injectorului motorului diesel (9 unități) JTC-4050",
  "JTC-4052": "Set extractoare pentru bujii incandescente (7 buc.) JTC-4052",
  "JTC-4053": "Kit de restaurare filet bujii incandescente (33 buc.) JTC-4053",
  "JTC-4061": "Set șaibe din cupru pentru injectoare 150 buc JTC-4061",
  "JTC-4063": "Protecție din plastic pentru compresorul de arcuri JTC-4063, pentru setul JTC-1941",
  "JTC-4195": "Kit deconectare țevi pentru AUDI A3, A4, A5, Q5,10 articole JTC-4195",
  "JTC-4226": "Extractor de injector pentru motoare pe benzină și diesel (utilizat cu JTC-2503) JTC-4226",
  "JTC-4263": "Kit de demontare a bujiilor incandescente MB OM611,612,613,628,646 (nu EVO),647,648 JTC-4263",
  "JTC-4275": "Set extractoare ștergătoare de parbriz, 5 buc JTC-4275",
  "JTC-4297": "Set de capete tubulare pentru scoaterea încuietorilor, 9 buc. JTC-4297",
  "JTC-4434": "Set de dispozitive de blocare pentru arborele cotit și arborele cu came, diesel VOLVO 1.6/ 1.9/ 2.0/ 2.4 JTC-4434",
  "JTC-4435": "Set de scule pentru demontarea pompei de injectie FORD Duratorg (2.0/ 2.2/ 2.4/ 3.2 litri diesel) JTC-4435",
  "JTC-4448": "Set cleme arbore cotit BMW N47 Diesel (118750, 1187) JTC-4448",
  "JTC-4518": "Cap tubular pentru etrier de frână JTC-4518, 9 mm, 10 caneluri, Porsche/VW Touareg/Audi Q7",
  "JTC-4529": "Set de adaptoare pentru scoaterea injectoarelor 10 unități JTC-4529",
  "JTC-4597": "Cap tubular pentru etrier de frână JTC-4597, 22 mm, 7 caneluri, Audi",
  "JTC-4598": "Cap tubular pentru etrier de frână JTC-4598, 20 mm, 10 caneluri, Porsche",
  "JTC-4599": "Cap tubular pentru etrier de frână JTC-4599, 15 mm, 10 caneluri, Porsche",
  "JTC-4619A": "Set de dispozitive de blocare pentru arborele cu came pentru reglarea temporizării BMW N51, N52 (OEM 114280,114290) JTC-4619A",
  "JTC-4639": "Extractoare de șuruburi deteriorate, 15 bucăți JTC-4639",
  "JTC-4657": "Kit deconectare conductă, 8 bucăți JTC-4657",
  "JTC-4660": "Cârlige pentru îndepărtarea bucșelor de cauciuc 2 buc JTC-4660",
  "JTC-4666": "Set extractoare pentru filtru de ulei, 16 buc JTC-4666",
  "JTC-4669": "Extractor pentru filtru de ulei JTC-4669, 76 mm, 12 muchii",
  "JTC-4670": "Extractor pentru filtru de ulei JTC-4670, 86 mm, 18 muchii",
  "JTC-4693": "Cheie pentru demontarea suportului motorului 16mm. (MB W220,210,203,219,221,211,204) JTC-4693",
  "JTC-4694": "Cheie pentru demontarea suportului motorului 17mm. (MB W140,202,126,124,201,170) JTC-4694",
  "JTC-4705": "Cadru pentru extractor hidraulic JTC-4704 JTC-4705",
  "JTC-4716": "Cap tubular pentru butuc JTC-4716, pătrat 1″",
  "JTC-4717": "Cap tubular pentru butuc JTC-4717, pătrat 1″",
  "JTC-4718A": "Extractor injector diesel COMMON RAIL (MB CDI 611, 612, 613) JTC-4718A",
  "JTC-4733": "Extractoare pentru elemente de fixare deteriorate, 25 articole JTC-4733",
  "JTC-4738": "Kit demontare scripete alternator, 14 buc JTC-4738",
  "JTC-4771": "Set pentru șlefuirea scaunelor injectorului motorului diesel (7 unități) JTC-4771",
  "JTC-4779A": "Set pentru restaurarea filetelor M6, M8, M10 JTC-4779A",
  "JTC-4808": "Set carlige pentru scoaterea simeringurilor, 3 bucati JTC-4808",
  "JTC-4878": "Extractor bujii incandescente 10mm, MB CDI 611, 612, 613 JTC-4878",
  "JTC-4886": "Set de bare de levier pentru anvelope si camion, 3 bucati JTC-4886",
  "JTC-4890": "Set dornuri pentru montarea rulmenților și etanșărilor de ulei (16 bucăți) JTC-4890",
  "JTC-4896": "Instrument pentru montarea ambreiajului auto-reglabil SAC (BMW E34,36,38,39,46,52,53,85) JTC-4896",
  "JTC-4898": "Tarod lung pentru repararea filetului bujiei JTC-4898, M12×1,25",
  "JTC-4899": "Tarod lung pentru repararea filetului bujiei JTC-4899, M14×1,25",
  "JTC-4914": "Extractor pentru articulații sferice JTC-4914, MB W163/W164/W251",
  "JTC-4923S": "Set de dispozitive de blocare pentru arborele cu came și arborele cotit (BMW (N47, N47S, N57), diesel (115320, 1164)) JTC-4923S",
  "JTC-5002": "Set pentru demontarea lentilelor farurilor JTC-5002, 2 piese",
  "JTC-5113": "Set de perii (17 buc.) JTC-5113",
  "JTC-5129": "Set de dălți, punctator și dornuri JTC-5129, 5 piese",
  "JTC-5151": "Cap tubular pentru butuc JTC-5151, pătrat 1″",
  "JTC-5152": "Cap tubular pentru butuc JTC-5152, pătrat 1″",
  "JTC-5233": "Ciocan de banc 500g JTC-5233",
  "JTC-5326": "Set extractoare pentru demontarea panourilor de placare 8 unități JTC-5326",
  "JTC-5502": "Extractoare și burghie pentru șuruburi deteriorate, 10 articole JTC-5502",
  "JTC-5533": "Seringă cu piston pentru lichid de frână, antigel 200 ml. (polipropilena) JTC-5533",
  "JTC-5534": "Seringă cu piston cu gradație de 1500 ml. (polipropilena) JTC-5534",
  "JTC-5547": "Furtun din poliuretan, spirala 8/12mm, lungime 10m JTC-5547",
  "JTC-5548": "Furtun din poliuretan, spirala 8/12mm, lungime 15m JTC-5548",
  "JTC-5595": "Set chei de service pentru etriere de frana (camioane) 5 unitati JTC-5595",
  "JTC-5601": "Extractoare de crampoane deteriorate, 5 bucăți JTC-5601",
  "JTC-5630": "Căptușeli de protecție pentru jante (2 bucăți) JTC-5630",
  "JTC-5632": "Kit de tăiere și evazare a tuburilor (9 articole) JTC-5632",
  "JTC-5632M": "Kit de tăiere și evazare a tuburilor, dimensiuni metrice (9 bucăți) JTC-5632M",
  "JTC-5636": "Set pile cu ace de diferite profile 215 mm, 5 articole JTC-5636",
  "JTC-5806": "Disc de curățare cu tijă de antrenare JTC-5806, 2″",
  "JTC-6631": "Extractor pentru articulații sferice JTC-6631, Land Rover V8 4,0 l",
  "JTC-6632": "Extractor injector diesel, hidraulic 17 t JTC-6632",
  "JTC-6747": "Set de capete pentru șuruburi și piulițe antifurt Mercedes JTC-6747, 35 piese",
  "JTC-6790": "Extractor pneumohidraulic universal pentru butuc, forta 10 t JTC-6790",
  "JTC-6820": "Kit pentru instalarea pistoanelor etrierului de frână cu disc (24 unități) JTC-6820",
  "JTC-6828": "Cap tubular pentru etrier de frână JTC-6828, 14 mm, 7 caneluri, Audi A5/A6L/Q5/Q7",
  "JTC-6964": "Kit deconectare conductă de combustibil (3 bucăți) JTC-6964",
  "JTC-7643": "Set de șurubelnițe, 7 buc JTC-7643",
  "JTC-7677A": "Pâlnie din plastic pentru lichide tehnice, cu 2 vârfuri JTC-7677A",
  "JTC-7786": "Instrument pentru înlocuirea cupei injectorului SCANIA (113,114) JTC-7786",
  "JTC-7823": "Set pensete din otel inoxidabil, 5 bucati JTC-7823",
  "JTC-7941": "Clește combinat pentru muchii, curbat (90 de grade) JTC-7941",
  "JTC-8468085": "Cap tubular pentru butuc JTC-8468085, pătrat 1″",
  "JTC-8468090": "Cap tubular pentru butuc JTC-8468090, pătrat 1″",
  "JTC-8468100": "Cap tubular pentru butuc JTC-8468100, pătrat 1″",
  "JTC-8468110": "Cap tubular pentru butuc JTC-8468110, pătrat 1″",
  "JTC-8468115": "Cap tubular pentru butuc JTC-8468115, pătrat 1″",
  "JTC-849019": "Cap tubular adânc de impact JTC-849019, pătrat 1″",
  "JTC-849021": "Cap tubular adânc de impact JTC-849021, pătrat 1″",
  "JTC-849022": "Cap tubular adânc de impact JTC-849022, pătrat 1″",
  "JTC-849023": "Cap tubular adânc de impact JTC-849023, pătrat 1″",
  "JTC-849027": "Cap tubular adânc de impact JTC-849027, pătrat 1″",
  "JTC-849036": "Cap tubular adânc de impact JTC-849036, pătrat 1″",
  "JTC-849052": "Cap tubular adânc de impact JTC-849052, pătrat 1″",
  "JTC-849060": "Cap tubular adânc de impact JTC-849060, pătrat 1″",
  "JTC-849063": "Cap tubular adânc de impact JTC-849063, pătrat 1″",
  "JTC-849065": "Cap tubular adânc de impact JTC-849065, pătrat 1″",
  "JTC-849070": "Cap tubular adânc de impact JTC-849070, pătrat 1″",
  "JTC-849075": "Cap tubular adânc de impact JTC-849075, pătrat 1″",
  "JTC-849080": "Cap tubular adânc de impact JTC-849080, pătrat 1″",
  "JTC-8P110": "Scripete pentru lanțuri de 5/16”, 3/8”, 2 știfturi de blocare JTC-8P110",
  "JTC-AM45": "Tava de scurgere a uleiului, medie 16 l JTC-AM45",
  "JTC-AM46": "Tava de scurgere a uleiului, mare 24 l JTC-AM46",
  "JTC-AM48": "Tava metalica de scurgere a uleiului (adanca) 22 l JTC-AM48",
  "JTC-C101": "Clemă pentru lucrări de caroserie JTC-C101, 5 t",
  "JTC-C203": "Clemă ranforsată pentru lucrări de caroserie JTC-C203, 5 t",
  "JTC-C303": "Clemă pentru lucrări de caroserie JTC-C303, 90°, 3 t",
  "JTC-C601": "Clemă dublă pentru lucrări de caroserie JTC-C601, 5 t",
  "JTC-C603": "Clemă dublă pentru lucrări de caroserie JTC-C603, 5 t",
  "JTC-H816M": "Set de capete tubulare JTC-H816M, pătrat 1″",
  "JTC-K7085": "Set de șurubelnițe electrice (cu fante și Phillips), 8 bucăți JTC-K7085",
  "JTC-K7102": "Set de șurubelnițe într-un suport (10 unități) JTC-K7102",
  "JTC-K8261": "Kit pentru demontarea elementelor cu filete deteriorate în suport (26 buc) JTC-K8261",
  "JTC-LS10S": "Set chei combinate lungi 10-19 mm, 10 buc JTC-LS10S",
  "JTC-RDY": "Set cleme din plastic auto 600 buc JTC-RDY",
  "JTC-SJ3063": "Cric cărucior cu pedală, hidraulic, 3 t JTC-SJ3063",
  "JW0095": "Pompă hidraulică cu picior, 10 t JTC JW0095",
  "JW0703A": "Extractor injector diesel MB CDI OM 611, 612, 613 JTC JW0703A",
  "NE-0413": "Set cu clichet pneumatic M7 NE-0413, pătrat 1/2″, 135 Nm, capete 8–19 mm, 13 piese",
  "SD-22107": "Furtun pneumatic antistatic M7 SD-22107, 12×8 mm, 7,5 m",
  "SG-500": "Pistol pneumatic pentru unsoare M7 SG-500, alimentare în impulsuri, 500 ml",
  "SG-501": "Pistol pneumatic pentru unsoare M7 SG-501, alimentare continuă, 500 ml",
  "SM-0903": "Extractor manual pentru lichide tehnice M7 SM-0903, 9 l"
}
JSON, true, flags: JSON_THROW_ON_ERROR);

        $ruTitles = json_decode(<<<'JSON'
{
  "8572-25": "Вороток скользящий KING TONY 8572-25, квадрат 1″",
  "8779-32F": "Трещотка KING TONY 8779-32F, квадрат 1″, 32 зубца",
  "JTC-H816M": "Набор торцевых головок JTC-H816M, квадрат 1″",
  "JTC-1560": "Головка ступичная JTC-1560, квадрат 1″",
  "JTC-1561": "Головка ступичная JTC-1561, квадрат 1″",
  "JTC-4716": "Головка ступичная JTC-4716, квадрат 1″",
  "JTC-4717": "Головка ступичная JTC-4717, квадрат 1″",
  "JTC-5151": "Головка ступичная JTC-5151, квадрат 1″",
  "JTC-5152": "Головка ступичная JTC-5152, квадрат 1″",
  "JTC-8468085": "Головка ступичная JTC-8468085, квадрат 1″",
  "JTC-8468090": "Головка ступичная JTC-8468090, квадрат 1″",
  "JTC-8468100": "Головка ступичная JTC-8468100, квадрат 1″",
  "JTC-8468110": "Головка ступичная JTC-8468110, квадрат 1″",
  "JTC-8468115": "Головка ступичная JTC-8468115, квадрат 1″",
  "JTC-849019": "Головка торцевая ударная глубокая JTC-849019, квадрат 1″",
  "JTC-849021": "Головка торцевая ударная глубокая JTC-849021, квадрат 1″",
  "JTC-849022": "Головка торцевая ударная глубокая JTC-849022, квадрат 1″",
  "JTC-849023": "Головка торцевая ударная глубокая JTC-849023, квадрат 1″",
  "JTC-849027": "Головка торцевая ударная глубокая JTC-849027, квадрат 1″",
  "JTC-849036": "Головка торцевая ударная глубокая JTC-849036, квадрат 1″",
  "JTC-849052": "Головка торцевая ударная глубокая JTC-849052, квадрат 1″",
  "JTC-849060": "Головка торцевая ударная глубокая JTC-849060, квадрат 1″",
  "JTC-849063": "Головка торцевая ударная глубокая JTC-849063, квадрат 1″",
  "JTC-849065": "Головка торцевая ударная глубокая JTC-849065, квадрат 1″",
  "JTC-849070": "Головка торцевая ударная глубокая JTC-849070, квадрат 1″",
  "JTC-849075": "Головка торцевая ударная глубокая JTC-849075, квадрат 1″",
  "JTC-849080": "Головка торцевая ударная глубокая JTC-849080, квадрат 1″"
}
JSON, true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($roTitles, $ruTitles): void {
            $now = now();

            foreach ($roTitles as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first([
                    'id', 'name_ro', 'short_description_ro', 'description_ro',
                ]);
                if (! $product || trim((string) $product->name_ro) === trim($title)) {
                    continue;
                }

                $updates = ['name_ro' => $title, 'updated_at' => $now];
                foreach (['short_description_ro', 'description_ro'] as $column) {
                    $value = (string) $product->{$column};
                    if ($value !== '' && (string) $product->name_ro !== '') {
                        $updates[$column] = str_replace((string) $product->name_ro, $title, $value);
                    }
                }

                DB::table('products')->where('id', $product->id)->update($updates);
            }

            foreach ($ruTitles as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first([
                    'id', 'name', 'name_ru', 'short_description', 'short_description_ru',
                    'description', 'description_ru',
                ]);
                if (! $product || trim((string) $product->name_ru) === trim($title)) {
                    continue;
                }

                $updates = [
                    'name' => $title,
                    'name_ru' => $title,
                    'updated_at' => $now,
                ];
                foreach (['short_description', 'short_description_ru', 'description', 'description_ru'] as $column) {
                    $value = (string) $product->{$column};
                    if ($value !== '' && (string) $product->name_ru !== '') {
                        $updates[$column] = str_replace((string) $product->name_ru, $title, $value);
                    }
                }

                DB::table('products')->where('id', $product->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally retained.
    }
};
