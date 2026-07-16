<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductLanguageQualityGuard;
use Illuminate\Console\Command;

class ParserAuditTranslations extends Command
{
    protected $signature = 'masterscule:parser-audit-translations {--mark-review} {--clear-valid}';

    protected $description = 'Audit parser product RU and RO content';

    public function handle(ProductLanguageQualityGuard $guard): int
    {
        $stats = [
            'parser_products' => 0,
            'invalid_translations' => 0,
            'marked_for_review' => 0,
            'cleared_valid_review_flags' => 0,
        ];
        Product::whereNotNull('source_import_batch_id')->orderBy('id')->chunkById(300, function ($products) use ($guard, &$stats) {
            foreach ($products as $product) {
                $stats['parser_products']++;
                if ($guard->evaluate($product)['allowed']) {
                    if ($this->option('clear-valid') && $product->needs_translation_review) {
                        $product->forceFill(['needs_translation_review' => false])->save();
                        $stats['cleared_valid_review_flags']++;
                    }

                    continue;
                }
                $stats['invalid_translations']++;
                if ($this->option('mark-review') && ! $product->needs_translation_review) {
                    $product->forceFill(['needs_translation_review' => true])->save();
                    $stats['marked_for_review']++;
                }
            }
        });
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
