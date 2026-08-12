<?php
namespace App\Models\Sales;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;
use App\Services\Document;
use App\Services\UnitConversionService;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\CRM\Customer;
use App\Models\Settings\Device;
use App\Models\HR\Employee;
use App\Models\Inventory\IssueOrder;
use App\Models\Treasury\Treasury;
use App\Models\Inventory\Warehouse;

class SalesInvoice extends Document
{
    use SoftDeletes, BelongsToCompany, BranchScoped;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'treasury_id', 'load_request_id', 'issue_order_id',
        'route_id', 'sales_rep_id', 'customer_id', 'payment_term_id', 'currency_id',
        'exchange_rate', 'invoice_no', 'temp_invoice_no', 'source', 'mode', 'device_id',
        'sync_status', 'synced_at', 'number_series_id',
        'invoice_date', 'invoice_time',
        'subtotal', 'item_discount_total', 'invoice_discount_total', 'tax_total',
        'incentive_total', 'net_total', 'paid_amount', 'remaining_amount',
        'status', 'notes', 'created_by', 'approved_by', 'posted_at',
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
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function items() { return $this->hasMany(SalesInvoiceItem::class); }
    public function discounts() { return $this->hasMany(SalesInvoiceDiscount::class); }
    public function taxes() { return $this->hasMany(SalesInvoiceTax::class); }
    public function invoiceIncentives() { return $this->hasMany(SalesInvoiceIncentive::class); }
    public function device() { return $this->belongsTo(Device::class, 'device_id', 'id'); }

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

        if ($this->source === 'mobile') return;

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
                'qty' => -$qtyInBase,
                'unit_cost' => $item['price'] ?? 0,
                'total_cost' => $enteredQty * ($item['price'] ?? 0),
            ]);
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

    // ─── Auto-generate Invoice Number ───────────────────────

    public function generateNumber(): string
    {
        $companyId = $this->getAttribute('company_id');
        $documentType = $this->documentType();

        $branchId = $this->getAttribute('branch_id') ?? null;
        $branchCode = null;

        if ($branchId && method_exists($this, 'branch')) {
            $branch = $this->branch;
            $branchCode = $branch?->code ?? null;
        }

        $salesRepCode = null;
        if ($this->sales_rep_id) {
            $employee = \App\Models\HR\Employee::find($this->sales_rep_id);
            $salesRepCode = $employee?->employee_code ?? null;
        }

        return \App\Models\NumberSeries::nextNumber(
            companyId: (int) $companyId,
            documentType: $documentType,
            branchId: $branchId !== null ? (int) $branchId : null,
            branchCode: $salesRepCode ?? $branchCode,
        );
    }

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $model) {
            if (!$model->invoice_no) {
                $model->invoice_no = $model->generateNumber();
            }
        });
    }
}
