<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductParserItem;
use App\Services\ProductParserContentBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshCatalogLocalization extends Command
{
    protected $signature = 'masterscule:refresh-catalog-localization {--limit=0}';

    protected $description = 'Disabled automatic catalog localization';

    public function handle(ProductParserContentBuilder $contentBuilder): int
    {
        $this->error('Automatic catalog localization is disabled. Use translation review in admin.');

        return self::FAILURE;

        $limit = max(0, (int) $this->option('limit'));
        $updated = 0;
        $missingSource = 0;

        Product::with('brand')->orderBy('id')->chunkById(300, function ($products) use ($contentBuilder, $limit, &$updated, &$missingSource) {
            foreach ($products as $product) {
                if ($limit > 0 && $updated >= $limit) {
                    return false;
                }

                $item = ProductParserItem::where('id', $product->source_parser_item_id)
                    ->orWhere(function ($query) use ($product) {
                        $query->where('sku', $product->sku)->whereNotNull('raw_name');
                    })
                    ->latest('id')
                    ->first();

                if (! $item) {
                    $missingSource++;

                    continue;
                }

                $content = $contentBuilder->build(
                    $product->sku,
                    $item->raw_name ?: $item->parsed_name ?: $product->name,
                    $product->brand?->name,
                    $item->detected_group,
                );

                $item->forceFill($content)->save();
                $product->forceFill([
                    'name' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $content['short_description_ru'],
                    'description' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'meta_title' => Str::limit($content['name_ru'].' | '.config('store.domain_label'), 255, ''),
                    'meta_description' => Str::limit($content['short_description_ru'], 155, ''),
                ])->save();
                $updated++;
            }

            return true;
        });

        $this->info(json_encode([
            'updated' => $updated,
            'missing_source' => $missingSource,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
