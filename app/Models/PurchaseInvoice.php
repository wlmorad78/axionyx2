<?php
namespace App\Models;

use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Services\Document;
use App\Services\UnitConversionService;

class PurchaseInvoice extends Document
{
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;

    protected $table = 'purchase_invoices';

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'invoice_no', 'supplier_id',
        'purchase_receipt_id', 'invoice_date', 'due_date', 'purchase_order_no', 'invoice_type',
        'subtotal', 'discount_total', 'tax_total', 'net_total',
        'paid_amount', 'remaining_amount', 'notes', 'status', 'created_by', 'posted_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseReceipt() { return $this->belongsTo(PurchaseReceipt::class); }
    public function createdByEmployee() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseInvoiceItem::class); }

    // ─── Document Implementation ────────────────────────────

    protected function documentType(): string
    {
        return 'purchase_invoice';
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
        if (!$this->supplier_id) {
            throw new \DomainException('يجب اختيار المورد');
        }
    }

    protected function onApprove(): void {}

    protected function onPost(): void
    {
        $items = $this->items()->get()->toArray();
        $this->syncStock($items);
    }

    protected function onCancel(): void
    {
        Log::info('PurchaseInvoice onCancel called', ['id' => $this->id, 'invoice_no' => $this->invoice_no]);
        $this->reverseStock();
    }

    

    protected function onReopen(): void
    {
        $items = $this->items()->get()->toArray();
        $this->syncStock($items);
    }

    public function cancel(): static
    {
        if ($this->status !== 'partial') {
            return parent::cancel();
        }

        $this->onCancel();
        $this->update(['status' => static::STATUS_CANCELLED]);

        return $this;
    }

    // ─── Stock Sync ─────────────────────────────────────────

    private function syncStock(array $items): void
    {
        if (empty($items) || !$this->warehouse_id) return;

        $type = InventoryTransactionType::firstWhere('code', 'PURCHASE_RECEIPT') ?? null;
        if (!$type) {
            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'PURCHASE_RECEIPT'],
                ['name' => 'Purchase Receipt', 'effect' => 'addition', 'is_active' => true]
            );
        }

        Log::info('PurchaseInvoice syncStock start', ['invoice_id' => $this->id, 'invoice_no' => $this->invoice_no, 'items_count' => count($items), 'warehouse_id' => $this->warehouse_id]);

        $txn = InventoryTransaction::create([
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'transaction_type_id' => $type->id,
            'warehouse_id' => $this->warehouse_id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($this->company_id),
            'transaction_date' => $this->invoice_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $this->id,
            'notes' => "فاتورة مشتريات رقم {$this->invoice_no}",
            'status' => 'posted',
            'created_by' => $this->created_by,
        ]);

        Log::info('InventoryTransaction created for PurchaseInvoice', ['txn_id' => $txn->id, 'invoice_id' => $this->id]);

        $unitService = app(UnitConversionService::class);

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

            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id' => $itemId,
                'unit_id' => $unitId,
                'conversion_factor' => $conversionFactor,
                'qty' => $qtyInBase,
                'unit_cost' => $item['price'] ?? 0,
                'total_cost' => $enteredQty * ($item['price'] ?? 0),
                'from_location_type' => 'supplier',
                'from_location_id'   => $this->supplier_id,
                'to_location_type'   => 'warehouse',
                'to_location_id'     => $this->warehouse_id,
            ]);
            Log::info('InventoryTransactionItem created', ['txn_id' => $txn->id, 'item_id' => $itemId, 'qty' => $qtyInBase]);
        }
    }

    public function reverseStock(): void
    {
        $txns = InventoryTransaction::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $this->id)->get();
        Log::info('reverseStock called', ['invoice_id' => $this->id, 'txns' => $txns->pluck('id')]);
        foreach ($txns as $txn) {
            $txn->items()->delete();
            $txn->forceDelete();
            Log::info('InventoryTransaction removed in reverseStock', ['txn_id' => $txn->id, 'invoice_id' => $this->id]);
        }
    }

    /**
     * مجموع كمية المخزون المسجّلة على الفاتورة لكل صنف قبل ربط حركاتها.
     * تُستخدم عند التعديل حتى لا يضيع مخزون فاتورة لا تملك استلامًا مسجّلًا.
     *
     * @return array<int, float>
     */
    public function stockQtyByItem(): array
    {
        $txns = InventoryTransaction::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($txns->isEmpty()) {
            return [];
        }

        return InventoryTransactionItem::whereIn('inventory_transaction_id', $txns)
            ->get()
            ->groupBy('item_id')
            ->mapWithKeys(fn ($rows, $itemId) => [(int) $itemId => (float) $rows->sum('qty')])
            ->all();
    }

    /**
     * إعادة بناء حركات المخزون للفاتورة بعد تعديلها من الكميات المستلمة.
     * يجب استدعاؤها بعد reverseStock() وداخل نفس المعاملة.
     *
     * @param array<int, float> $previousStock مخزون كل صنف قبل التعديل، يُستخدم حين لا يوجد استلام مسجّل
     */
    public function rebuildStockFromReceived(array $previousStock = []): void
    {
        $this->load('items');

        if ($this->isCancelled() || !$this->warehouse_id || $this->items->isEmpty()) {
            return;
        }

        $type = InventoryTransactionType::firstWhere('code', 'PURCHASE_RECEIPT') ?? null;
        if (!$type) {
            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'PURCHASE_RECEIPT'],
                ['name' => 'Purchase Receipt', 'effect' => 'addition', 'is_active' => true]
            );
        }

        $unitService = app(UnitConversionService::class);
        $lines = [];

        foreach ($this->items as $item) {
            $received = (float) $item->received_qty;
            $enteredUnitId = $item->unit_id ?? null;

            if ($received > 0) {
                $resolved = $unitService->resolveUnit(
                    (int) $item->item_id,
                    $enteredUnitId !== null ? (int) $enteredUnitId : null
                );
                $unitId = $resolved?->unit_id ?? $enteredUnitId;
                $conversionFactor = $resolved?->conversion_factor ?? 1;
                $qtyInBase = $unitId !== null
                    ? $unitService->toBase((int) $item->item_id, (int) $unitId, $received)
                    : $received;
            } else {
                // فاتورة بلا استلام مسجّل: نحافظ على المخزون القائم بدل حذفه
                $unitId = $enteredUnitId;
                $conversionFactor = (float) ($item->conversion_factor ?: 1);
                $qtyInBase = (float) ($previousStock[$item->item_id] ?? 0);
            }

            if ($qtyInBase <= 0) {
                continue;
            }

            $lines[] = [
                'item_id'          => $item->item_id,
                'unit_id'          => $unitId,
                'conversion_factor' => $conversionFactor,
                'qty'              => $qtyInBase,
                'unit_cost'        => (float) $item->price,
            ];
        }

        if (empty($lines)) {
            return;
        }

        Log::info('PurchaseInvoice rebuildStockFromReceived', [
            'invoice_id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'lines'      => count($lines),
        ]);

        $txn = InventoryTransaction::create([
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'transaction_type_id' => $type->id,
            'warehouse_id' => $this->warehouse_id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($this->company_id),
            'transaction_date' => $this->invoice_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $this->id,
            'notes' => "فاتورة مشتريات رقم {$this->invoice_no}",
            'status' => 'posted',
            'created_by' => $this->created_by,
        ]);

        foreach ($lines as $line) {
            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id' => $line['item_id'],
                'unit_id' => $line['unit_id'],
                'conversion_factor' => $line['conversion_factor'],
                'qty' => $line['qty'],
                'unit_cost' => $line['unit_cost'],
                'total_cost' => round($line['qty'] * $line['unit_cost'], 2),
                'from_location_type' => 'supplier',
                'from_location_id'   => $this->supplier_id,
                'to_location_type'   => 'warehouse',
                'to_location_id'     => $this->warehouse_id,
            ]);
        }
    }

    /**
     * إعادة حساب حالة الفاتورة من الكميات المستلمة بعد تعديلها.
     * فاتورة بلا استلام مسجّل تُبقي حالتها الحالية حتى لا تُخفَّض بلا سبب.
     */
    public function refreshStatusFromItems(): void
    {
        if ($this->isCancelled()) {
            return;
        }

        $this->load('items');

        if ($this->items->isEmpty()) {
            return;
        }

        $allReceived = $this->items->every(
            fn ($item) => (float) $item->qty > 0 && (float) $item->received_qty >= (float) $item->qty
        );
        $anyReceived = $this->items->contains(fn ($item) => (float) $item->received_qty > 0);

        $status = $allReceived ? 'posted' : ($anyReceived ? 'partial' : null);

        if ($status !== null && $this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    // ─── Receive Quantities ────────────────────────────────

    public function receiveQuantities(array $received): void
    {
        if ($this->isCancelled()) {
            throw new \DomainException('لا يمكن استلام فاتورة مشتريات ملغاة');
        }

        $this->load('items');

        foreach ($this->items as $item) {
            $qty = (float) ($received[$item->item_id] ?? 0);
            if ($qty > 0) {
                $item->increment('received_qty', $qty);
                $this->addItemToStock($item, $qty);
            }
        }

        $this->refresh()->load('items');

        $allFullyReceived = $this->items->every(fn ($item) => (float) $item->received_qty >= (float) $item->qty);
        $anyReceived = $this->items->contains(fn ($item) => (float) $item->received_qty > 0);

        if ($allFullyReceived) {
            $this->update(['status' => 'posted']);
        } elseif ($anyReceived) {
            $this->update(['status' => 'partial']);
        }
    }

    private function addItemToStock(PurchaseInvoiceItem $item, float $receivedQty): void
    {
        if (!$this->warehouse_id || $receivedQty <= 0) return;

        $type = InventoryTransactionType::firstWhere('code', 'PURCHASE_RECEIPT') ?? null;
        if (!$type) {
            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'PURCHASE_RECEIPT'],
                ['name' => 'Purchase Receipt', 'effect' => 'addition', 'is_active' => true]
            );
        }

        $txn = InventoryTransaction::create([
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'transaction_type_id' => $type->id,
            'warehouse_id' => $this->warehouse_id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($this->company_id),
            'transaction_date' => $this->invoice_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $this->id,
            'notes' => "استلام جزئي - فاتورة مشتريات رقم {$this->invoice_no}",
            'status' => 'posted',
            'created_by' => $this->created_by,
        ]);

        $unitService = app(UnitConversionService::class);
        $enteredUnitId = $item->unit_id ?? null;
        $resolved = $unitService->resolveUnit($item->item_id, $enteredUnitId);
        $unitId = $resolved?->unit_id ?? $enteredUnitId;
        $conversionFactor = $resolved?->conversion_factor ?? 1;
        $qtyInBase = $unitService->toBase($item->item_id, $unitId, $receivedQty);

        InventoryTransactionItem::create([
            'inventory_transaction_id' => $txn->id,
            'item_id' => $item->item_id,
            'unit_id' => $unitId,
            'conversion_factor' => $conversionFactor,
            'qty' => $qtyInBase,
            'unit_cost' => $item->price ?? 0,
            'total_cost' => $receivedQty * ($item->price ?? 0),
            'from_location_type' => 'supplier',
            'from_location_id'   => $this->supplier_id,
            'to_location_type'   => 'warehouse',
            'to_location_id'     => $this->warehouse_id,
        ]);
    }

    // ─── Auto-generate Invoice Number ───────────────────────

    protected static function booted(): void
    {
        static::creating(function (PurchaseInvoice $model) {
            if (!$model->invoice_no) {
                $model->invoice_no = $model->generateNumber();
            }
        });
    }
}
