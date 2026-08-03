<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $kingTonyPages = [
            28 => '39124016M',
            104 => '4572-10',
            106 => '7702-25',
            107 => '7702-50',
            108 => '4260-05P',
            115 => '213312DF',
            117 => '68SS-10',
            121 => '2752-06G',
            134 => '42114GP',
            142 => '6721-10',
            144 => '4768-10F',
            150 => '3769-08F,4759-10G',
            151 => '2769-55F',
            161 => '4452-18FR',
            167 => '4762-10F',
            172 => '433519MR',
            173 => '233010M,233510M,233011M,233511M,233012M,233512M,233013M,233513M,233014M,233514M,233515M,233045M,233545M,233004M,233504M,233055M,233555M,233005M,233505M,233006M,233506M,233007M,233507M,233008M,233508M,233009M,233509M,223510M,223511M,223512M,223513M,223514M,223545M,223504M,223555M,223505M,223506M,223507M,223508M,223509M',
            174 => '237510M,237504M,237505M,237506M,237507M,237508M,223010M,223011M,223012M,223013M,223014M,223045M,223004M,223055M,223005M,223006M,223007M,223008M,223009M,227504M,227505M,227506M,227507M,227508M',
            175 => '203506,203103,203104',
            179 => '2771-55F',
            180 => '2781-06P',
            185 => '8720,870208,870422',
            188 => '2755-55G',
            194 => '333010M,333510M,333011M,333511M,333012M,333512M,333013M,333513M,333014M,333514M,333015M,333515M,333016M,333516M,333017M,333517M,333018M,333518M,333019M,333519M,333521M,333007M,333507M,333008M,333508M,333009M,333509M',
            195 => '323010M,323510M,323011M,323511M,323012M,323512M,323013M,323513M,323014M,323514M,323015M,323515M,323016M,323516M,323017M,323517M,323018M,323518M,323019M,323519M,323021M,323521M,323022M,323522M,323024M,323524M,323007M,323507M,323008M,323508M,323009M,323509M',
            196 => '337510M,337511M,337512M,337514M,337516M,337518M,337504M,337505M,337506M,337507M,337508M,327510M,327511M,327512M,327514M,327516M,327518M,327504M,327505M,327506M,327507M,327508M',
            198 => '365016B',
            200 => '375011M,375010M,375012M,375013M,375014M,375015M,375016M,375017M,375018M,375019M',
            201 => '302D15,302D27',
            202 => '3761-08P',
            207 => '870311',
            213 => '433010MR,433510MR,433011MR,433511MR,433012MR,433512MR,433013MR,433513MR,433014MR,433514MR,433015MR,433515MR,433016MR,433516MR,433017MR,433517MR,433018MR,433518MR,433019MR,433020MR,433520MR,433021MR,433521MR,433022MR,433522MR,433023MR,433523MR,433024MR,433524MR,433025MR,433525MR,433026MR,433526MR,433027MR,433527MR,433028MR,433029MR,433529MR,433030MR,433530MR,433032MR,433532MR,433033MR,433533MR,433034MR,433534MR,433036MR,433536MR,433538MR,433539MR,433541MR,433008MR,433508MR,433009MR,433509MR',
            214 => '423010M,423011M,423012M,423013M,423014M,423015M,423016M,423017M,423018M,423019M,423020M,423021M,423022M,423023M,423024M,423025M,423026M,423027M,423028M,423029M,423030M,423032M,423033M,423036M,423008M,423009M',
            215 => '423510M,423511M,423512M,423513M,423514M,423515M,423516M,423517M,423518M,423519M,423520M,423521M,423522M,423523M,423524M,423526M,423527M,423528M,423529M,423530M,423532M,423533M,423536M,423538M,423541M,423508M,423509M,425510M,425512M,425513M,425514M,425508M,425509M',
            216 => '437510M,437511M,437512M,437514M,437516M,437518M,437520M,437522M,437524M,437508M,427510M,427512M,427514M,427516M,427518M,427520M,427522M,427524M,427508M',
            218 => '402504,402505,403507,402508,402509',
            219 => '404913,404914,404916,404904,404906,404907',
            220 => '4725-12BR',
            221 => '4771-10BR,4768-10G,4762-15G',
            224 => '4251-02R',
            239 => '633017M,633019M,633021M,633022M,633024M,633026M,633027M,633030M,633032M,633033M,633034M,633035M,633036M,633038M,633041M,633042M,633046M,633048M,633050M,633054M,633055M,633058M,633060M,633063M,633065M,633070M',
            240 => '623517M,623518M,623519M,623520M,623521M,623522M,623523M,623524M,623525M,623526M,623527M,623528M,623529M,623530M,623531M,623032M,623532M,623033M,623533M,623534M,623535M,623036M,623536M,623537M,623538M,623039M,623539M,623540M,623041M,623541M,623542M,623543M,623544M,623545M,623046M,623546M,623547M,623548M,623549M,623050M,623550M,623551M,623552M,623553M,623554M,623555M',
            241 => '602522,6724-20F',
            245 => '8779-32F,833036M,833038M,833041M,833046M,833050M,833054M,833055M,833058M,833060M,833063M,833065M,833067M,833070M,833071M,833075M,833077M,833080M',
            274 => '493024M,493030M,493027M',
            275 => '49152122M',
            277 => '4260-04P,4260-06P,4260-08P,4260-10P',
            284 => '651517M,651518M,651519M,651521M,651522M,651523M',
            285 => '643565M',
            317 => '9BM2-02,9BM2210A',
            367 => '373108M,373109M,373110M,373111M,373112M,373113M,373114M,373115M,373116M,373117M,373118M,373119M,373121M,373122M,373124M,373127M,373130M,373132M,373008M,373009M,373010M,373011M,373012M,373013M,373014M,373015M,373016M,373017M,373018M,373019M,373021M,373022M,373024M',
            376 => '19992427,19992732,19993032,19993233,199920R',
            377 => '118508M,118510M,118512M,118513M,118514M,118517M,118519M',
            378 => '4795-18',
            388 => '68HB-10,42116GP',
            419 => '183612H',
            429 => '711110H,711102H,711125H,711103H,711104H,711105H,711505H,711106H,711107H,711108H,711002P,711110T,711510T,711115T,711515T,711120T,711520T,711525T,711125T,711127T,711527T,711130T,711530T,711140T,711540T,711506T,717006T,711507T,717007T,711508T,717008T,711509T,717009T,715005S1,715055S1,715004S1',
            434 => '752-60,752-75',
            437 => '7704-50,7702-75',
            490 => '6141-08C',
            529 => '74512,74514',
            533 => '12939MQ-A05',
            544 => '9TA54',
            545 => '9TA52A',
            605 => '9BC5110M,9BC5108M',
            622 => '9CJ61-40,9CJ7432',
        ];

        $mightySevenPages = [
            10 => 'DS-203A',
            18 => 'DW-401A',
            19 => 'DW-404A,DW-406A,DW-601A',
            22 => 'DRS-102A',
            23 => 'DG-501A',
            145 => 'SC-2A,SC-2B,SC-425',
        ];

        DB::transaction(function () use ($kingTonyPages, $mightySevenPages): void {
            $kingTonyBrandId = DB::table('brands')->where('name', 'King Tony')->value('id');
            if ($kingTonyBrandId) {
                foreach ($kingTonyPages as $page => $skuList) {
                    $this->verifyProducts(
                        (int) $kingTonyBrandId,
                        explode(',', $skuList),
                        'https://www.kingtony.com/e_catalog/files/basic-html/page'.$page.'.html',
                        'www.kingtony.com',
                        'official_manufacturer_catalog',
                        100,
                        'King Tony official catalog page '.$page
                    );
                }
            }

            $mightySevenBrandId = DB::table('brands')->where('name', 'like', 'M7%')->value('id');
            if ($mightySevenBrandId) {
                foreach ($mightySevenPages as $page => $skuList) {
                    $this->verifyProducts(
                        (int) $mightySevenBrandId,
                        explode(',', $skuList),
                        'https://sklep.anb.com.pl/data/include/cms/M7/2025/Katalogi/2025_2026_Katalog_M7_int2.pdf#page='.$page,
                        'sklep.anb.com.pl',
                        'official_manufacturer_catalog',
                        95,
                        'M7 2025/2026 official catalog page '.$page
                    );
                }
            }

            $this->copyExactParserSourcesToProducts();
        });
    }

    private function verifyProducts(
        int $brandId,
        array $skus,
        string $url,
        string $domain,
        string $sourceType,
        int $confidence,
        string $sourceTitle
    ): void {
        $now = now();

        DB::table('products')
            ->where('brand_id', $brandId)
            ->whereIn('sku', $skus)
            ->select('id', 'sku', 'name_ro', 'source_parser_item_id', 'parser_source_urls')
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($url, $domain, $sourceType, $confidence, $sourceTitle, $now): void {
                $sourceUrls = json_decode((string) $product->parser_source_urls, true);
                $sourceUrls = is_array($sourceUrls) ? $sourceUrls : [];
                $sourceUrls[] = $url;
                $sourceUrls = array_values(array_unique(array_filter($sourceUrls)));

                DB::table('products')->where('id', $product->id)->update([
                    'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
                    'source_url' => $url,
                    'source_domain' => $domain,
                    'source_type' => $sourceType,
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => max($confidence, 90),
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    return;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'official_source_url' => $url,
                    'official_source_domain' => $domain,
                    'official_source_confidence' => $confidence,
                    'fallback_source_url' => null,
                    'fallback_source_domain' => null,
                    'fallback_source_used' => false,
                    'source_match_confidence' => $confidence,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('product_parser_sources')->updateOrInsert(
                    ['parser_item_id' => $product->source_parser_item_id, 'url' => $url],
                    [
                        'domain' => $domain,
                        'title' => $sourceTitle,
                        'snippet' => 'Exact SKU verified in a reviewed manufacturer catalog.',
                        'source_type' => $sourceType,
                        'confidence_score' => $confidence,
                        'raw_data_json' => json_encode([
                            'sku' => $product->sku,
                            'verification' => 'exact_catalog_sku',
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    private function copyExactParserSourcesToProducts(): void
    {
        $now = now();

        DB::table('products as p')
            ->join('product_parser_items as i', 'i.id', '=', 'p.source_parser_item_id')
            ->where('p.needs_source_review', true)
            ->whereIn('i.official_source_domain', ['www.mighty-seven.com', 'mighty-seven.com'])
            ->whereNotNull('i.official_source_url')
            ->where('i.source_match_confidence', '>=', 90)
            ->select('p.id', 'i.official_source_url', 'i.official_source_domain', 'i.source_match_confidence')
            ->orderBy('p.id')
            ->get()
            ->each(function (object $row) use ($now): void {
                DB::table('products')->where('id', $row->id)->update([
                    'source_url' => $row->official_source_url,
                    'source_domain' => $row->official_source_domain,
                    'source_type' => 'official_manufacturer',
                    'fallback_source_used' => false,
                    'needs_source_review' => false,
                    'source_reviewed_at' => $now,
                    'parser_confidence' => max(90, (int) $row->source_match_confidence),
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        // Curated catalog verification is intentionally irreversible.
    }
};
