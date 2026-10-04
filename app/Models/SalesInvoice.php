<?php
namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Services\Document;
use App\Services\UnitConversionService;
use App\Services\CostingService;
use App\Support\InvoiceDiscounts;

class SalesInvoice extends Document
{
    use SoftDeletes, BelongsToCompany;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'treasury_id', 'load_request_id', 'issue_order_id',
        'route_id', 'sales_territory_id', 'sales_rep_id', 'customer_id', 'customer_type_id', 'payment_term_id', 'currency_id',
        'exchange_rate', 'uuid', 'client_uuid', 'invoice_no', 'temp_invoice_no', 'source', 'mode', 'device_id',
        'sync_status', 'synced_at', 'number_series_id',
        'invoice_date', 'invoice_time',
        'subtotal', 'item_discount_total', 'invoice_discount_total', 'tax_total',
        'incentive_total', 'net_total', 'paid_amount', 'remaining_amount',
        'status', 'notes', 'created_by', 'approved_by', 'posted_at', 'deleted_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'item_discount_total' => 'decimal:2',
        'invoice_discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'incentive_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'invoice_time' => 'datetime:H:i',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
    public function route() { return $this->belongsTo(Route::class); }
    public function salesTerritory() { return $this->belongsTo(SalesTerritory::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id', 'user_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function items() { return $this->hasMany(SalesInvoiceItem::class); }
    public function discounts() { return $this->hasMany(SalesInvoiceDiscount::class); }
    public function taxes() { return $this->hasMany(SalesInvoiceTax::class); }
    public function invoiceIncentives() { return $this->hasMany(SalesInvoiceIncentive::class); }
    public function device() { return $this->belongsTo(Device::class, 'device_id', 'id'); }

    // ─── Discounts & Totals ─────────────────────────────────

    /**
     * استخراج صفوف خصم الفاتورة من الـ payload المُرسَل من أي عميل
     * (API عام / هاند هيلد / مزامنة). يدعم الشكلين:
     *  - صفوف متعددة: discounts = [{discount_type, discount_value, reason}, ...]
     *  - خصم واحد: invoice_discount_type + invoice_discount_value
     *  - إجمالي صريح: invoice_discount_total
     */
    public static function discountRowsFromPayload(?array $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $rows = $payload['discounts'] ?? $payload['invoice_discounts'] ?? null;
        if (is_array($rows) && count($rows) > 0) {
            return array_values(array_filter($rows, 'is_array'));
        }

        $type = $payload['invoice_discount_type'] ?? null;
        $value = (float) ($payload['invoice_discount_value'] ?? 0);
        $amount = (float) ($payload['invoice_discount_total'] ?? 0);
        $reason = $payload['discount_reason'] ?? null;

        if ($type !== null && $value > 0) {
            return [[
                'discount_type' => InvoiceDiscounts::type($type),
                'discount_value' => $value,
                'reason' => $reason,
            ]];
        }

        if ($amount > 0) {
            return [[
                'discount_type' => InvoiceDiscounts::TYPE_FIXED,
                'discount_value' => $amount,
                'discount_amount' => $amount,
                'reason' => $reason,
            ]];
        }

        return [];
    }

    /**
     * حساب إجمالي خصم مستوى الفاتورة مبكراً (قبل إنشاء الفاتورة) حتى تُحسب
     *_paid_amount و remaining_amount على الإجمالي الصحيح.
     */
    public static function invoiceDiscountTotalFromPayload(?array $payload, float $base): float
    {
        [, $total] = InvoiceDiscounts::rows(
            static::discountRowsFromPayload($payload),
            $base
        );

        return $total;
    }

    /**
     * هل يحمل الـ payload بيانات خصم على مستوى الفاتورة؟
     * يُستخدم لتمييز التحديثات الجزئية (التي لا تذكر الخصم) عن الحذف الصريح.
     */
    public static function hasDiscountPayload(?array $payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        foreach (['discounts', 'invoice_discounts', 'invoice_discount_type', 'invoice_discount_value', 'invoice_discount_total'] as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        return false;
    }

    /**
     * استبدال صفوف خصم الفاتورة بالصفوف المستخرجة من الـ payload
     * ثم إعادة حساب إجماليات الفاتورة.
     */
    public function applyDiscounts(?array $payload): void
    {
        if (!is_array($payload)) {
            return;
        }

        $rows = static::discountRowsFromPayload($payload);

        $this->discounts()->delete();

        if (count($rows) > 0) {
            $base = $this->discountBase();
            [$normalized] = InvoiceDiscounts::rows($rows, $base);

            foreach ($normalized as $row) {
                $this->discounts()->create($row);
            }
        }

        $this->recalculateTotals();
    }

    /**
     * الأساس الذي تُخصم منه خصومات الفاتورة = المجموع - خصومات الأصناف.
     */
    public function discountBase(): float
    {
        $subtotal = (float) $this->items()->sum('gross_amount');
        $itemDiscount = (float) $this->items()->sum('discount_amount');

        if ($subtotal <= 0) {
            $subtotal = (float) $this->subtotal;
            $itemDiscount = (float) $this->item_discount_total;
        }

        return round(max(0, $subtotal - $itemDiscount), 2);
    }

    /**
     * إعادة حساب إجماليات الفاتورة من مصادرها الحقيقية:
     * أصناف الفاتورة + صفوف الخصم.
     *
     *   net_total = subtotal - item_discount_total - invoice_discount_total + tax_total
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        if ($items->isNotEmpty()) {
            $subtotal = round((float) $items->sum('gross_amount'), 2);
            $itemDiscountTotal = round((float) $items->sum('discount_amount'), 2);
            $taxTotal = round((float) $items->sum('tax_amount'), 2);
        } else {
            // فاتورة بدون أصناف (ترويسة فقط): نحافظ على الإجماليات المُدخلة يدوياً.
            $subtotal = round((float) $this->subtotal, 2);
            $itemDiscountTotal = round((float) $this->item_discount_total, 2);
            $taxTotal = round((float) $this->tax_total, 2);
        }

        $base = round(max(0, $subtotal - $itemDiscountTotal), 2);
        $invoiceDiscountTotal = round((float) $this->discounts()->sum('discount_amount'), 2);
        $invoiceDiscountTotal = round(min($invoiceDiscountTotal, $base), 2);

        $netTotal = round($subtotal - $itemDiscountTotal - $invoiceDiscountTotal + $taxTotal, 2);
        $paidAmount = round((float) ($this->paid_amount ?? 0), 2);
        $remainingAmount = round(max(0, $netTotal - $paidAmount), 2);

        $this->update([
            'subtotal' => $subtotal,
            'item_discount_total' => $itemDiscountTotal,
            'invoice_discount_total' => $invoiceDiscountTotal,
            'tax_total' => $taxTotal,
            'net_total' => $netTotal,
            'remaining_amount' => $remainingAmount,
        ]);
    }


    // ─── Document Implementation ────────────────────────────

    protected function documentType(): string
    {
        return 'sales_invoice';
    }

    protected function numberField(): string
    {
        return 'invoice_no';
    }

    protected function validateBusinessRules(): void
    {
        if ((float)($this->net_total ?? 0) <= 0) {
            throw new \DomainException('صافي الفاتورة يجب أن يكون أكبر من صفر');
        }
        if (!$this->customer_id) {
            throw new \DomainException('يجب اختيار العميل');
        }
    }

    protected function onApprove(): void
    {
        // No side effects on approve for MVP
    }

    protected function onPost(): void
    {
        // لا نقوم بتحديث الخزينة هنا لأن العميل يدفع للمندوب مباشرة
        // الخزينة تتFFECT فقط عندما يدفع المندوب لأمين الخزنة (عند تسوية المديونية)

        // Sync stock (inventory deduction)
        $items = $this->items()->get()->toArray();
        $this->syncStock($items);
    }

    protected function onCancel(): void
    {
        // Reverse stock
        $this->reverseStock();
    }

    protected function onReopen(): void
    {
        // Re-apply stock (re-post)
        $items = $this->items()->get()->toArray();
        $this->syncStock($items);
    }

    // ─── Stock Sync ─────────────────────────────────────────

    private function syncStock(array $items): void
    {
        if (empty($items) || !$this->warehouse_id) return;

        // تخطي مزامنة المخزون لمبيعات الهاند هيلد (mobile)
        if (($this->source ?? '') === 'mobile') {
            Log::info('Skipping syncStock for mobile/handheld invoice', ['invoice_id' => $this->id]);
            return;
        }

        $type = InventoryTransactionType::firstWhere('code', 'SALES_INVOICE') ?? null;
        if (!$type) {
            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'SALES_INVOICE'],
                ['name' => 'Sales Invoice', 'effect' => 'subtraction', 'is_active' => true]
            );
        }

        $existing = InventoryTransaction::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $this->id)->first();
        if ($existing) {
            Log::info('Skipping duplicate InventoryTransaction for SalesInvoice', ['invoice_id' => $this->id, 'txn_id' => $existing->id]);
            return;
        }

        Log::info('SalesInvoice syncStock start', ['invoice_id' => $this->id, 'invoice_no' => $this->invoice_no, 'items_count' => count($items), 'warehouse_id' => $this->warehouse_id]);

        $txn = InventoryTransaction::create([
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'transaction_type_id' => $type->id,
            'warehouse_id' => $this->warehouse_id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($this->company_id),
            'transaction_date' => $this->invoice_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => SalesInvoice::class,
            'reference_id' => $this->id,
            'notes' => "فاتورة مبيعات رقم {$this->invoice_no}",
            'status' => 'posted',
            'created_by' => $this->created_by,
        ]);

        $unitService = app(UnitConversionService::class);
        $costingService = app(CostingService::class);

        foreach ($items as $item) {
            if (empty($item['item_id'])) continue;

            $itemId = $item['item_id'];
            $enteredQty = (float) ($item['qty'] ?? 0);

            $savedCf = (float) ($item['conversion_factor'] ?? 0);
            $savedBaseQty = (float) ($item['base_quantity'] ?? 0);

            if ($savedCf > 0 && $savedBaseQty > 0) {
                $conversionFactor = $savedCf;
                $qtyInBase = $savedBaseQty;
                $unitId = $item['unit_id'] ?? null;
            } else {
                $enteredUnitId = $item['unit_id'] ?? null;
                $resolved = $unitService->resolveUnit($itemId, $enteredUnitId);
                $unitId = $resolved?->unit_id ?? $enteredUnitId;
                $conversionFactor = $resolved?->conversion_factor ?? 1;
                $qtyInBase = $unitService->toBase($itemId, $unitId, $enteredQty);
            }

            // حساب تكلفة المبيعات باستخدام Moving Average
            $cost = $costingService->calculateSaleCost(
                $itemId,
                $qtyInBase,
                $this->warehouse_id,
                $this->invoice_date
            );

            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id' => $itemId,
                'unit_id' => $unitId,
                'conversion_factor' => $conversionFactor,
                'qty' => -$qtyInBase,
                'unit_cost' => $cost['unit_cost'],
                'total_cost' => $cost['total_cost'],
            ]);

            // حفظ التكلفة على سطر الفاتورة لإ reported الأرباح
            $salesInvoiceItemId = $item['id'] ?? null;
            if ($salesInvoiceItemId) {
                $totalCost = round((float) $cost['total_cost'], 4);
                DB::table('sales_invoice_items')
                    ->where('id', $salesInvoiceItemId)
                    ->update([
                        'unit_cost' => $cost['unit_cost'],
                        'total_cost' => $totalCost,
                        'profit' => DB::raw('ROUND(net_amount - ' . (float) $totalCost . ', 2)'),
                    ]);
            }
        }
    }

    public function reverseStock(): void
    {
        $txns = InventoryTransaction::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $this->id)
            ->get();

        foreach ($txns as $txn) {
            $txn->items()->delete();
            $txn->forceDelete();
        }
    }

    // ─── Treasury Sync ──────────────────────────────────────

    private function syncTreasury(): void
    {
        $paid = (float)($this->paid_amount ?? 0);
        if ($paid <= 0) return;

        $treasuryId = $this->treasury_id;
        if (!$treasuryId) {
            $mainTreasury = Treasury::where('company_id', $this->company_id)
                ->where('is_main', true)->where('is_active', true)->first();
            if (!$mainTreasury) return;
            $treasuryId = $mainTreasury->id;
            $this->update(['treasury_id' => $treasuryId]);
        }

        // Prevent duplicate treasury transactions for same reference
        $existing = TreasuryTransaction::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $this->id)->first();
        if ($existing) {
            Log::info('Skipping duplicate TreasuryTransaction for SalesInvoice', ['invoice_id' => $this->id, 'txn_id' => $existing->id]);
            return;
        }

        Log::info('SalesInvoice syncTreasury creating transaction', ['invoice_id' => $this->id, 'amount' => $paid, 'treasury_id' => $treasuryId]);

        TreasuryTransaction::create([
            'company_id' => $this->company_id,
            'treasury_id' => $treasuryId,
            'type' => 'credit',
            'amount' => $paid,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $this->id,
            'description' => "تحصيل فاتورة مبيعات رقم {$this->invoice_no}",
            'transaction_date' => $this->invoice_date,
            'created_by' => $this->created_by,
        ]);
    }

    public function reverseTreasury(): void
    {
        $txns = TreasuryTransaction::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $this->id)
            ->get();

        foreach ($txns as $txn) {
            $txn->forceDelete();
        }
    }


}
