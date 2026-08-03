<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tapHolders = [
            '12939MQ-A02' => ['T-образный', 'tip T', 'M3–M12', 'M3–M12', null],
            '12939MQ-A04' => ['цанговый', 'tip mandrină', '3–6 мм', '3–6 mm', null],
            '39110312M' => ['рычажный', 'cu brațe', 'M3–M12', 'M3–M12', null],
            '39123010M' => ['', '', 'M2–M6', 'M2–M6', '3/8″'],
            '39123012M' => ['', '', 'M4–M9', 'M4–M9', '3/8″'],
            '39124012M' => ['', '', 'M6–M12', 'M6–M12', '1/2″'],
        ];
        $bitHolders = [
            '314708S' => [true, '3/8″', '1/4″', '40 мм'],
            '314710M' => [true, '3/8″', '10 мм', '40 мм'],
            '314710S' => [true, '3/8″', '5/16″', '40 мм'],
            '314808S' => [false, '3/8″', '1/4″', '30 мм'],
            '314810M' => [false, '3/8″', '10 мм', '30 мм'],
            '314810S' => [false, '3/8″', '5/16″', '30 мм'],
            '414708S' => [true, '1/2″', '1/4″', '48 мм'],
            '414710M' => [true, '1/2″', '10 мм', '48 мм'],
            '414710S' => [true, '1/2″', '5/16″', '48 мм'],
            '414808S' => [false, '1/2″', '1/4″', '38 мм'],
            '414810M' => [false, '1/2″', '10 мм', '38 мм'],
        ];

        DB::transaction(function () use ($tapHolders, $bitHolders): void {
            foreach ($tapHolders as $sku => [$typeRu, $typeRo, $rangeRu, $rangeRo, $drive]) {
                $typeRuText = $typeRu !== '' ? ' '.$typeRu : '';
                $typeRoText = $typeRo !== '' ? ' '.$typeRo : '';
                $driveRu = $drive ? ', привод '.$drive : '';
                $driveRo = $drive ? ', antrenare '.$drive : '';
                $nameRu = 'Держатель метчиков'.$typeRuText.' King Tony '.$sku.', '.$rangeRu.$driveRu;
                $nameRo = 'Suport'.$typeRoText.' pentru tarozi King Tony '.$sku.', '.$rangeRo.$driveRo;
                $descriptionRu = 'Держатель предназначен для фиксации и вращения метчиков размерного диапазона '.$rangeRu.$driveRu.'.';
                $descriptionRo = 'Suportul este destinat fixării și rotirii tarozilor din gama '.$rangeRo.$driveRo.'.';
                $this->updateProduct($sku, $nameRu, $nameRo, $descriptionRu, $descriptionRo);
            }

            foreach ($bitHolders as $sku => [$impact, $drive, $bitSize, $length]) {
                $typeRu = $impact ? 'Ударный держатель бит' : 'Держатель бит';
                $typeRo = $impact ? 'Adaptor de impact pentru biți' : 'Adaptor pentru biți';
                $bitSizeRo = str_replace(' мм', ' mm', $bitSize);
                $lengthRo = str_replace(' мм', ' mm', $length);
                $nameRu = $typeRu.' King Tony '.$sku.', привод '.$drive.', биты '.$bitSize;
                $nameRo = $typeRo.' King Tony '.$sku.', antrenare '.$drive.', biți '.$bitSizeRo;
                $descriptionRu = $typeRu.' соединяет привод '.$drive.' со вставками '.$bitSize.'. Общая длина — '.$length.'.';
                $descriptionRo = $typeRo.' conectează antrenarea '.$drive.' cu inserții de '.$bitSizeRo.'. Lungimea totală este de '.$lengthRo.'.';
                $this->updateProduct($sku, $nameRu, $nameRo, $descriptionRu, $descriptionRo);
            }
        });
    }

    private function updateProduct(string $sku, string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo): void
    {
        DB::table('products')->where('sku', $sku)->update([
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $descriptionRu,
            'short_description_ru' => $descriptionRu,
            'short_description_ro' => $descriptionRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'meta_title' => $nameRu.' | MasterScule.md',
            'meta_description' => $descriptionRu,
            'needs_content_review' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Verified content corrections are intentionally irreversible.
    }
};
