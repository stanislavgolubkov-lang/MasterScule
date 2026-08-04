<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use XMLWriter;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $addUrl = static function (string $location, ?string $lastModified = null, ?string $priority = null) use ($xml): void {
            $xml->startElement('url');
            $xml->writeElement('loc', $location);
            if ($lastModified) {
                $xml->writeElement('lastmod', $lastModified);
            }
            if ($priority) {
                $xml->writeElement('priority', $priority);
            }
            $xml->endElement();
        };

        $addUrl(route('home'), null, '1.0');
        $addUrl(route('catalog'), null, '0.9');
        $addUrl(route('brands'), null, '0.8');
        $addUrl(route('promotions'), null, '0.7');
        $addUrl(route('new'), null, '0.7');

        foreach (['about', 'delivery-payment', 'warranty', 'returns', 'contacts', 'privacy-policy', 'terms', 'cookie-policy'] as $slug) {
            $addUrl(route('page', $slug), null, '0.5');
        }

        Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(fn (Category $category) => $addUrl(
                route('catalog', $category->slug),
                $category->updated_at?->toDateString(),
                '0.7',
            ));

        Brand::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(fn (Brand $brand) => $addUrl(
                route('brand.show', $brand->slug),
                $brand->updated_at?->toDateString(),
                '0.7',
            ));

        Product::query()
            ->availableForSale()
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(fn (Product $product) => $addUrl(
                route('product.show', $product->slug),
                $product->updated_at?->toDateString(),
                '0.8',
            ));

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
