<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $records = [
                'JTC-4898' => [
                    'name_ro' => 'Tarod lung pentru refacerea filetului bujiei JTC-4898, M12 × 1,25',
                    'description_ro' => 'Tarodul lung JTC-4898 este destinat refacerii filetului M12 × 1,25 din locașurile bujiilor. Forma alungită facilitează accesul în zonele adânci ale chiulasei.',
                ],
                'JTC-4899' => [
                    'name_ro' => 'Tarod lung pentru refacerea filetului bujiei JTC-4899, M14 × 1,25',
                    'description_ro' => 'Tarodul lung JTC-4899 este destinat refacerii filetului M14 × 1,25 din locașurile bujiilor. Forma alungită facilitează accesul în zonele adânci ale chiulasei.',
                ],
                '37335-076' => [
                    'name_ro' => 'Clichet pneumatic KING TONY 37335-076, 3/8″, 102 Nm',
                    'description_ro' => 'Clichet pneumatic KING TONY 37335-076 cu pătrat de 3/8″ și cuplu maxim de 102 Nm. Viteza liberă este de 200 rot/min, consumul mediu de aer de 113 l/min, iar presiunea de lucru de 6,2 bar. Nivelul de zgomot este de 92 dB(A), vibrațiile de 10,2 m/s², lungimea de 260 mm și greutatea de 1,2 kg.',
                ],
                '37435-076' => [
                    'name_ro' => 'Clichet pneumatic KING TONY 37435-076, 1/2″, 102 Nm',
                    'description_ro' => 'Clichet pneumatic KING TONY 37435-076 cu pătrat de 1/2″ și cuplu maxim de 102 Nm. Viteza liberă este de 200 rot/min, consumul mediu de aer de 113 l/min, iar presiunea de lucru de 6,2 bar. Nivelul de zgomot este de 92 dB(A), vibrațiile de 10,5 m/s², lungimea de 260 mm și greutatea de 1,2 kg.',
                ],
                '77328-26' => [
                    'name_ro' => 'Set de lere KING TONY 77328-26, 26 lame, 0,038–0,635 mm',
                    'description_ro' => 'Setul de lere KING TONY 77328-26 este destinat măsurării și reglării jocurilor la rulmenți, supape, segmenți și alte îmbinări mecanice. Include 26 de lame cu grosimi de la 0,0015″ la 0,025″, echivalentul a 0,038–0,635 mm.',
                ],
            ];

            foreach ($records as $sku => $content) {
                $short = preg_match('/^(.+?[.!?])(?:\s|$)/u', $content['description_ro'], $match) === 1
                    ? $match[1]
                    : $content['description_ro'];
                DB::table('products')->where('sku', $sku)->update([
                    'name_ro' => $content['name_ro'],
                    'short_description_ro' => $short,
                    'description_ro' => $content['description_ro'],
                    'updated_at' => $now,
                ]);
            }

            DB::table('products')->orderBy('id')->chunkById(250, function ($products) use ($now): void {
                foreach ($products as $product) {
                    $updates = [];
                    foreach (['short_description_ro', 'description_ro'] as $column) {
                        $value = (string) $product->{$column};
                        $clean = preg_replace('/\.{3,}/u', '…', $value) ?? $value;
                        $clean = preg_replace('/\.{2}/u', '.', $clean) ?? $clean;
                        $clean = str_replace(['mm.;', 'kg.;'], ['mm;', 'kg;'], $clean);
                        if ($clean !== $value) {
                            $updates[$column] = $clean;
                        }
                    }
                    if ($updates !== []) {
                        $updates['updated_at'] = $now;
                        DB::table('products')->where('id', $product->id)->update($updates);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Verified localization corrections are intentionally not reverted.
    }
};
