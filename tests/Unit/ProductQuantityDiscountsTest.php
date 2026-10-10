<?php

namespace Tests\Unit;

use App\Support\ProductQuantityDiscounts;
use PHPUnit\Framework\TestCase;

class ProductQuantityDiscountsTest extends TestCase
{
    public function test_converts_base_units_to_cartons(): void
    {
        $this->assertSame(5.0, ProductQuantityDiscounts::cartonQuantity(2500, 1, 500));
    }

    public function test_converts_carton_sales_units_to_cartons(): void
    {
        $this->assertSame(5.0, ProductQuantityDiscounts::cartonQuantity(5, 500, 500));
    }

    public function test_returns_zero_for_invalid_conversion_factors(): void
    {
        $this->assertSame(0.0, ProductQuantityDiscounts::cartonQuantity(5, 1, 0));
    }
}