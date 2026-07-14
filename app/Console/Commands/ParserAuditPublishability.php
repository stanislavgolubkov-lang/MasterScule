<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Console\Command;

class ParserAuditPublishability extends Command
{
    protected $signature = 'masterscule:parser-audit-publishability';

    protected $description = 'Audit parser products through the publication guard';

    public function handle(ProductPublicationGuard $guard): int
    {
        $stats = ['parser_products' => 0, 'publishable' => 0, 'blocked' => 0];
        $reasons = [];
        Product::whereNotNull('source_import_batch_id')->orderBy('id')->chunkById(300, function ($products) use ($guard, &$stats, &$reasons) {
            foreach ($products as $product) {
                $stats['parser_products']++;
                $result = $guard->evaluate($product, true);
                $stats[$result['allowed'] ? 'publishable' : 'blocked']++;
                foreach ($result['error_codes'] as $code) {
                    $reasons[$code] = ($reasons[$code] ?? 0) + 1;
                }
            }
        });
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());
        if ($reasons !== []) {
            arsort($reasons);
            $this->table(['Reason', 'Count'], collect($reasons)->map(fn ($count, $reason) => [$reason, $count])->values()->all());
        }

        return self::SUCCESS;
    }
}
