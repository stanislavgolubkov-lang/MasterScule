<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductCategoryDecision;
use Illuminate\Support\Facades\DB;
use Throwable;

class AutomaticCategoryAgent
{
    public function __construct(
        private CategoryCandidateService $candidates,
        private CategoryDecisionValidator $validator,
        private CategoryTaxonomy $taxonomy,
        private OpenAiCategoryClient $openAi,
    ) {}

    public function decide(Product $product, bool $useAi = true): array
    {
        $ranked = $this->candidates->rank($product);
        $selectedSlug = $ranked['selected_slug'];
        $classifierConfidence = (float) $ranked['confidence'];
        $verifierConfidence = 0.0;
        $evidence = $ranked['evidence'];
        $validationErrors = [];
        $mode = 'deterministic';
        $verifier = null;

        if ($useAi && config('catalog_ai.enabled') && $this->openAi->configured()) {
            $mode = 'openai';

            try {
                $classification = $this->openAi->classify($product, $ranked['input'], $ranked['candidates']);
                $selectedSlug = $classification['category_slug'] ?? null;
                $classifierConfidence = (float) ($classification['confidence'] ?? 0);
                $evidence = $classification['evidence'] ?? [];
                $verifier = $this->openAi->verify($product, $ranked['input'], $ranked['candidates'], $classification);
                $verifierConfidence = (float) ($verifier['confidence'] ?? 0);

                if (! ($verifier['accepted'] ?? false)) {
                    $validationErrors[] = 'independent_verifier_rejected';
                }
                if (($verifier['corrected_category_slug'] ?? null) !== $selectedSlug) {
                    $validationErrors[] = 'independent_verifier_disagreed';
                }
                $validationErrors = array_merge($validationErrors, $verifier['conflicts'] ?? []);
            } catch (Throwable $exception) {
                $validationErrors[] = 'openai_error: '.$exception->getMessage();
            }
        }

        $selected = $selectedSlug ? $this->taxonomy->findAssignable($selectedSlug) : null;
        $validationErrors = array_values(array_unique(array_merge(
            $validationErrors,
            $this->validator->validate($product, $selected?->slug),
        )));

        $minimum = $mode === 'openai'
            ? (float) config('catalog_ai.minimum_confidence', 0.96)
            : (float) config('catalog_ai.deterministic_minimum_confidence', 0.97);
        $confidencePassed = $classifierConfidence >= $minimum
            && ($mode !== 'openai' || $verifierConfidence >= $minimum);

        return [
            'product_id' => $product->id,
            'previous_category_id' => $product->category_id,
            'selected_category_id' => $selected?->id,
            'selected_slug' => $selected?->slug,
            'taxonomy_version' => $this->taxonomy->version(),
            'input_hash' => $ranked['input_hash'],
            'mode' => $mode,
            'model' => $mode === 'openai' ? config('catalog_ai.model') : null,
            'verifier_model' => $mode === 'openai' ? config('catalog_ai.verifier_model') : null,
            'classifier_confidence' => $classifierConfidence,
            'verifier_confidence' => $verifierConfidence,
            'evidence' => array_values(array_unique($evidence)),
            'alternatives' => $ranked['candidates']->map(fn (array $candidate) => [
                'slug' => $candidate['slug'],
                'score' => $candidate['score'],
            ])->take(5)->values()->all(),
            'validation_errors' => $validationErrors,
            'can_apply' => $selected !== null && $confidencePassed && $validationErrors === [],
            'changed' => $selected !== null && (int) $product->category_id !== (int) $selected->id,
        ];
    }

    public function apply(Product $product, array $decision): ProductCategoryDecision
    {
        return DB::transaction(function () use ($product, $decision) {
            if (! $decision['can_apply']) {
                return $this->record($decision, 'rejected');
            }

            $categoryId = (int) $decision['selected_category_id'];
            $product->forceFill([
                'category_id' => $categoryId,
                'needs_category_review' => false,
            ])->save();
            $product->syncCategoryLinks(
                [$categoryId],
                $categoryId,
                'catalog_agent',
                [$categoryId => (int) round($decision['classifier_confidence'] * 100)],
            );

            return $this->record($decision, 'applied');
        });
    }

    public function record(array $decision, string $status = 'proposed'): ProductCategoryDecision
    {
        return ProductCategoryDecision::create([
            'product_id' => $decision['product_id'],
            'previous_category_id' => $decision['previous_category_id'],
            'selected_category_id' => $decision['selected_category_id'],
            'taxonomy_version' => $decision['taxonomy_version'],
            'input_hash' => $decision['input_hash'],
            'mode' => $decision['mode'],
            'status' => $status,
            'model' => $decision['model'],
            'verifier_model' => $decision['verifier_model'],
            'classifier_confidence' => $decision['classifier_confidence'],
            'verifier_confidence' => $decision['verifier_confidence'],
            'evidence' => $decision['evidence'],
            'alternatives' => $decision['alternatives'],
            'validation_errors' => $decision['validation_errors'],
            'applied_at' => $status === 'applied' ? now() : null,
        ]);
    }
}
