<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $families = [
            '745' => ['745.png', '74512,74514'],
            '752' => ['752.png', '752-60,752-75'],
            '770' => ['770.png', '7704-50,7702-25,7702-50,7702-75'],
            '870' => ['870.png', '870311,870422'],
            '872' => ['872.png', '8720,870208'],
            '1999' => ['1999.png', '19992427,19992732,19993032,19993233'],
            '2031' => ['2031.png', '203101,203102,203103,203104'],
            '2035' => ['2035.png', '203503,203504,203505,203506,203507,203508'],
            '4025' => ['4025.png', '402503,402504,402505,402506,402507,402508'],
            '4035' => ['4035.png', '403504,403505,403506,403507,403508,403509'],
            '4049' => ['4049.png', '404910,404925,404912,404913,404914,404916,404904,404905,404906,404907'],
            '4452' => ['4452.png', '4452-18FR'],
            '4572' => ['4572.png', '4572-10'],
            '6025' => ['6025.png', '602522'],
            '1010CMR' => ['1010CMR.png', '1010CMR'],
            '1185M' => ['1185M.png', '118508M,118510M,118512M,118513M,118514M,118517M,118519M'],
            '1800H' => ['1800H.png', '183612H'],
            '1999R' => ['1999R.png', '199920R'],
            '2133DF' => ['2133DF.png', '213312DF'],
            '2230M' => ['2230M.png', '223010M,223011M,223012M,223013M,223014M,223045M,223004M,223055M,223005M,223006M,223007M,223008M,223009M'],
            '2235M' => ['2235M.png', '223510M,223511M,223512M,223513M,223514M,223545M,223504M,223555M,223505M,223506M,223507M,223508M,223509M'],
            '2275M' => ['2275M.png', '227504M,227505M,227506M,227507M,227508M'],
            '2330M' => ['2330M.png', '233010M,233011M,233012M,233013M,233014M,233045M,233004M,233055M,233005M,233006M,233007M,233008M,233009M'],
            '2335M' => ['2335M.png', '233510M,233511M,233512M,233513M,233514M,233515M,233545M,233504M,233555M,233505M,233506M,233507M,233508M,233509M'],
            '2375M' => ['2375M.png', '237510M,237504M,237505M,237506M,237507M,237508M'],
            '2769F' => ['2769F.png', '2769-55F'],
            '2771F' => ['2771F.png', '2771-55F'],
            '2781P' => ['2781P.png', '2781-06P'],
            '3230M' => ['3230M.png', '323010M,323011M,323012M,323013M,323014M,323015M,323016M,323017M,323018M,323019M,323021M,323022M,323024M,323007M,323008M,323009M'],
            '3235M' => ['3235M.png', '323510M,323511M,323512M,323513M,323514M,323515M,323516M,323517M,323518M,323519M,323521M,323522M,323524M,323507M,323508M,323509M'],
            '3275M' => ['3275M.png', '327510M,327511M,327512M,327514M,327516M,327518M,327504M,327505M,327506M,327507M,327508M'],
            '3330M' => ['3330M.png', '333010M,333011M,333012M,333013M,333014M,333015M,333016M,333017M,333018M,333019M,333007M,333008M,333009M'],
            '3335M' => ['3335M.png', '333510M,333511M,333512M,333513M,333514M,333515M,333516M,333517M,333518M,333519M,333521M,333507M,333508M,333509M'],
            '3375M' => ['3375M.png', '337510M,337511M,337512M,337514M,337516M,337518M,337504M,337505M,337506M,337507M,337508M'],
            '34467-AG' => ['34467-AG.png', '34467-1AG-1,34367-2AG-1'],
            '3730M' => ['3730M.png', '373008M,373009M,373010M,373011M,373012M,373013M,373014M,373015M,373016M,373017M,373018M,373019M,373021M,373022M,373024M'],
            '3731M' => ['3731M.png', '373108M,373109M,373110M,373111M,373112M,373113M,373114M,373115M,373116M,373117M,373118M,373119M,373121M,373122M,373124M,373127M,373130M,373132M'],
            '3750M' => ['3750M.png', '375011M,375010M,375012M,375013M,375014M,375015M,375016M,375017M,375018M,375019M'],
            '3761P' => ['3761P.png', '3761-08P'],
            '3769F' => ['3769F.png', '3769-08F'],
            '3912M' => ['3912M.png', '39124016M'],
            '40D0' => ['40D0.png', '40D006,40D008'],
            '4116PR' => ['4116PR.png', '4116PR'],
            '4230M' => ['4230M.png', '423010M,423011M,423012M,423013M,423014M,423015M,423016M,423017M,423018M,423019M,423020M,423021M,423022M,423023M,423024M,423025M,423026M,423027M,423028M,423029M,423030M,423032M,423033M,423036M,423008M,423009M'],
            '4235M' => ['4235M.png', '423510M,423511M,423512M,423513M,423514M,423515M,423516M,423517M,423518M,423519M,423520M,423521M,423522M,423523M,423524M,423526M,423527M,423528M,423529M,423530M,423532M,423533M,423536M,423538M,423541M,423508M,423509M'],
            '4251R' => ['4251R.png', '4251-02R'],
            '4255M' => ['4255M.png', '425510M,425512M,425513M,425514M,425508M,425509M'],
            '4260P' => ['4260P.png', '4260-04P,4260-05P,4260-06P,4260-08P,4260-10P'],
            '4275M' => ['4275M.png', '427510M,427512M,427514M,427516M,427518M,427520M,427522M,427524M,427508M'],
            '4330MR' => ['4330MR.png', '433010MR,433011MR,433012MR,433013MR,433014MR,433015MR,433016MR,433017MR,433018MR,433019MR,433020MR,433021MR,433022MR,433023MR,433024MR,433025MR,433026MR,433027MR,433028MR,433030MR,433032MR,433033MR,433034MR,433036MR'],
            '4335MR' => ['4335MR.png', '433510MR,433511MR,433512MR,433513MR,433514MR,433515MR,433516MR,433517MR,433518MR,433519MR,433520MR,433521MR,433522MR,433523MR,433524MR,433525MR,433526MR,433527MR,433529MR,433530MR,433532MR,433533MR,433534MR,433536MR,433538MR,433539MR,433541MR,433508MR,433509MR'],
            '4375M' => ['4375M.png', '437510M,437511M,437512M,437514M,437516M,437518M,437520M,437522M,437524M,437508M'],
            '4759G' => ['4759G.png', '4759-10G'],
            '4762F' => ['4762F.png', '4762-10F'],
            '4762G' => ['4762G.png', '4762-15G'],
            '4768F' => ['4768F.png', '4768-10F'],
            '4771BR' => ['4771BR.png', '4771-10BR'],
            '4915M' => ['4915M.png', '49152122M'],
            '4930M' => ['4930M.png', '493024M,493030M,493027M'],
            '6141C' => ['6141C.png', '6141-08C'],
            '6230M' => ['6230M.png', '623032M,623033M,623036M,623039M,623041M,623046M,623050M'],
            '6235M' => ['6235M.png', '623517M,623518M,623519M,623520M,623521M,623522M,623523M,623524M,623525M,623526M,623527M,623528M,623529M,623530M,623531M,623532M,623533M,623534M,623535M,623536M,623537M,623538M,623539M,623540M,623541M,623542M,623543M,623544M,623545M,623546M,623547M,623548M,623549M,623550M,623551M,623552M,623553M,623554M,623555M'],
            '6330M' => ['6330M.png', '633017M,633019M,633021M,633022M,633024M,633026M,633027M,633030M,633032M,633033M,633034M,633035M,633036M,633038M,633041M,633042M,633046M,633048M,633050M,633054M,633055M,633058M,633060M,633063M,633065M,633070M'],
            '6411MP' => ['6411MP.png', '6411MP'],
            '6435M' => ['6435M.png', '643565M'],
            '6515M' => ['6515M.png', '651517M,651518M,651519M,651521M,651522M,651523M'],
            '6724F' => ['6724F.png', '6724-20F'],
            '7100H' => ['7100H.png', '711110H,711102H,711125H,711103H,711104H,711105H,711505H,711106H,711108H'],
            '7100P' => ['7100P.png', '711002P'],
            '7100T' => ['7100T.png', '711110T,711510T,711115T,711515T,711120T,711520T,711525T,711125T,711127T,711527T,711130T,711530T,711140T,711540T,711506T,717006T,711507T,717007T,711508T,717008T,711509T,717009T'],
            '7150S' => ['7150S.png', '715005S1,715055S1,715004S1'],
            '8330M' => ['8330M.png', '833036M,833038M,833041M,833046M,833050M,833054M,833055M,833058M,833060M,833063M,833065M,833067M,833070M,833071M,833075M,833077M,833080M'],
            '8779F' => ['8779F.png', '8779-32F'],
            '9BA11' => ['9BA11.png', '9BA11'],
            '9BM' => ['9BM.png', '9BM2-02,9BM2210A'],
            '9CF230' => ['9CF230.png', '9CF230'],
        ];

        DB::transaction(function () use ($families): void {
            $brandId = DB::table('brands')->where('name', 'King Tony')->value('id');
            if (! $brandId) {
                return;
            }

            foreach ($families as $family => [$sourceFile, $skuList]) {
                $slug = Str::slug($family);
                $directory = '/images/catalog-reviewed/king-tony-families/'.$slug;
                $main = $directory.'/'.$slug.'-main.webp';
                $preview = $directory.'/'.$slug.'-preview.webp';
                $thumb = $directory.'/'.$slug.'-thumb.webp';
                $absoluteMain = public_path(ltrim($main, '/'));
                if (! is_file($absoluteMain)
                    || ! is_file(public_path(ltrim($preview, '/')))
                    || ! is_file(public_path(ltrim($thumb, '/')))) {
                    continue;
                }

                $sourceUrl = 'https://www.kingtony.com/upload/products/'.rawurlencode($sourceFile);
                $skus = explode(',', $skuList);

                DB::table('products')
                    ->where('brand_id', $brandId)
                    ->whereIn('sku', $skus)
                    ->select('id', 'sku', 'name_ru', 'source_url', 'source_parser_item_id')
                    ->orderBy('id')
                    ->get()
                    ->each(function (object $product) use ($main, $preview, $thumb, $sourceUrl, $absoluteMain): void {
                        $now = now();

                        DB::table('products')->where('id', $product->id)->update([
                            'main_image' => $main,
                            'gallery' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                            'needs_image_review' => false,
                            'updated_at' => $now,
                        ]);

                        if ($product->source_parser_item_id) {
                            DB::table('product_parser_image_assets')
                                ->where('parser_item_id', $product->source_parser_item_id)
                                ->update(['is_selected' => false, 'is_main' => false, 'updated_at' => $now]);

                            DB::table('product_parser_image_assets')->updateOrInsert(
                                ['parser_item_id' => $product->source_parser_item_id, 'source_url' => $sourceUrl],
                                [
                                    'source_domain' => 'www.kingtony.com',
                                    'original_path' => null,
                                    'processed_path' => $main,
                                    'preview_path' => $preview,
                                    'thumb_path' => $thumb,
                                    'width' => 1200,
                                    'height' => 1200,
                                    'mime_type' => 'image/webp',
                                    'status' => 'processed',
                                    'is_selected' => true,
                                    'is_main' => true,
                                    'has_watermark' => true,
                                    'background_removed' => false,
                                    'background_removal_failed' => false,
                                    'needs_review' => false,
                                    'error_message' => null,
                                    'updated_at' => $now,
                                    'created_at' => $now,
                                ]
                            );

                            DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                                'selected_images_json' => json_encode([$sourceUrl], JSON_UNESCAPED_SLASHES),
                                'processed_images_json' => json_encode([$main], JSON_UNESCAPED_SLASHES),
                                'image_source_type' => 'official_manufacturer_family',
                                'needs_image_review' => false,
                                'image_reviewed_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }

                        DB::table('product_images')->where('product_id', $product->id)->delete();
                        DB::table('product_images')->insert([
                            'product_id' => $product->id,
                            'path' => $main,
                            'alt' => $product->name_ru,
                            'sort_order' => 1,
                            'source_url' => $sourceUrl,
                            'source_page_url' => $product->source_url,
                            'source_domain' => 'www.kingtony.com',
                            'is_official' => true,
                            'mime_type' => 'image/webp',
                            'width' => 1200,
                            'height' => 1200,
                            'file_size' => filesize($absoluteMain) ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
            }
        });
    }

    public function down(): void
    {
        // Reviewed official family media is intentionally irreversible.
    }
};
