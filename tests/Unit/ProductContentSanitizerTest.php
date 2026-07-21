<?php

namespace Tests\Unit;

use App\Services\Catalog\ProductContentSanitizer;
use PHPUnit\Framework\TestCase;

class ProductContentSanitizerTest extends TestCase
{
    public function test_it_rejects_maximum_marketplace_copy(): void
    {
        $sanitizer = new ProductContentSanitizer;

        $this->assertSame('', $sanitizer->sanitize(
            'Купить по лучшей цене GYS 082809 в онлайн-магазине maximum.md. Заказ: +373(22)54-54-54',
        ));
        $this->assertTrue($sanitizer->containsMarketplacePromotion(
            'Cumpara la cel mai bun pret in magazinul online maxim.md',
        ));
    }

    public function test_it_keeps_a_real_product_description(): void
    {
        $sanitizer = new ProductContentSanitizer;
        $description = 'Сварочная маска GYS 082809 с регулируемым затемнением DIN 5–9 и 9–13.';

        $this->assertSame($description, $sanitizer->sanitize($description));
    }
}
