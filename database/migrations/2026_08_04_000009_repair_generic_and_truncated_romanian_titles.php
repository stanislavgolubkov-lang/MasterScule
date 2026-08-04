<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $titles = $this->verifiedTitles();

            $torxProducts = DB::table('products')
                ->where('status', 'published')
                ->where('name_ro', 'like', 'Bit sau adaptor King Tony%')
                ->where('name_ru', 'like', 'Насадка бита TORX%')
                ->get(['sku', 'name_ro']);
            foreach ($torxProducts as $product) {
                $titles[$product->sku] = str_replace(
                    'Bit sau adaptor King Tony',
                    'Cap cu bit KING TONY',
                    (string) $product->name_ro,
                );
            }

            foreach ($titles as $sku => $title) {
                $product = DB::table('products')->where('sku', $sku)->first([
                    'id', 'name_ro', 'short_description_ro', 'description_ro',
                ]);
                if (! $product) {
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

            $this->moveProducts('carucioare-de-scule', ['ST87432-5B', 'ST87434-7B'], $now);
            $this->moveProducts('mobilier-pentru-service', ['87C34-3B-B'], $now);
        });
    }

    private function verifiedTitles(): array
    {
        return [
            '22319PR' => 'Set de chei TORX în L KING TONY 22319PR, T10–T50, 9 piese',
            'ST20219MY' => 'Set de chei hexagonale KING TONY ST20219MY, 1,5–10 mm, 9 piese, STARTER',
            '39124016M' => 'Suport pentru tarozi KING TONY 39124016M, M20–M27, 1/2″',
            '9CJ7432' => 'Răzuitor din carbură KING TONY 9CJ7432, 32 mm',
            '8720' => 'Clemă pentru șina de capete tubulare KING TONY 8720, 1/4″',
            '870208' => 'Șină pentru capete tubulare KING TONY 870208, 1/4″, 200 mm, fără cleme',
            '870311' => 'Șină pentru capete tubulare KING TONY 870311, 3/8″, 280 mm, fără cleme',
            '870422' => 'Șină pentru capete tubulare KING TONY 870422, 1/2″, 560 mm, fără cleme',
            '87C34-3B-B' => 'Scaun de atelier KING TONY 87C34-3B-B, 3 sertare',
            'ST87432-5B' => 'Cărucior de scule roșu KING TONY ST87432-5B, seria ST, 5 sertare',
            'ST87434-7B' => 'Cărucior de scule roșu KING TONY ST87434-7B, seria ST, 7 sertare',

            'QE-3B' => 'Mandrină rapidă M7 QE-3B, 1/2″, pentru QE-341 și QE-441',
            'QE-3A' => 'Mandrină rapidă M7 QE-3A, 3/8″, pentru QE-331 și QE-332',
            'QE-833P02' => 'Mandrină M7 QE-833P02, 3/8″, pentru QE-833',
            'QE-231P46' => 'Burghiu cu strat de titan M7 QE-231P46, 8 mm, pentru găurirea punctelor de sudură',
            'QB-913' => 'Disc de tăiere M7 QB-913, 76 mm (3″), pentru QC-213',
            'QB-914' => 'Disc de tăiere M7 QB-914, 100 mm (4″), pentru QC-234',
            'QD-221T49' => 'Set de pile M7 QD-221T49, Ø4 mm, 5 piese, pentru QD-221',
            'QD-230T36' => 'Set de pile M7 QD-230T36, Ø5 mm, 5 piese, pentru QD-230',
            'QD-924' => 'Set de lame bimetalice M7 QD-924, 24 TPI, 0,025″, 10 piese',
            'QD-932' => 'Set de lame bimetalice M7 QD-932, 32 TPI, 0,025″, 10 piese',
            'QB-904' => 'Disc abraziv M7 QB-904, 100 mm (4″)',
            'QB-905' => 'Disc abraziv M7 QB-905, 125 mm (5″)',
            'QB-907' => 'Disc abraziv M7 QB-907, 178 mm (7″)',
            'QP-123P31' => 'Talpă de schimb M7 QP-123P31, 76 mm (3″)',
            'QB-9323F' => 'Talpă de schimb M7 QB-9323F, 76 mm (3″), pentru QP-9323 și QP-123',
            'ZF-01' => 'Protecție magnetică pentru aripă M7 ZF-01, 1050 × 650 mm, neagră',
            'ZC-112' => 'Geantă pentru scule M7 ZC-112, bază din cauciuc, 40 × 22 × 30 cm',
            'ZC-111' => 'Geantă pentru scule M7 ZC-111, 37 × 24 × 23 cm',
            'SX-4101' => 'Pistol pneumatic de sablare M7 SX-4101, rezervor 0,6 l',

            '7962-06' => 'Extractor pentru rulmenți KING TONY 7962-06',
            '42114GP' => 'Set de clești pentru inele de siguranță KING TONY 42114GP, 4 piese',
            '42116GP' => 'Set de clești pentru inele de siguranță KING TONY 42116GP, 6 piese',
            '6721-10' => 'Clește multifuncțional KING TONY 6721-10 pentru dezizolare, tăiere și sertizare',

            'JTC-4046' => 'Extractor pentru filtru de ulei JTC-4046, 46 mm, 6 muchii',
            'JTC-4756' => 'Set de capete pentru demontarea injectoarelor de camion JTC-4756, 4 piese',
            'JTC-3219S' => 'Set de chei inelare lungi JTC-3219S, 6 piese',
            'JTC-3325S' => 'Set de chei inelare cu clichet deschis JTC-3325S, 10–22 mm, 6 piese',
            'JTC-1341' => 'Set de cleme pentru obturarea conductelor JTC-1341, 4 piese',
            'JTC-1543' => 'Set de capete pentru senzori de oxigen JTC-1543, 4 piese',
            'JTC-4865' => 'Set de capete pentru extragerea injectoarelor diesel JTC-4865, 4 piese',
            'JTC-4315' => 'Set pentru verificarea etanșeității sistemului turbo JTC-4315, 4 perechi de adaptoare',
            'JTC-4062' => 'Set de cârlige pentru demontarea simeringurilor, inelelor și furtunurilor JTC-4062, 6 piese',
            'JTC-4085' => 'Dispozitiv de blocare a fuliei arborelui cu came JTC-4085, 4 puncte de sprijin',
            'JTC-5625' => 'Set de extractoare pentru panouri de interior JTC-5625, 6 piese',
            'JTC-4399' => 'Set de capete pentru șuruburi și piulițe deteriorate JTC-4399, 6 piese',
            'JTC-6722' => 'Set de cârlige pentru demontarea simeringurilor și inelelor JTC-6722, 4 piese',
            'JTC-AM44A' => 'Tavă mică pentru scurgerea uleiului JTC-AM44A, 6,6 l',
            'JTC-3123' => 'Menghină rotativă de banc JTC-3123, 6″',
            'JTC-3207' => 'Set de șurubelniță de impact JTC-3207, 6 biți',
            'JTC-6736' => 'Extractor pentru brațe de ștergător și borne de baterie JTC-6736',
            'JTC-2027' => 'Set de terminale auto izolate JTC-2027, 530 piese',
            'JTC-1363' => 'Set pentru refacerea filetelor racordurilor de climatizare JTC-1363, 6 piese',
            'JTC-1529' => 'Set pentru demontarea terminalelor electrice JTC-1529, 12 piese',
            'JTC-4688' => 'Set pentru demontarea terminalelor electrice JTC-4688, 23 piese',
            'JTC-6673' => 'Set pentru demontarea terminalelor electrice JTC-6673, 30 piese',
            'JW0321' => 'Set de extractoare pentru compresorul instalației de climatizare JTC JW0321, 6 piese',
            'JTC-5621' => 'Clește automat JTC-5621 pentru dezizolare, tăiere și sertizare',
            'JTC-5209' => 'Clește JTC-5209 pentru dezizolare, tăiere și sertizare',
            'JTC-5628' => 'Extractor JTC-5628 pentru rulmenți de alternator, borne și brațe de ștergător',
            'JTC-1261' => 'Perie dublă pentru curățarea bornelor bateriei JTC-1261',

            'JTC-1622' => 'Tester profesional pentru pompe de vid și combustibil JTC-1622',
            'JTC-4251' => 'Tester pentru presiunea uleiului servodirecției JTC-4251, în cutie',
            'JTC-4250' => 'Tester pentru presiunea uleiului din cutia de viteze JTC-4250, cu două manometre',
            'JTC-1256' => 'Tester pentru presiunea uleiului cu adaptoare JTC-1256',
            'JTC-1538A' => 'Tester digital pentru lichid de frână JTC-1538A, 5 indicatoare',
            'JTC-4790' => 'Set de conectori pentru testarea componentelor auto JTC-4790, 23 piese',
            'JTC-1720A' => 'Tester pentru verificarea scânteii de aprindere JTC-1720A',
            'JTC-1440' => 'Tester pentru semnalele injectoarelor JTC-1440',
        ];
    }

    private function moveProducts(string $slug, array $skus, $now): void
    {
        $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
        if (! $categoryId) {
            return;
        }

        foreach (DB::table('products')->whereIn('sku', $skus)->get(['id']) as $product) {
            DB::table('products')->where('id', $product->id)->update([
                'category_id' => $categoryId,
                'needs_category_review' => false,
                'updated_at' => $now,
            ]);
            DB::table('category_product')->where('product_id', $product->id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'is_primary' => true,
                'source' => 'verified_deep_audit',
                'confidence' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Verified terminology and category corrections are intentionally retained.
    }
};
