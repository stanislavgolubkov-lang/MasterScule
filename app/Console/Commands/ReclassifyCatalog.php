<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\AutomaticCategoryAgent;
use App\Services\Catalog\OpenAiCategoryClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReclassifyCatalog extends Command
{
    protected $signature = 'masterscule:reclassify-catalog
        {--apply : Apply accepted category changes}
        {--force : Required safety confirmation for --apply}
        {--limit=0 : Maximum number of products to inspect}
        {--show=30 : Number of sample rows to display}
        {--product= : Inspect one product ID or SKU}
        {--from= : Only products currently in this category slug}
        {--to= : Only decisions proposing this category slug}
        {--changed : Skip inputs already applied for the current taxonomy version}
        {--ai : Require OpenAI classification and verification}
        {--no-ai : Use deterministic rules only}';

    protected $description = 'Classify products into canonical catalog categories with validation and an audit trail';

    public function handle(AutomaticCategoryAgent $agent, OpenAiCategoryClient $openAi): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && ! $this->option('force')) {
            $this->error('--apply requires --force. Run without --apply for a read-only preview.');

            return self::FAILURE;
        }

        if ($this->option('ai') && $this->option('no-ai')) {
            $this->error('Use either --ai or --no-ai, not both.');

            return self::FAILURE;
        }

        if ($this->option('ai') && ! $openAi->configured()) {
            $this->error('OPENAI_API_KEY is not configured; strict AI mode cannot run.');

            return self::FAILURE;
        }

        $useAi = ! $this->option('no-ai') && ($this->option('ai') || config('catalog_ai.enabled'));
        $limit = max(0, (int) $this->option('limit'));
        $processed = $accepted = $changed = $acceptedChanges = $applied = $rejected = $unchangedInput = 0;
        $samples = [];
        $transitions = [];
        $show = max(0, min(200, (int) $this->option('show')));
        $query = Product::query()->with(['brand', 'category', 'parserItem']);

        if ($product = trim((string) $this->option('product'))) {
            $query->where(fn ($match) => $match
                ->when(ctype_digit($product), fn ($idQuery) => $idQuery->where('id', (int) $product))
                ->orWhere('sku', $product));
        }

        if ($from = trim((string) $this->option('from'))) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $from));
        }

        foreach ($query->lazyById(100) as $product) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $decision = $agent->decide($product, $useAi);

            if (($to = trim((string) $this->option('to'))) && $decision['selected_slug'] !== $to) {
                continue;
            }

            if ($this->option('changed') && $product->categoryDecisions()
                ->where('taxonomy_version', $decision['taxonomy_version'])
                ->where('input_hash', $decision['input_hash'])
                ->exists()) {
                $unchangedInput++;

                continue;
            }

            $processed++;
            $accepted += (int) $decision['can_apply'];
            $changed += (int) $decision['changed'];
            $acceptedChanges += (int) ($decision['can_apply'] && $decision['changed']);
            $rejected += (int) ! $decision['can_apply'];

            if ($decision['can_apply'] && $decision['changed']) {
                $transition = ($product->category?->slug ?: '-').' -> '.($decision['selected_slug'] ?: '-');
                $transitions[$transition] = ($transitions[$transition] ?? 0) + 1;
            }

            if (count($samples) < $show && ($decision['changed'] || ! $decision['can_apply'])) {
                $samples[] = [
                    $product->id,
                    $product->sku,
                    Str::limit($product->name_ru ?: $product->name, 55),
                    $product->category?->slug ?: '-',
                    $decision['selected_slug'] ?: '-',
                    number_format($decision['classifier_confidence'], 3),
                    Str::limit(implode(', ', $decision['evidence']), 70),
                    $decision['can_apply'] ? 'accepted' : (implode('; ', $decision['validation_errors']) ?: 'low confidence'),
                ];
            }

            if (! $apply) {
                continue;
            }

            if ($decision['can_apply'] && $decision['changed']) {
                $agent->apply($product, $decision);
                $applied++;
            } elseif ($decision['can_apply']) {
                $agent->record($decision, 'confirmed');
            } elseif (! $decision['can_apply']) {
                $agent->record($decision, 'rejected');
            }
        }

        if ($samples !== []) {
            $this->table(['ID', 'SKU', 'Product', 'Current', 'Proposed', 'Confidence', 'Evidence', 'Result'], $samples);
        }

        if ($transitions !== []) {
            arsort($transitions);
            $this->table(
                ['Accepted category transition', 'Products'],
                collect($transitions)->take(50)->map(fn (int $count, string $transition) => [$transition, $count])->values()->all(),
            );
        }

        $this->newLine();
        $this->info(json_encode([
            'mode' => $useAi ? 'openai_when_configured' : 'deterministic',
            'write_mode' => $apply ? 'apply' : 'dry-run',
            'processed' => $processed,
            'accepted' => $accepted,
            'proposed_changes' => $changed,
            'accepted_changes' => $acceptedChanges,
            'applied' => $applied,
            'rejected' => $rejected,
            'skipped_unchanged_input' => $unchangedInput,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
