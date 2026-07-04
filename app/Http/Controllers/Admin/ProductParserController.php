<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ParseSingleSkuJob;
use App\Jobs\ParseSkuBatchJob;
use App\Jobs\ProcessProductImagesJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Services\ProductDraftService;
use App\Services\ProductParserSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use ZipArchive;

class ProductParserController extends Controller
{
    public function __construct(private ProductParserSettings $settings)
    {
    }

    public function index()
    {
        $this->guard('parser.view');

        return view('admin.parser.index', [
            'batches' => ProductParserBatch::with('user')->withCount('items')->latest()->paginate(10),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name_ro')->get(),
            'settings' => $this->settings->all(),
        ]);
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

        dispatch_sync(new ParseSingleSkuJob($item->id));

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

        $rows = collect($this->rowsFromText($data['sku_text'] ?? ''))
            ->merge($request->hasFile('sku_file') ? $this->rowsFromFile($request->file('sku_file')->getRealPath(), $request->file('sku_file')->getClientOriginalExtension()) : [])
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
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.parser.batch', compact('batch', 'items'));
    }

    public function showItem(ProductParserItem $item)
    {
        $this->guard('parser.view');

        return view('admin.parser.item', [
            'item' => $item->load(['batch', 'category', 'sources', 'imageAssets', 'existingProduct.brand', 'createdProduct']),
        ]);
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

        dispatch_sync(new ProcessProductImagesJob($item->id));

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
            'action' => ['required', Rule::in(['add_photos', 'replace_photos', 'update_description'])],
            'replace_confirmed' => ['nullable', 'boolean'],
        ]);

        try {
            $product = $drafts->updateExisting($item->load(['imageAssets', 'existingProduct', 'batch']), $data['action'], $request->boolean('replace_confirmed'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['update' => $e->getMessage()]);
        }

        return redirect()->route('admin.products', ['q' => $product->sku])->with('success', __('ui.parser_existing_updated'));
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
        dispatch_sync(new ParseSingleSkuJob($item->id));

        return redirect()->route('admin.parser.items.show', $item)->with('success', __('ui.parser_retry_started'));
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
            'max_sku_per_batch' => ['required', 'integer', 'min:1', 'max:500'],
            'max_images_per_product' => ['required', 'integer', 'min:1', 'max:4'],
            'min_confidence_score' => ['required', 'integer', 'min:0', 'max:100'],
            'image_size' => ['required', 'integer', 'min:600', 'max:2000'],
            'thumb_size' => ['required', 'integer', 'min:150', 'max:800'],
            'webp_quality' => ['required', 'integer', 'min:70', 'max:95'],
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
            'max_images_per_product' => (int) $data['max_images_per_product'],
            'min_confidence_score' => (int) $data['min_confidence_score'],
            'image_size' => (int) $data['image_size'],
            'thumb_size' => (int) $data['thumb_size'],
            'webp_quality' => (int) $data['webp_quality'],
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
        $zip = new ZipArchive();

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
}
