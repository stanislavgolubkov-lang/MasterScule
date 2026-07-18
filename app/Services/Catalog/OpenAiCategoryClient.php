<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCategoryClient
{
    public function __construct(private CategoryTaxonomy $taxonomy) {}

    public function configured(): bool
    {
        return filled(config('catalog_ai.api_key'));
    }

    public function classify(Product $product, array $input, Collection $candidates): array
    {
        $slugs = $candidates->pluck('slug')->values()->all();

        return $this->request(
            (string) config('catalog_ai.model'),
            'You classify professional tools and workshop products into one canonical category. '
                .'Use only the supplied category slugs. Prefer the most specific category supported by product identity, purpose, source breadcrumb and attributes. '
                .'Do not infer a product type from incidental specifications such as noise level, weight or dimensions. Return concise evidence.',
            [
                'product' => $input,
                'candidate_categories' => $this->taxonomy->payload($candidates->pluck('category')),
            ],
            'catalog_category_classification',
            [
                'type' => 'object',
                'properties' => [
                    'category_slug' => ['type' => 'string', 'enum' => $slugs],
                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                    'evidence' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 5],
                    'rejected_categories' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $slugs], 'maxItems' => 5],
                ],
                'required' => ['category_slug', 'confidence', 'evidence', 'rejected_categories'],
                'additionalProperties' => false,
            ],
        );
    }

    public function verify(Product $product, array $input, Collection $candidates, array $classification): array
    {
        $slugs = $candidates->pluck('slug')->values()->all();

        return $this->request(
            (string) config('catalog_ai.verifier_model'),
            'You independently verify a proposed catalog category. Reject it if evidence is weak, if a more specific supplied category fits, '
                .'or if the proposal relies on incidental specifications. Use only supplied slugs. Be conservative: acceptance authorizes an automatic storefront change.',
            [
                'product' => $input,
                'candidate_categories' => $this->taxonomy->payload($candidates->pluck('category')),
                'proposed_decision' => $classification,
            ],
            'catalog_category_verification',
            [
                'type' => 'object',
                'properties' => [
                    'accepted' => ['type' => 'boolean'],
                    'corrected_category_slug' => ['type' => 'string', 'enum' => $slugs],
                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                    'conflicts' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 5],
                ],
                'required' => ['accepted', 'corrected_category_slug', 'confidence', 'conflicts'],
                'additionalProperties' => false,
            ],
        );
    }

    private function request(string $model, string $instructions, array $input, string $schemaName, array $schema): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $response = $this->http()->post('/responses', [
            'model' => $model,
            'instructions' => $instructions,
            'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'reasoning' => ['effort' => (string) config('catalog_ai.reasoning_effort', 'medium')],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ])->throw()->json();

        $text = collect($response['output'] ?? [])
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->first(fn (array $content) => ($content['type'] ?? null) === 'output_text')['text'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new RuntimeException('OpenAI response did not contain structured output text.');
        }

        $decoded = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI structured output is malformed.');
        }

        return $decoded;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('catalog_ai.base_url'))
            ->withToken((string) config('catalog_ai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('catalog_ai.timeout', 60))
            ->retry(2, 500);
    }
}
