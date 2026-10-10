<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\ProductDiscountRule;
use App\Models\Unit;
use App\Support\ProductQuantityDiscounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات منطق قواعد الخصم بالكراتين كما يستخدمه الجهاز (الهاند هيلد).
 *
 * يجب أن يطابق مجموع الخصوم الناتج ما يحسبه تطبيق الجهاز تماماً:
 * كرتونات السطر = الكمية × عامل الوحدة ÷ عامل الكرتونة، والعتبة على
 * مستوى الصنف، وأفضل قاعدة واحدة فقط، والاستبدال لا الجمع.
 */
class ProductQuantityDiscountsApplyTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Unit $boxUnit;
    private Unit $cartonUnit;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'code' => 'PQD' . uniqid(),
            'name_ar' => 'شركة قواعد الخصم',
            'is_active' => true,
        ]);

        $this->boxUnit = Unit::create([
            'company_id' => $this->company->id,
            'code' => 'BOX' . uniqid(),
            'name_ar' => 'علبة',
            'is_active' => true,
        ]);

        $this->cartonUnit = Unit::create([
            'company_id' => $this->company->id,
            'code' => 'CTN' . uniqid(),
            'name_ar' => 'كرتونة',
            'is_active' => true,
        ]);

        $this->item = Item::create([
            'company_id' => $this->company->id,
            'code' => 'ITM' . uniqid(),
            'name_ar' => 'صنف',
            'is_active' => true,
            'base_unit_id' => $this->boxUnit->id,
            'sales_unit_id' => $this->boxUnit->id,
        ]);

        ItemUnit::create([
            'item_id' => $this->item->id,
            'unit_id' => $this->boxUnit->id,
            'conversion_factor' => 1,
            'is_default' => true,
            'is_sales_unit' => true,
        ]);

        ItemUnit::create([
            'item_id' => $this->item->id,
            'unit_id' => $this->cartonUnit->id,
            'conversion_factor' => 12,
        ]);
    }

    private function makeRule(array $overrides = []): ProductDiscountRule
    {
        $rule = ProductDiscountRule::create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'قاعدة خصم',
            'minimum_quantity' => 2,
            'discount_per_carton' => 10,
            'is_active' => true,
        ], $overrides));

        $rule->items()->sync([$this->item->id]);

        return $rule;
    }

    private function makeItem(): Item
    {
        $item = Item::create([
            'company_id' => $this->company->id,
            'code' => 'ITM' . uniqid(),
            'name_ar' => 'صنف آخر',
            'is_active' => true,
            'base_unit_id' => $this->boxUnit->id,
            'sales_unit_id' => $this->boxUnit->id,
        ]);

        ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $this->boxUnit->id,
            'conversion_factor' => 1,
            'is_default' => true,
            'is_sales_unit' => true,
        ]);

        ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $this->cartonUnit->id,
            'conversion_factor' => 12,
        ]);

        return $item;
    }

    private function makeLine(array $overrides = []): array
    {
        return array_merge([
            'item_id' => $this->item->id,
            'qty' => 24,
            'price' => 5,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
        ], $overrides);
    }

    private function apply(array $lines): array
    {
        return ProductQuantityDiscounts::apply($this->company->id, $lines);
    }

    public function test_quantity_in_base_units_is_converted_to_cartons(): void
    {
        $this->makeRule(['minimum_quantity' => 2, 'discount_per_carton' => 10]);

        $lines = $this->apply([$this->makeLine(['qty' => 24])]);

        // 24 علبة ÷ 12 = كرتونتان × 10 = 20
        $this->assertSame(20.0, (float) $lines[0]['discount_amount']);
    }

    public function test_quantity_sold_by_carton_counts_one_carton_per_unit(): void
    {
        $this->item->update(['sales_unit_id' => $this->cartonUnit->id]);
        $this->makeRule(['minimum_quantity' => 2, 'discount_per_carton' => 10]);

        $lines = $this->apply([$this->makeLine(['qty' => 3, 'price' => 50])]);

        // 3 كرتونة × 12 ÷ 12 = 3 كراتين × 10 = 30 (وقيمة السطر 150 لا تحدّ)
        $this->assertSame(30.0, (float) $lines[0]['discount_amount']);
    }

    public function test_below_threshold_leaves_the_line_untouched(): void
    {
        $this->makeRule(['minimum_quantity' => 2, 'discount_per_carton' => 10]);

        $lines = $this->apply([$this->makeLine(['qty' => 12])]);

        // كرتونة واحدة فقط < 2
        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
    }

    public function test_replaces_manual_discount_instead_of_adding_to_it(): void
    {
        $this->makeRule(['minimum_quantity' => 2, 'discount_per_carton' => 10]);

        $lines = $this->apply([
            $this->makeLine([
                'qty' => 24,
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'discount_amount' => 100,
            ]),
        ]);

        // الاستبدال: 20 فقط وليس 100 + 20
        $this->assertSame(20.0, (float) $lines[0]['discount_amount']);
    }

    public function test_only_the_best_rule_applies_per_item(): void
    {
        $this->makeRule([
            'name' => 'العتبة الدنيا',
            'minimum_quantity' => 2,
            'discount_per_carton' => 10,
        ]);
        $this->makeRule([
            'name' => 'العتبة العليا',
            'minimum_quantity' => 5,
            'discount_per_carton' => 4,
        ]);

        $lines = $this->apply([$this->makeLine(['qty' => 60])]);

        // 5 كراتين × 4 = 20 (القاعدة الأعلى عتبة تفوز وحدها)
        $this->assertSame(20.0, (float) $lines[0]['discount_amount']);
    }

    public function test_threshold_is_checked_per_item_not_across_the_invoice(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule(['minimum_quantity' => 3, 'discount_per_carton' => 10]);
        $rule->items()->sync([$this->item->id, $other->id]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 24]), // 2 كرتونة < 3
            ['item_id' => $other->id, 'qty' => 36, 'price' => 5, 'discount_amount' => 0], // 3 كراتين
        ]);

        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(30.0, (float) $lines[1]['discount_amount']);
    }

    public function test_rules_outside_the_active_window_are_ignored(): void
    {
        $this->makeRule([
            'minimum_quantity' => 2,
            'discount_per_carton' => 10,
            'starts_at' => now()->addDays(7)->toDateString(),
        ]);

        $lines = $this->apply([$this->makeLine(['qty' => 24])]);

        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
    }

    public function test_rules_scoped_to_other_items_do_not_apply(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule(['minimum_quantity' => 2, 'discount_per_carton' => 10]);
        $rule->items()->sync([$other->id]);

        $lines = $this->apply([$this->makeLine(['qty' => 24])]);

        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
    }

    public function test_discount_never_exceeds_the_line_value(): void
    {
        $this->makeRule(['minimum_quantity' => 1, 'discount_per_carton' => 100]);

        $lines = $this->apply([$this->makeLine(['qty' => 24, 'price' => 0.5])]);

        // 2 كرتونة × 100 = 200 لكن قيمة السطر 12
        $this->assertSame(12.0, (float) $lines[0]['discount_amount']);
    }

    // ── نطاق "إجمالي الفاتورة" ────────────────────────────────────────

    public function test_invoice_total_scope_checks_the_sum_of_all_items(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule([
            'minimum_quantity' => 4,
            'discount_per_carton' => 10,
            'threshold_scope' => 'invoice_total',
        ]);
        $rule->items()->sync([$this->item->id, $other->id]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 12]), // كرتونة واحدة
            ['item_id' => $other->id, 'qty' => 36, 'price' => 5, 'discount_amount' => 0], // 3 كراتين
        ]);

        // المجموع 4 كراتين يحقق العتبة، والخصم يشمل السطرين معاً.
        $this->assertSame(10.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(30.0, (float) $lines[1]['discount_amount']);
    }

    public function test_same_quantities_fail_under_per_item_scope(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule([
            'minimum_quantity' => 4,
            'discount_per_carton' => 10,
        ]);
        $rule->items()->sync([$this->item->id, $other->id]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 12]), // كرتونة واحدة < 4
            ['item_id' => $other->id, 'qty' => 36, 'price' => 5, 'discount_amount' => 0], // 3 كراتين < 4
        ]);

        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(0.0, (float) $lines[1]['discount_amount']);
    }

    public function test_invoice_total_scope_applies_to_every_item_in_the_invoice(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule([
            'minimum_quantity' => 2,
            'discount_per_carton' => 10,
            'threshold_scope' => 'invoice_total',
        ]);
        // النطاق مربوط بصنف واحد فقط، لكن الخصم يشمل كل أصناف الفاتورة.
        $rule->items()->sync([$this->item->id]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 24]), // 2 كرتونة → 20
            ['item_id' => $other->id, 'qty' => 12, 'price' => 5, 'discount_amount' => 0], // كرتونة → 10
        ]);

        $this->assertSame(20.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(10.0, (float) $lines[1]['discount_amount']);
    }

    public function test_invoice_total_scope_below_threshold_applies_nothing(): void
    {
        $other = $this->makeItem();
        $rule = $this->makeRule([
            'minimum_quantity' => 10,
            'discount_per_carton' => 10,
            'threshold_scope' => 'invoice_total',
        ]);
        $rule->items()->sync([$this->item->id, $other->id]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 24]), // 2 كرتونة
            ['item_id' => $other->id, 'qty' => 12, 'price' => 5, 'discount_amount' => 0], // كرتونة
        ]);

        $this->assertSame(0.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(0.0, (float) $lines[1]['discount_amount']);
    }

    public function test_invoice_total_and_per_item_rules_do_not_stack(): void
    {
        $other = $this->makeItem();
        $this->makeRule([
            'name' => 'إجمالي الفاتورة',
            'minimum_quantity' => 1,
            'discount_per_carton' => 10,
            'threshold_scope' => 'invoice_total',
        ]);
        $this->makeRule([
            'name' => 'لكل صنف',
            'minimum_quantity' => 2,
            'discount_per_carton' => 50,
        ]);

        $lines = $this->apply([
            $this->makeLine(['qty' => 24]), // 2 كرتونة
            ['item_id' => $other->id, 'qty' => 12, 'price' => 5, 'discount_amount' => 0], // كرتونة
        ]);

        // للصنف المغطى بالقاعدة اليدوية يفوز الأعلى (100 بدل 20)،
        // وللصنف الآخر يفوز خصم الإجمالي (10).
        $this->assertSame(100.0, (float) $lines[0]['discount_amount']);
        $this->assertSame(10.0, (float) $lines[1]['discount_amount']);
    }
}
