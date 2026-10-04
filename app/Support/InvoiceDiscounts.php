<?php
/**
 * =====================================================================
 * مساعد (Helper): InvoiceDiscounts
 * ---------------------------------------------------------------------
 * الوصف:
 * دوال مشتركة لحساب الخصومات على مستوى سطر الصنف (Sales Invoice Item)
 * ومستوى الفاتورة (Sales Invoice Discount) وتوحيد الناتج بين كل مسارات
 * الإنشاء: API العام، الهاند هيلد، والمزامنة (sync).
 * =====================================================================
 */
namespace App\Support;

class InvoiceDiscounts
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    /** أنواع الخصم المسموح بها. */
    public static function types(): array
    {
        return [self::TYPE_PERCENTAGE, self::TYPE_FIXED];
    }

    /** تطبيع نوع الخصم؛ أي قيمة غير معروفة تُعامل كمبلغ ثابت. */
    public static function type($type): string
    {
        return in_array($type, self::types(), true) ? $type : self::TYPE_FIXED;
    }

    /**
     * حساب مبلغ الخصم من (النوع + القيمة) على أساس معيّن، محصوراً بحد الأساس.
     */
    public static function amount(float $base, $type, $value): float
    {
        $value = (float) $value;

        if ($base <= 0 || $value <= 0) {
            return 0.0;
        }

        $amount = self::type($type) === self::TYPE_PERCENTAGE
            ? $base * $value / 100
            : $value;

        return round(min($amount, $base), 2);
    }

    /**
     * حساب خصم السطر من بياناته.
     *
     * يدعم:
     *  - discount_amount صريح (يُحترم مباشرة مع الحد الأقصى بقيمة السطر).
     *  - discount_type + discount_value (يُحسب منها المبلغ).
     *
     * @return array{0: ?string, 1: float, 2: float} [discount_type, discount_value, discount_amount]
     */
    public static function line(array $item, float $gross): array
    {
        $type = isset($item['discount_type']) && $item['discount_type'] !== null
            ? self::type($item['discount_type'])
            : null;

        $value = round((float) ($item['discount_value'] ?? 0), 4);

        $explicit = isset($item['discount_amount']) && $item['discount_amount'] !== null
            ? round((float) $item['discount_amount'], 2)
            : null;

        if ($explicit !== null && $explicit > 0) {
            $amount = round(min($explicit, max($gross, 0)), 2);

            if ($value <= 0) {
                $type = $type ?? self::TYPE_FIXED;
                $value = $type === self::TYPE_PERCENTAGE && $gross > 0
                    ? round($amount / $gross * 100, 4)
                    : $amount;
            }

            return [$type, $value, $amount];
        }

        if ($type === null || $value <= 0) {
            return [null, 0.0, 0.0];
        }

        return [$type, $value, self::amount($gross, $type, $value)];
    }

    /**
     * تطبيع صفوف خصم الفاتورة وحساب مجموعها.
     *
     * الأساس للنسبة المئوية هو (المجموع قبل الخصم - خصومات الأصناف)،
     * ويُمنع تجاوز مجموع الخصومات لهذا الأساس.
     *
     * @param  array $rows صفوف الخصم القادمة من الـ payload.
     * @param  float $base الأساس الذي تُخصم منه خصومات الفاتورة.
     * @return array{0: array, 1: float} [الصفوف المطبّمة, إجمالي الخصم]
     */
    public static function rows(array $rows, float $base): array
    {
        $normalized = [];
        $total = 0.0;
        $remaining = max(0.0, round($base, 2));

        foreach ($rows as $row) {
            if (!is_array($row) || $remaining <= 0) {
                continue;
            }

            $type = self::type($row['discount_type'] ?? self::TYPE_FIXED);
            $value = round((float) ($row['discount_value'] ?? 0), 4);

            $explicit = isset($row['discount_amount']) && $row['discount_amount'] !== null
                ? round((float) $row['discount_amount'], 2)
                : null;

            if ($explicit !== null && $explicit > 0) {
                $amount = round(min($explicit, $remaining), 2);
                if ($value <= 0) {
                    $value = $type === self::TYPE_PERCENTAGE && $base > 0
                        ? round($amount / $base * 100, 4)
                        : $amount;
                }
            } else {
                $amount = self::amount($remaining, $type, $value);
            }

            if ($amount <= 0) {
                continue;
            }

            $normalized[] = [
                'discount_type' => $type,
                'discount_value' => $value,
                'discount_amount' => $amount,
                'reason' => $row['reason'] ?? null,
            ];

            $total += $amount;
            $remaining = round($remaining - $amount, 2);
        }

        return [$normalized, round($total, 2)];
    }
}
