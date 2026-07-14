<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchOfficialProductImagesJob;
use App\Jobs\FetchTrisToolsFallbackDataJob;
use App\Jobs\FindOfficialProductSourceJob;
use App\Jobs\ParsePriceListJob;
use App\Jobs\ParseSingleSkuJob;
use App\Jobs\ParseSkuBatchJob;
use App\Jobs\ProcessProductImagesJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\Catalog\ProductPublicationGuard;
use App\Services\ProductDraftService;
use App\Services\ProductParserSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class ProductParserController extends Controller
{
    private const PUBLICATION_APPROVAL_FLAGS = [
        'needs_translation_review',
        'needs_content_review',
        'needs_price_review',
        'needs_stock_review',
    ];

    public function __construct(private ProductParserSettings $settings) {}

    public function index()
    {
        $this->guard('parser.view');

        return view('admin.parser.index', [
            'batches' => ProductParserBatch::with('user')->withCount('items')->latest()->paginate(10),
            'priceBatches' => ProductParserBatch::with('user')->withCount('items')->where('source_type', 'price_list')->latest()->limit(8)->get(),
            'draftItems' => ProductParserItem::with(['createdProduct', 'category', 'batch'])
                ->whereNotNull('created_product_id')
                ->latest()
                ->limit(8)
                ->get(),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name_ro')->get(),
            'settings' => $this->settings->all(),
        ]);
    }

    public function storePriceList(Request $request)
    {
        $this->guard('parser.import');
        $this->ensureEnabled();

        $maxFileSize = (int) $this->settings->get('max_file_size_kb', 20480);
        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:160'],
            'brand_default' => ['nullable', 'string', 'max:120'],
            'category_default_id' => ['nullable', 'exists:categories,id'],
            'price_file' => ['required', 'file', 'max:'.$maxFileSize],
            'price_type' => ['required', Rule::in(['retail_price'])],
            'import_mode' => ['required', Rule::in(['dry_run', 'create_drafts', 'review_only'])],
            'update_existing_products' => ['nullable', 'boolean'],
            'add_photos_to_existing' => ['nullable', 'boolean'],
            'replace_existing_photos' => ['nullable', 'boolean'],
            'search_images' => ['nullable', 'boolean'],
            'translate_descriptions' => ['nullable', 'boolean'],
            'create_drafts_automatically' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('price_file');
        $extension = $this->validateParserUpload(
            $file,
            $this->settings->get('allowed_formats', ['xls', 'xlsx', 'csv']),
            $this->priceListMimeMap(),
            'price_file'
        );

        $storedPath = $file->storeAs(
            'parser/imports/'.now()->format('YmdHis'),
            uniqid('price_', true).'.'.$extension,
            'local'
        );

        $batch = ProductParserBatch::create([
            'user_id' => $request->user()->id,
            'title' => ($data['supplier_name'] ?: __('ui.parser_price_import')).' - '.$file->getClientOriginalName(),
            'source_type' => 'price_list',
            'supplier_name' => $data['supplier_name'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_type' => $extension,
            'brand_default' => $this->brandValue($data['brand_default'] ?? null),
            'category_default_id' => $data['category_default_id'] ?? null,
            'price_type' => 'retail_price',
            'import_mode' => 'dry_run',
            'status' => 'pending',
            'options_json' => [
                'requested_import_mode' => $data['import_mode'] === 'dry_run' ? 'create_drafts' : $data['import_mode'],
                'update_existing_products' => $request->boolean('update_existing_products'),
                'add_photos_to_existing' => $request->boolean('add_photos_to_existing', true),
                'replace_existing_photos' => $request->boolean('replace_existing_photos'),
                'search_images' => $request->boolean('search_images', true),
                'process_images' => $request->boolean('search_images', true),
                'translate_descriptions' => $request->boolean('translate_descriptions', true),
                'create_drafts_automatically' => $data['import_mode'] !== 'review_only' && $request->boolean('create_drafts_automatically', true),
                'image_limit' => (int) $this->settings->get('max_images_per_product', 4),
            ],
        ]);
        $batch->addLog('Price list uploaded by admin', ['file' => $file->getClientOriginalName()]);

        ParsePriceListJob::dispatch($batch->id);

        return redirect()->route('admin.parser.batches.show', $batch)->with('success', __('ui.parser_price_dry_run_started'));
    }

    public function runPriceListImport(Request $request, ProductParserBatch $batch)
    {
        $this->guard('parser.run');
        $this->ensureEnabled();

        abort_unless($batch->source_type === 'price_list', 404);

        $data = $request->validate([
            'import_mode' => ['required', Rule::in(['create_drafts', 'review_only'])],
            'row_limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $options = $batch->options_json ?: [];
        $options['create_drafts_automatically'] = $data['import_mode'] === 'create_drafts'
            && (bool) ($options['create_drafts_automatically'] ?? true);
        $options['row_limit'] = $data['row_limit'] ?? null;

        $batch->forceFill([
            'import_mode' => $data['import_mode'],
            'status' => 'pending',
            'options_json' => $options,
            'started_at' => null,
            'finished_at' => null,
        ])->save();
        $batch->addLog('Admin started import from dry-run report', [
            'mode' => $data['import_mode'],
            'row_limit' => $data['row_limit'] ?? null,
        ]);

        ParsePriceListJob::dispatch($batch->id);

        return redirect()->route('admin.parser.batches.show', $batch)->with('success', __('ui.parser_price_import_started'));
    }

    public function storeSingle(Request $request)
    {
        $this->guard('parser.run');
        $this->ensureEnabled();

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language' => ['required', Rule::in(['auto', 'ru', 'ro', 'en'])],
            'image_limit' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $batch = ProductParserBatch::create([
            'user_id' => $request->user()->id,
            'title' => 'Single SKU '.$data['sku'],
            'source_type' => 'single',
            'sku_count' => 1,
            'status' => 'pending',
            'options_json' => [
                'language' => $data['language'],
                'image_limit' => (int) $data['image_limit'],
                'mode' => 'find_only',
            ],
        ]);

        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => trim($data['sku']),
            'brand' => $this->brandValue($data['brand'] ?? null),
            'category_id' => $data['category_id'] ?? null,
            'status' => 'queued',
        ]);

        ParseSingleSkuJob::dispatch($item->id);

        return redirect()->route('admin.parser.items.show', $item)->with('success', __('ui.parser_single_started'));
    }

    public function storeBatch(Request $request)
    {
        $this->guard('parser.run');
        $this->ensureEnabled();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'sku_text' => ['nullable', 'string'],
            'sku_file' => ['nullable', 'file', 'max:4096'],
            'brand' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language' => ['required', Rule::in(['auto', 'ru', 'ro', 'en'])],
            'mode' => ['required', Rule::in(['find_only', 'find_prepare_photos', 'create_drafts'])],
        ]);

        $skuFile = $request->file('sku_file');
        if ($skuFile) {
            $this->validateParserUpload($skuFile, ['csv', 'txt', 'xlsx'], $this->skuFileMimeMap(), 'sku_file');
        }

        $rows = collect($this->rowsFromText($data['sku_text'] ?? ''))
            ->merge($skuFile ? $this->rowsFromFile($skuFile->getRealPath(), $skuFile->getClientOriginalExtension()) : [])
            ->filter(fn ($row) => trim((string) ($row['sku'] ?? '')) !== '')
            ->unique(fn ($row) => mb_strtolower(trim((string) $row['sku'])))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withErrors(['sku_text' => __('ui.parser_no_sku')])->withInput();
        }

        $max = (int) $this->settings->get('max_sku_per_batch', 100);
        if ($rows->count() > $max) {
            return back()->withErrors(['sku_text' => __('ui.parser_too_many_sku', ['max' => $max])])->withInput();
        }

        $batch = ProductParserBatch::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?: 'Parser batch '.now()->format('Y-m-d H:i'),
            'source_type' => 'batch',
            'sku_count' => $rows->count(),
            'status' => 'pending',
            'options_json' => [
                'language' => $data['language'],
                'mode' => $data['mode'],
                'image_limit' => (int) $this->settings->get('max_images_per_product', 4),
            ],
        ]);

        foreach ($rows as $row) {
            ProductParserItem::create([
                'batch_id' => $batch->id,
                'sku' => trim((string) $row['sku']),
                'brand' => $this->brandValue($row['brand'] ?? $data['brand'] ?? null),
                'category_id' => $this->categoryValue($row['category'] ?? null) ?: ($data['category_id'] ?? null),
                'status' => 'queued',
            ]);
        }

        $batch->addLog('Batch created by admin', ['sku_count' => $rows->count(), 'mode' => $data['mode']]);
        ParseSkuBatchJob::dispatch($batch->id);

        return redirect()->route('admin.parser.batches.show', $batch)->with('success', __('ui.parser_batch_started'));
    }

    public function showBatch(ProductParserBatch $batch)
    {
        $this->guard('parser.view');

        $items = $batch->items()
            ->with(['category', 'existingProduct', 'createdProduct', 'imageAssets'])
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(request('needs_category'), fn ($query) => $query->where('needs_category_review', true))
            ->when(request('no_images'), fn ($query) => $query->where('needs_image_review', true))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $bulkStats = $this->bulkStats($batch);

        return view('admin.parser.batch', compact('batch', 'items', 'bulkStats'));
    }

    public function showItem(ProductParserItem $item, ProductPublicationGuard $publicationGuard)
    {
        $this->guard('parser.view');
        $item->load(['batch', 'category', 'detectedCategory', 'sources', 'imageAssets', 'existingProduct.brand', 'createdProduct.brand', 'createdProduct.category']);

        return view('admin.parser.item', [
            'item' => $item,
            'publicationCheck' => $item->createdProduct
                ? $publicationGuard->evaluate($item->createdProduct, true)
                : null,
            'categories' => Category::orderBy('sort_order')->orderBy('name_ro')->get(),
        ]);
    }

    public function drafts()
    {
        $this->guard('parser.view');

        $items = ProductParserItem::with(['batch', 'category', 'createdProduct.brand'])
            ->whereNotNull('created_product_id')
            ->latest()
            ->paginate(30);

        return view('admin.parser.drafts', compact('items'));
    }

    public function rules()
    {
        $this->guard('parser.category_rules');

        return view('admin.parser.rules', [
            'settings' => $this->settings->all(),
            'categories' => Category::orderBy('sort_order')->orderBy('name_ro')->get(),
        ]);
    }

    public function updateRules(Request $request)
    {
        $this->guard('parser.category_rules');

        $data = $request->validate([
            'min_confidence' => ['required', 'integer', 'min:0', 'max:100'],
            'keywords' => ['nullable', 'string'],
            'sku_prefixes' => ['nullable', 'string'],
            'group_mapping' => ['nullable', 'string'],
        ]);

        $settings = $this->settings->all();
        $settings['category_rules'] = [
            'min_confidence' => (int) $data['min_confidence'],
            'keywords' => $this->parseRuleLines($data['keywords'] ?? '', true),
            'sku_prefixes' => $this->parseRuleLines($data['sku_prefixes'] ?? ''),
            'group_mapping' => $this->parseRuleLines($data['group_mapping'] ?? ''),
        ];

        $this->settings->update($settings);

        return back()->with('success', __('ui.parser_rules_saved'));
    }

    public function selectImages(Request $request, ProductParserItem $item)
    {
        $this->guard('parser.approve');

        $data = $request->validate([
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['integer', 'exists:product_parser_image_assets,id'],
        ]);

        $ids = collect($data['images'] ?? [])->map(fn ($id) => (int) $id)->values();
        ProductParserImageAsset::where('parser_item_id', $item->id)->update(['is_selected' => false, 'is_main' => false]);

        foreach ($ids as $index => $id) {
            ProductParserImageAsset::where('parser_item_id', $item->id)->whereKey($id)->update([
                'is_selected' => true,
                'is_main' => $index === 0,
            ]);
        }

        $item->forceFill([
            'selected_images_json' => $item->imageAssets()->whereIn('id', $ids)->pluck('source_url')->values()->all(),
        ])->save();
        $item->batch?->addLog('Admin selected parser images', ['sku' => $item->sku, 'count' => $ids->count()]);

        return back()->with('success', __('ui.parser_images_selected'));
    }

    public function processImages(ProductParserItem $item)
    {
        $this->guard('parser.approve');

        ProcessProductImagesJob::dispatch($item->id);

        return redirect()->route('admin.parser.items.show', $item)->with('success', __('ui.parser_images_processed'));
    }

    public function createDraft(ProductParserItem $item, ProductDraftService $drafts)
    {
        $this->guard('parser.approve');

        try {
            $product = $drafts->createDraft($item->load(['imageAssets', 'category', 'batch']));
        } catch (RuntimeException $e) {
            return back()->withErrors(['draft' => $e->getMessage()]);
        }

        return redirect()->route('admin.products', ['q' => $product->sku])->with('success', __('ui.parser_draft_created'));
    }

    public function updateExisting(Request $request, ProductParserItem $item, ProductDraftService $drafts)
    {
        $this->guard('parser.approve');

        $data = $request->validate([
            'action' => ['required', Rule::in(['add_photos', 'replace_photos', 'update_description', 'update_price', 'update_stock', 'update_price_stock'])],
            'replace_confirmed' => ['nullable', 'boolean'],
        ]);

        if (in_array($data['action'], ['update_price', 'update_price_stock'], true) && ! $this->settings->get('update_existing_prices', false)) {
            return back()->withErrors(['update' => 'Updating existing prices is disabled in parser settings.']);
        }

        if (in_array($data['action'], ['update_stock', 'update_price_stock'], true) && ! $this->settings->get('update_existing_stock', false)) {
            return back()->withErrors(['update' => 'Updating existing stock is disabled in parser settings.']);
        }

        try {
            $product = $drafts->updateExisting($item->load(['imageAssets', 'existingProduct', 'batch']), $data['action'], $request->boolean('replace_confirmed'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['update' => $e->getMessage()]);
        }

        return redirect()->route('admin.products', ['q' => $product->sku])->with('success', __('ui.parser_existing_updated'));
    }

    public function updateItemCategory(Request $request, ProductParserItem $item)
    {
        $this->guard('parser.approve');

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $category = Category::findOrFail($data['category_id']);
        $item->forceFill([
            'category_id' => $category->id,
            'detected_category_id' => $item->detected_category_id ?: $category->id,
            'detected_category_path' => $item->detected_category_path ?: $category->display_name,
            'needs_category_review' => false,
            'status' => $item->existing_product_id ? 'existing_product_found' : 'ready_for_review',
            'category_confidence_score' => max((int) $item->category_confidence_score, 100),
            'category_detection_method' => 'admin',
            'category_detection_notes_json' => array_merge($item->category_detection_notes_json ?: [], ['admin selected '.$category->slug]),
        ])->save();
        $item->batch?->addLog('Admin changed parser category', ['sku' => $item->sku, 'category_id' => $category->id]);

        return back()->with('success', __('ui.parser_category_updated'));
    }

    public function publishDraft(ProductParserItem $item, ProductPublicationGuard $publicationGuard)
    {
        $this->guard('parser.approve');

        $product = $item->createdProduct;
        abort_unless($product, 404);

        $result = $publicationGuard->publish($product, true, self::PUBLICATION_APPROVAL_FLAGS);
        if (! $result['allowed']) {
            return back()
                ->with('warning', app()->isLocale('ru') ? 'Публикация заблокирована.' : 'Publicarea este blocata.')
                ->with('publication_errors', $result['errors']);
        }

        $item->forceFill([
            'status' => 'approved',
            'approval_status' => 'approved',
            'needs_translation_review' => false,
            'needs_content_review' => false,
            'needs_price_review' => false,
            'needs_stock_review' => false,
        ])->save();
        $item->batch?->addLog('Admin approved and published draft product', ['sku' => $item->sku, 'product_id' => $product->id]);

        return redirect()->route('admin.products', ['q' => $product->sku])->with('success', __('ui.parser_draft_published'));
    }

    public function bulkBatchAction(
        Request $request,
        ProductParserBatch $batch,
        ProductDraftService $drafts,
        ProductPublicationGuard $publicationGuard,
    ) {
        $this->guard('parser.approve');

        $data = $request->validate([
            'action' => ['required', Rule::in(['create_safe_drafts', 'publish_drafts', 'update_existing_stock', 'update_existing_price', 'update_existing_price_stock'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20000'],
        ]);

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $limit = (int) ($data['limit'] ?? 20000);
        $processed = 0;
        $failed = 0;

        if ($data['action'] === 'create_safe_drafts') {
            $query = $batch->items()
                ->whereNull('created_product_id')
                ->whereNull('existing_product_id')
                ->where('needs_category_review', false)
                ->whereIn('status', ['ready_for_review', 'dry_run_ready', 'parsed']);

            $query->orderBy('id')->limit($limit)->chunkById(200, function ($items) use ($drafts, &$processed, &$failed) {
                foreach ($items as $item) {
                    try {
                        $drafts->createDraft($item->load(['imageAssets', 'category', 'batch']));
                        $processed++;
                    } catch (RuntimeException) {
                        $failed++;
                    }
                }
            });

            $batch->addLog('Bulk created safe draft products', ['processed' => $processed, 'failed' => $failed]);

            return back()->with('success', $this->bulkMessage('Draft создано', $processed, $failed));
        }

        if ($data['action'] === 'publish_drafts') {
            $query = $batch->items()
                ->whereNotNull('created_product_id')
                ->where('needs_category_review', false)
                ->whereHas('createdProduct', fn ($product) => $product->where('status', 'draft'));

            $blockedReasons = [];
            $blockedMessages = [];
            $query->with('createdProduct')->orderBy('id')->limit($limit)->chunkById(200, function ($items) use ($publicationGuard, &$processed, &$failed, &$blockedReasons, &$blockedMessages) {
                foreach ($items as $item) {
                    $product = $item->createdProduct;
                    if (! $product) {
                        $failed++;

                        continue;
                    }

                    $result = $publicationGuard->publish($product, true, self::PUBLICATION_APPROVAL_FLAGS);
                    if (! $result['allowed']) {
                        $failed++;
                        foreach ($result['error_codes'] as $index => $code) {
                            $blockedReasons[$code] = ($blockedReasons[$code] ?? 0) + 1;
                            $blockedMessages[$code] ??= $result['errors'][$index] ?? $code;
                        }

                        continue;
                    }

                    $item->forceFill([
                        'status' => 'approved',
                        'approval_status' => 'approved',
                        'needs_translation_review' => false,
                        'needs_content_review' => false,
                        'needs_price_review' => false,
                        'needs_stock_review' => false,
                    ])->save();
                    $processed++;
                }
            });

            $batch->addLog('Bulk publication completed through guard', [
                'processed' => $processed,
                'blocked' => $failed,
                'blocked_reasons' => $blockedReasons,
            ]);

            $response = back()->with('success', "Опубликовано: {$processed}. Заблокировано: {$failed}.");
            if ($failed > 0) {
                arsort($blockedReasons);
                $publicationErrors = collect($blockedReasons)
                    ->map(fn (int $count, string $code) => "{$count} товаров: ".($blockedMessages[$code] ?? $code))
                    ->values()
                    ->all();
                $response->with('publication_errors', $publicationErrors);
            }

            return $response;
        }

        $action = match ($data['action']) {
            'update_existing_stock' => 'update_stock',
            'update_existing_price' => 'update_price',
            default => 'update_price_stock',
        };

        if (in_array($action, ['update_price', 'update_price_stock'], true) && ! $this->settings->get('update_existing_prices', false)) {
            return back()->withErrors(['bulk' => 'Updating existing prices is disabled in parser settings.']);
        }

        if (in_array($action, ['update_stock', 'update_price_stock'], true) && ! $this->settings->get('update_existing_stock', false)) {
            return back()->withErrors(['bulk' => 'Updating existing stock is disabled in parser settings.']);
        }

        $batch->items()
            ->whereNotNull('existing_product_id')
            ->whereIn('status', ['existing_product_found', 'ready_for_review'])
            ->with(['imageAssets', 'existingProduct', 'batch'])
            ->orderBy('id')
            ->limit($limit)
            ->chunkById(200, function ($items) use ($drafts, $action, &$processed, &$failed) {
                foreach ($items as $item) {
                    try {
                        $drafts->updateExisting($item, $action);
                        $processed++;
                    } catch (RuntimeException) {
                        $failed++;
                    }
                }
            });

        $batch->addLog('Bulk updated existing products', ['action' => $action, 'processed' => $processed, 'failed' => $failed]);

        return back()->with('success', $this->bulkMessage('Обновлено', $processed, $failed));
    }

    public function reject(ProductParserItem $item)
    {
        $this->guard('parser.approve');

        $item->forceFill(['status' => 'rejected'])->save();
        $item->batch?->addLog('Parser item rejected', ['sku' => $item->sku]);

        return back()->with('success', __('ui.parser_item_rejected'));
    }

    public function retry(ProductParserItem $item)
    {
        $this->guard('parser.run');

        $item->forceFill(['status' => 'queued', 'error_message' => null])->save();
        ParseSingleSkuJob::dispatch($item->id);

        return redirect()->route('admin.parser.items.show', $item)->with('success', __('ui.parser_retry_started'));
    }

    public function retryOfficial(ProductParserItem $item)
    {
        $this->guard('parser.run');

        $item->forceFill([
            'status' => 'queued',
            'error_message' => null,
            'official_source_url' => null,
            'official_source_domain' => null,
            'official_source_confidence' => null,
            'source_match_confidence' => null,
            'needs_source_review' => true,
        ])->save();
        FindOfficialProductSourceJob::dispatch($item->id);

        return back()->with('success', 'Official source search queued.');
    }

    public function retryOfficialImages(ProductParserItem $item)
    {
        $this->guard('parser.run');

        $item->forceFill(['status' => 'queued', 'error_message' => null, 'needs_image_review' => true])->save();
        FetchOfficialProductImagesJob::dispatch($item->id);

        return back()->with('success', 'Official image search queued.');
    }

    public function useFallback(ProductParserItem $item)
    {
        $this->guard('parser.run');

        $item->forceFill(['status' => 'queued', 'error_message' => null, 'needs_source_review' => true])->save();
        FetchTrisToolsFallbackDataJob::dispatch($item->id);

        return back()->with('success', 'TrisTools fallback search queued.');
    }

    public function rejectFallback(ProductParserItem $item)
    {
        $this->guard('parser.approve');

        $item->forceFill([
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'needs_source_review' => ! filled($item->official_source_url),
        ])->save();
        $item->sources()->where('source_type', 'fallback_reference')->delete();

        return back()->with('success', 'Fallback source rejected.');
    }

    public function approveQuality(ProductParserItem $item, string $type)
    {
        $this->guard('parser.approve');
        abort_unless(in_array($type, ['source', 'images', 'translation'], true), 404);

        $updates = match ($type) {
            'source' => ['needs_source_review' => false, 'source_reviewed_at' => now()],
            'images' => ['needs_image_review' => false, 'image_reviewed_at' => now()],
            'translation' => [
                'needs_translation_review' => false,
                'needs_content_review' => false,
                'translation_reviewed_at' => now(),
            ],
        };
        $item->forceFill($updates)->save();
        if ($type === 'images') {
            $item->imageAssets()->where('is_selected', true)->update(['needs_review' => false]);
        }

        if ($product = $item->createdProduct) {
            $productUpdates = match ($type) {
                'source' => ['needs_source_review' => false, 'source_reviewed_at' => now()],
                'images' => ['needs_image_review' => false],
                'translation' => [
                    'needs_translation_review' => false,
                    'needs_content_review' => false,
                ],
            };
            $product->forceFill($productUpdates)->save();
        }

        return back()->with('success', ucfirst($type).' review approved.');
    }

    public function cancelBatch(ProductParserBatch $batch)
    {
        $this->guard('parser.run');

        $batch->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();
        $batch->items()->whereIn('status', ['queued', 'searching'])->update(['status' => 'rejected']);
        $batch->addLog('Batch cancelled by admin');

        return back()->with('success', __('ui.parser_batch_cancelled'));
    }

    public function destroyBatch(ProductParserBatch $batch)
    {
        $this->guard('parser.delete');

        $batch->delete();

        return redirect()->route('admin.parser.index')->with('success', __('ui.parser_batch_deleted'));
    }

    public function updateSettings(Request $request)
    {
        $this->guard('parser.settings');

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'max_sku_per_batch' => ['required', 'integer', 'min:1', 'max:20000'],
            'max_file_size_kb' => ['required', 'integer', 'min:512', 'max:51200'],
            'max_images_per_product' => ['required', 'integer', 'min:1', 'max:4'],
            'min_confidence_score' => ['required', 'integer', 'min:0', 'max:100'],
            'image_size' => ['required', 'integer', 'min:600', 'max:2000'],
            'preview_size' => ['required', 'integer', 'min:300', 'max:1200'],
            'thumb_size' => ['required', 'integer', 'min:150', 'max:800'],
            'webp_quality' => ['required', 'integer', 'min:70', 'max:95'],
            'search_images' => ['nullable', 'boolean'],
            'translate_descriptions' => ['nullable', 'boolean'],
            'create_drafts_automatically' => ['nullable', 'boolean'],
            'update_existing_prices' => ['nullable', 'boolean'],
            'update_existing_stock' => ['nullable', 'boolean'],
            'official_sources_enabled' => ['nullable', 'boolean'],
            'tristools_fallback_enabled' => ['nullable', 'boolean'],
            'allow_marketplace_sources' => ['nullable', 'boolean'],
            'min_official_confidence' => ['required', 'integer', 'min:70', 'max:100'],
            'min_fallback_confidence' => ['required', 'integer', 'min:70', 'max:100'],
            'required_images_for_ready' => ['required', 'integer', 'min:1', 'max:4'],
            'allowed_domains' => ['nullable', 'string'],
            'blocked_domains' => ['nullable', 'string'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'watermark_file' => ['nullable', 'string', 'max:255'],
            'watermark_position' => ['required', Rule::in(['center', 'bottom_right', 'bottom_left'])],
            'watermark_opacity' => ['required', 'integer', 'min:8', 'max:20'],
            'watermark_size_percent' => ['required', 'integer', 'min:12', 'max:25'],
        ]);

        $this->settings->update([
            'enabled' => $request->boolean('enabled'),
            'max_sku_per_batch' => (int) $data['max_sku_per_batch'],
            'max_file_size_kb' => (int) $data['max_file_size_kb'],
            'max_images_per_product' => (int) $data['max_images_per_product'],
            'min_confidence_score' => (int) $data['min_confidence_score'],
            'image_size' => (int) $data['image_size'],
            'preview_size' => (int) $data['preview_size'],
            'thumb_size' => (int) $data['thumb_size'],
            'webp_quality' => (int) $data['webp_quality'],
            'search_images' => $request->boolean('search_images'),
            'translate_descriptions' => $request->boolean('translate_descriptions'),
            'create_drafts_automatically' => $request->boolean('create_drafts_automatically'),
            'update_existing_prices' => $request->boolean('update_existing_prices'),
            'update_existing_stock' => $request->boolean('update_existing_stock'),
            'official_sources_enabled' => $request->boolean('official_sources_enabled'),
            'tristools_fallback_enabled' => $request->boolean('tristools_fallback_enabled'),
            'tristools_fallback_only' => true,
            'allow_marketplace_sources' => $request->boolean('allow_marketplace_sources'),
            'min_official_confidence' => (int) $data['min_official_confidence'],
            'min_fallback_confidence' => (int) $data['min_fallback_confidence'],
            'required_images_for_ready' => (int) $data['required_images_for_ready'],
            'tristools' => array_replace($this->settings->get('tristools', []), [
                'enabled' => $request->boolean('tristools_fallback_enabled'),
            ]),
            'allowed_domains' => $this->lines($data['allowed_domains'] ?? ''),
            'blocked_domains' => $this->lines($data['blocked_domains'] ?? ''),
            'watermark' => [
                'enabled' => $request->boolean('watermark_enabled'),
                'file' => $data['watermark_file'] ?: '/images/brand/master-scule-logo.png',
                'position' => $data['watermark_position'],
                'opacity' => (int) $data['watermark_opacity'],
                'size_percent' => (int) $data['watermark_size_percent'],
            ],
        ]);

        return back()->with('success', __('ui.parser_settings_saved'));
    }

    private function guard(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->canUseParser($permission), 403);
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) $this->settings->get('enabled', true), 403);
    }

    private function validateParserUpload($file, array $allowedExtensions, array $mimeMap, string $field): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([$field => __('ui.parser_file_type_invalid')]);
        }

        $allowedMimes = $mimeMap[$extension] ?? [];
        $detectedMimes = array_filter([
            strtolower((string) $file->getMimeType()),
            strtolower((string) $file->getClientMimeType()),
        ]);

        if ($allowedMimes !== [] && ! array_intersect($detectedMimes, $allowedMimes)) {
            throw ValidationException::withMessages([$field => __('ui.parser_file_type_invalid')]);
        }

        if ($this->looksLikeExecutableUpload((string) $file->getRealPath())) {
            throw ValidationException::withMessages([$field => __('ui.parser_file_type_invalid')]);
        }

        return $extension;
    }

    private function looksLikeExecutableUpload(string $path): bool
    {
        $sample = strtolower((string) file_get_contents($path, false, null, 0, 4096));

        return str_contains($sample, '<?php')
            || str_contains($sample, '<?=')
            || (bool) preg_match('/^\s*(<script\b|#!.*\b(node|php)\b|import\s+|export\s+|const\s+|let\s+|var\s+|function\s+|console\.)/i', $sample);
    }

    private function priceListMimeMap(): array
    {
        return [
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];
    }

    private function bulkStats(ProductParserBatch $batch): array
    {
        return [
            'safe_new' => $batch->items()
                ->whereNull('created_product_id')
                ->whereNull('existing_product_id')
                ->where('needs_category_review', false)
                ->whereIn('status', ['ready_for_review', 'dry_run_ready', 'parsed'])
                ->count(),
            'drafts' => $batch->items()
                ->whereNotNull('created_product_id')
                ->where('needs_category_review', false)
                ->whereHas('createdProduct', fn ($product) => $product->where('status', 'draft'))
                ->count(),
            'existing' => $batch->items()
                ->whereNotNull('existing_product_id')
                ->whereIn('status', ['existing_product_found', 'ready_for_review'])
                ->count(),
            'exceptions' => $batch->items()
                ->where(function ($query) {
                    $query->where('needs_category_review', true)
                        ->orWhereIn('status', ['failed', 'not_found']);
                })
                ->count(),
        ];
    }

    private function bulkMessage(string $label, int $processed, int $failed): string
    {
        return $failed > 0
            ? "{$label}: {$processed}. Ошибок: {$failed}."
            : "{$label}: {$processed}.";
    }

    private function skuFileMimeMap(): array
    {
        return [
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'txt' => ['text/plain'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];
    }

    private function brandValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === 'auto' ? null : $value;
    }

    private function categoryValue(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Category::where('slug', $value)
            ->orWhere('name', $value)
            ->orWhere('name_ro', $value)
            ->value('id');
    }

    private function rowsFromText(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n|,|;/', (string) $text))
            ->map(fn ($sku) => ['sku' => trim($sku)])
            ->filter(fn ($row) => $row['sku'] !== '')
            ->values()
            ->all();
    }

    private function rowsFromFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        if ($extension === 'xlsx') {
            return $this->rowsFromXlsx($path);
        }

        $rows = [];
        $handle = fopen($path, 'rb');
        $headers = null;

        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            if (count($line) <= 1) {
                $line = str_getcsv(implode(',', $line), ';');
            }

            $line = array_map(fn ($value) => trim((string) $value), $line);

            if (! $headers && in_array('sku', array_map('strtolower', $line), true)) {
                $headers = array_map('strtolower', $line);

                continue;
            }

            $rows[] = $headers
                ? array_combine($headers, array_slice(array_pad($line, count($headers), null), 0, count($headers)))
                : ['sku' => $line[0] ?? null, 'brand' => $line[1] ?? null, 'category' => $line[2] ?? null];
        }

        fclose($handle);

        return $rows;
    }

    private function rowsFromXlsx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = $this->xlsxSharedStrings($zip);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $xml) {
            return [];
        }

        $sheet = simplexml_load_string($xml);
        $rows = [];
        $headers = null;

        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $value = (string) $cell->v;
                $values[] = $type === 's' ? ($shared[(int) $value] ?? '') : trim($value);
            }

            $values = array_map('trim', $values);
            if (! $headers && in_array('sku', array_map('strtolower', $values), true)) {
                $headers = array_map('strtolower', $values);

                continue;
            }

            $rows[] = $headers
                ? array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)))
                : ['sku' => $values[0] ?? null, 'brand' => $values[1] ?? null, 'category' => $values[2] ?? null];
        }

        return $rows;
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! $xml) {
            return [];
        }

        $strings = simplexml_load_string($xml);
        $values = [];

        foreach ($strings->si as $string) {
            $values[] = isset($string->t)
                ? (string) $string->t
                : collect($string->r ?? [])->map(fn ($run) => (string) $run->t)->implode('');
        }

        return $values;
    }

    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function parseRuleLines(string $value, bool $multiValue = false): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '' && (str_contains($line, '=>') || str_contains($line, '=')))
            ->mapWithKeys(function ($line) use ($multiValue) {
                $separator = str_contains($line, '=>') ? '=>' : '=';
                [$left, $right] = array_map('trim', explode($separator, $line, 2));

                return [$left => $multiValue ? $this->lines(str_replace(',', "\n", $right)) : $right];
            })
            ->all();
    }
}
