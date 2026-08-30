<?php
namespace App\Models\Purchase;

use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;
use App\Services\Document;
use App\Services\UnitConversionService;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionItem;
use App\Models\Inventory\InventoryTransactionType;

class PurchaseInvoice extends Document
{
    use SoftDeletes, BelongsToCompany, BranchScoped;

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

    public function receiveQuantities(array $received): void
    {
        if (!in_array($this->status, ['draft', 'partial'], true)) {
            throw new \DomainException('لا يمكن استلام فاتورة بهذه الحالة');
        }

        $stockItems = [];
        foreach ($this->items as $item) {
            $qty = (float) ($received[$item->item_id] ?? 0);
            $alreadyReceived = (float) ($item->received_qty ?? 0);
            $remaining = max(0, (float) $item->qty - $alreadyReceived);
            if ($qty < 0 || $qty > $remaining) {
                throw new \DomainException("الكمية المستلمة للصنف {$item->item_id} أكبر من المتبقي");
            }
            if ($qty > 0) {
                $stockItems[] = array_merge($item->toArray(), [
                    'qty' => $qty,
                    'base_quantity' => 0,
                    'conversion_factor' => 0,
                ]);
            }
        }

        if (!$stockItems) throw new \DomainException('أدخل كمية مستلمة واحدة على الأقل');
        $this->syncStock($stockItems, now());
        foreach ($this->items as $item) {
            $qty = (float) ($received[$item->item_id] ?? 0);
            if ($qty > 0) $item->increment('received_qty', $qty);
        }
        $this->refresh()->load('items');
        $complete = $this->items->every(fn ($item) =>
            (float) $item->received_qty >= (float) $item->qty);
        $this->update([
            'status' => $complete ? self::STATUS_POSTED : 'partial',
            'posted_at' => $complete ? now() : null,
        ]);
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

    // ─── Stock Sync ─────────────────────────────────────────

    private function syncStock(array $items, ?\Carbon\Carbon $transactionDate = null): void
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
            'transaction_date' => $transactionDate ?? $this->invoice_date,
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
