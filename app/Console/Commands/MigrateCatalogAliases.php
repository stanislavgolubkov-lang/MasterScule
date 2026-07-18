<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategoryDecision;
use App\Services\Catalog\CategoryTaxonomy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCatalogAliases extends Command
{
    protected $signature = 'masterscule:migrate-catalog-aliases
        {--apply : Move products from legacy aliases to canonical categories}
        {--force : Required safety confirmation for --apply}';

    protected $description = 'Move products out of deprecated category aliases with an audit trail';

    public function handle(CategoryTaxonomy $taxonomy): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && ! $this->option('force')) {
            $this->error('--apply requires --force. Run without --apply for a read-only preview.');

            return self::FAILURE;
        }

        $rows = [];
        $moved = 0;

        foreach (config('catalog_taxonomy.aliases', []) as $aliasSlug => $targetSlug) {
            $alias = Category::where('slug', $aliasSlug)->first();
            $target = $taxonomy->findAssignable($targetSlug);
            $count = $alias ? Product::where('category_id', $alias->id)->count() : 0;
            $rows[] = [$aliasSlug, $targetSlug, $count, $alias && $target ? 'ready' : 'missing category'];

            if (! $apply || ! $alias || ! $target || $count === 0) {
                continue;
            }

            Product::where('category_id', $alias->id)
                ->orderBy('id')
                ->chunkById(100, function ($products) use ($alias, $target, $taxonomy, &$moved): void {
                    foreach ($products as $product) {
                        DB::transaction(function () use ($product, $alias, $target, $taxonomy, &$moved): void {
                            $product->forceFill([
                                'category_id' => $target->id,
                                'needs_category_review' => false,
                            ])->save();
                            $product->syncCategoryLinks([$target->id], $target->id, 'taxonomy_alias', [$target->id => 100]);

                            ProductCategoryDecision::create([
                                'product_id' => $product->id,
                                'previous_category_id' => $alias->id,
                                'selected_category_id' => $target->id,
                                'taxonomy_version' => $taxonomy->version(),
                                'input_hash' => hash('sha256', 'taxonomy-alias|'.$product->id.'|'.$alias->slug.'|'.$target->slug),
                                'mode' => 'taxonomy_alias',
                                'status' => 'applied',
                                'classifier_confidence' => 1,
                                'verifier_confidence' => 1,
                                'evidence' => ['canonical alias: '.$alias->slug.' -> '.$target->slug],
                                'alternatives' => [],
                                'validation_errors' => [],
                                'applied_at' => now(),
                            ]);
                            $moved++;
                        });
                    }
                });
        }

        $this->table(['Legacy category', 'Canonical category', 'Products', 'Status'], $rows);
        $this->info(($apply ? 'Moved' : 'Products ready to move').': '.($apply ? $moved : collect($rows)->sum(2)));

        return collect($rows)->contains(fn (array $row) => $row[3] === 'missing category')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
