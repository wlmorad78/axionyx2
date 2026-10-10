<?php

namespace App\Support;

use App\Models\Item;
use App\Models\ProductDiscountRule;

class ProductQuantityDiscounts
{
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
            ->with(['items.itemUnits.unit'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->whereHas('items', fn ($query) => $query->whereIn('items.id', $itemIds))
            ->get();

        if ($rules->isEmpty()) {
            return $lines;
        }

        $items = Item::with(['itemUnits.unit'])
            ->where('company_id', $companyId)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $cartonsByItem = [];
        foreach ($lines as $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $item = $items->get($itemId);
            if (!$item) continue;

            $cartonFactor = self::cartonFactor($item);
            if ($cartonFactor <= 0) continue;
            $unitId = $line['unit_id'] ?? $item->sales_unit_id ?? $item->base_unit_id;
            $lineFactor = self::unitFactor($item, $unitId);
            if ($lineFactor <= 0) continue;

            $cartonsByItem[$itemId] = ($cartonsByItem[$itemId] ?? 0)
                + self::cartonQuantity(
                    (float) ($line['qty'] ?? $line['quantity'] ?? 0),
                    $lineFactor,
                    $cartonFactor,
                );
        }

        $selectedRuleByItem = [];
        foreach ($rules as $rule) {
            foreach ($rule->items as $item) {
                $itemId = (int) $item->id;
                if (($cartonsByItem[$itemId] ?? 0) + 0.0000001 < (float) $rule->minimum_quantity) {
                    continue;
                }

                $current = $selectedRuleByItem[$itemId] ?? null;
                if (!$current
                    || (float) $rule->minimum_quantity > (float) $current->minimum_quantity
                    || ((float) $rule->minimum_quantity === (float) $current->minimum_quantity
                        && (float) $rule->discount_per_carton > (float) $current->discount_per_carton)) {
                    $selectedRuleByItem[$itemId] = $rule;
                }
            }
        }

        foreach ($lines as &$line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $rule = $selectedRuleByItem[$itemId] ?? null;
            $item = $items->get($itemId);
            if (!$rule || !$item) continue;

            $cartonFactor = self::cartonFactor($item);
            $unitId = $line['unit_id'] ?? $item->sales_unit_id ?? $item->base_unit_id;
            $lineFactor = self::unitFactor($item, $unitId);
            if ($cartonFactor <= 0 || $lineFactor <= 0) continue;

            $quantity = (float) ($line['qty'] ?? $line['quantity'] ?? 0);
            $cartons = self::cartonQuantity($quantity, $lineFactor, $cartonFactor);
            $gross = (float) ($line['gross_amount'] ?? ($quantity * (float) ($line['price'] ?? $line['unit_price'] ?? 0)));
            $promotionDiscount = round($cartons * (float) $rule->discount_per_carton, 2);
            // الاستبدال لا الجمع: خصم القاعدة يحل محل الخصم اليدوي للسطر
            // (متفق عليه مع الجهاز حتى يخرج الطرفان بنفس الرقم).
            $discount = round(min(max($gross, 0), $promotionDiscount), 2);
            $line['discount_type'] = InvoiceDiscounts::TYPE_FIXED;
            $line['discount_value'] = $discount;
            $line['discount_amount'] = $discount;
            $line['discount_reason'] = trim(($line['discount_reason'] ?? '') . ' خصم كمية: ' . $rule->name);
        }
        unset($line);

        return $lines;
    }

    public static function cartonQuantity(float $quantity, float $unitFactor, float $cartonFactor): float
    {
        if ($quantity <= 0 || $unitFactor <= 0 || $cartonFactor <= 0) return 0.0;
        return $quantity * $unitFactor / $cartonFactor;
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