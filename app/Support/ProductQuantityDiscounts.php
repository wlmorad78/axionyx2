<?php

namespace App\Support;

use App\Models\Item;
use App\Models\ProductDiscountRule;

class ProductQuantityDiscounts
{
    /** فارق صغير يمنع رفض العتبة بسبب أخطاء العشرية. */
    private const EPSILON = 0.0000001;

    /**
     * تطبيق قواعد خصم الكميات (الحوافز) على بنود الفاتورة.
     *
     * نوعي القواعد (threshold_scope):
     *  - per_item      : الحد الأدنى يُفحص على كرتونات كل صنف على حدة،
     *                    والخصم يُطبَّق على كراتين ذلك الصنف فقط.
     *  - invoice_total : الحد الأدنى يُفحص على مجموع كراتين كل أصناف
     *                    الفاتورة، والخصم يُطبَّق على كل أصناف الفاتورة.
     *
     * عند تحقق النوعين معاً تفوز القاعدة الأعلى خصماً للفاتورة الواحدة
     * (لا جمع بين قاعدتين)، ثم يُقصّ الخصم بقيمة السطر.
     * الخصم الناتج يستبدل الخصم اليدوي للسطر ولا يُجمع معه.
     */
    public static function apply(int $companyId, array $lines): array
    {
        foreach ($lines as &$line) {
            if (empty($line['item_id']) && !empty($line['item_code'])) {
                $line['item_id'] = Item::where('company_id', $companyId)
                    ->where('code', $line['item_code'])
                    ->value('id');
            }
        }
        unset($line);

        $itemIds = collect($lines)
            ->pluck('item_id')
            ->filter()
            ->unique()
            ->values();
        if ($itemIds->isEmpty()) {
            return $lines;
        }

        $rules = ProductDiscountRule::query()
            ->with('items')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->get();

        if ($rules->isEmpty()) {
            return $lines;
        }

        $items = Item::with(['itemUnits.unit'])
            ->where('company_id', $companyId)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        // ── 1) كرتونات كل سطر، وكرتونات كل صنف، وإجمالي كراتين الفاتورة ──
        $lineCartons = [];
        $cartonsByItem = [];
        $totalCartons = 0.0;
        foreach ($lines as $index => $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $item = $items->get($itemId);
            if (!$item) {
                continue;
            }

            $cartonFactor = self::cartonFactor($item);
            if ($cartonFactor <= 0) {
                continue;
            }
            $unitId = $line['unit_id'] ?? $item->sales_unit_id ?? $item->base_unit_id;
            $lineFactor = self::unitFactor($item, $unitId);
            if ($lineFactor <= 0) {
                continue;
            }

            $cartons = self::cartonQuantity(
                (float) ($line['qty'] ?? $line['quantity'] ?? 0),
                $lineFactor,
                $cartonFactor,
            );
            $lineCartons[$index] = $cartons;
            $cartonsByItem[$itemId] = ($cartonsByItem[$itemId] ?? 0) + $cartons;
            $totalCartons += $cartons;
        }

        // ── 2) أفضل قاعدة لكل صنف + قاعدة الإجمالي إن تحقّقت عتبتها ──────
        $selectedRuleByItem = [];
        $invoiceTotalRule = null;
        foreach ($rules as $rule) {
            $minimum = (float) $rule->minimum_quantity;
            $rate = (float) $rule->discount_per_carton;
            if ($rate <= 0) {
                continue;
            }

            if ($rule->usesInvoiceTotalScope()) {
                if ($totalCartons + self::EPSILON < $minimum) {
                    continue;
                }
                if (!self::isBetter($invoiceTotalRule, $minimum, $rate)) {
                    continue;
                }
                $invoiceTotalRule = $rule;
                continue;
            }

            foreach ($rule->items as $ruleItem) {
                $itemId = (int) $ruleItem->id;
                if (($cartonsByItem[$itemId] ?? 0) + self::EPSILON < $minimum) {
                    continue;
                }
                if (!self::isBetter($selectedRuleByItem[$itemId] ?? null, $minimum, $rate)) {
                    continue;
                }
                $selectedRuleByItem[$itemId] = $rule;
            }
        }

        if (!$invoiceTotalRule && empty($selectedRuleByItem)) {
            return $lines;
        }

        // ── 3) خصم كل سطر ──────────────────────────────────────────────
        foreach ($lines as $index => &$line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $itemRule = $selectedRuleByItem[$itemId] ?? null;
            if (!$invoiceTotalRule && !$itemRule) {
                continue;
            }

            $cartons = $lineCartons[$index] ?? 0.0;
            if ($cartons <= 0) {
                continue;
            }

            $quantity = (float) ($line['qty'] ?? $line['quantity'] ?? 0);
            $gross = (float) ($line['gross_amount'] ?? ($quantity * (float) ($line['price'] ?? $line['unit_price'] ?? 0)));

            $invoicePromotion = $invoiceTotalRule
                ? round($cartons * (float) $invoiceTotalRule->discount_per_carton, 2)
                : 0.0;
            $itemPromotion = $itemRule
                ? round($cartons * (float) $itemRule->discount_per_carton, 2)
                : 0.0;

            $promotion = max($invoicePromotion, $itemPromotion);
            $discount = round(min(max($gross, 0), $promotion), 2);
            if ($discount <= 0) {
                continue;
            }

            $appliedRule = $itemPromotion >= $invoicePromotion ? $itemRule : $invoiceTotalRule;

            $line['discount_type'] = InvoiceDiscounts::TYPE_FIXED;
            $line['discount_value'] = $discount;
            $line['discount_amount'] = $discount;
            $line['discount_reason'] = trim(($line['discount_reason'] ?? '') . ' خصم كمية: ' . $appliedRule->name);
        }
        unset($line);

        return $lines;
    }

    public static function cartonQuantity(float $quantity, float $unitFactor, float $cartonFactor): float
    {
        if ($quantity <= 0 || $unitFactor <= 0 || $cartonFactor <= 0) return 0.0;
        return $quantity * $unitFactor / $cartonFactor;
    }

    /**
     * هل القاعدة المرشّحة أفضل من القاعدة المختارة حالياً؟
     * الترتيب: الأعلى حدّاً أولاً، ثم الأعلى خصماً (لا تُجمع قاعدتان).
     */
    private static function isBetter(?ProductDiscountRule $current, float $minimum, float $rate): bool
    {
        if (!$current) {
            return true;
        }

        $currentMinimum = (float) $current->minimum_quantity;
        $currentRate = (float) $current->discount_per_carton;

        return $minimum > $currentMinimum
            || ($minimum === $currentMinimum && $rate > $currentRate);
    }

    private static function cartonFactor(Item $item): float
    {
        foreach ($item->itemUnits as $itemUnit) {
            $name = mb_strtolower((string) ($itemUnit->unit?->name_ar . ' ' . $itemUnit->unit?->name_en . ' ' . $itemUnit->unit?->code));
            if (str_contains($name, 'كرتون') || str_contains($name, 'carton') || str_contains($name, 'ctn')) {
                return (float) $itemUnit->conversion_factor;
            }
        }
        return 0.0;
    }

    private static function unitFactor(Item $item, $unitId): float
    {
        if (!$unitId || (int) $unitId === (int) $item->base_unit_id) return 1.0;
        $itemUnit = $item->itemUnits->firstWhere('unit_id', (int) $unitId);
        return $itemUnit ? (float) $itemUnit->conversion_factor : 0.0;
    }
}
