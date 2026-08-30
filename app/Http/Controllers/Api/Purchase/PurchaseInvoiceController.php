<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseInvoiceController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Invoice
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Invoice" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\TreasuryTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Invoice) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with([
            'supplier',
            'purchaseReceipt',
            'createdByEmployee',
            'items.item',
            'items.unit',
        ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->boolean('incomplete')) {
            $query->whereHas('items', function ($items) {
                $items->whereColumn('received_qty', '<', 'qty');
            });
        }
        if ($request->filled('search')) {
            $query->where('invoice_no', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereRaw('DATE(invoice_date) >= ?', [$request->date_from]);
        }
        if ($request->filled('date_to')) {
            $query->whereRaw('DATE(invoice_date) <= ?', [$request->date_to]);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Invoice) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice', 'store'));
        $items = $request->input('items', []);

        $invoice = DB::transaction(function () use ($validated, $items) {
            $invoice = PurchaseInvoice::create($validated);

            if (!empty($items)) {
                $unitService = app(\App\Services\UnitConversionService::class);

                foreach ($items as $item) {
                    $itemId = $item['item_id'] ?? null;
                    $enteredUnitId = $item['unit_id'] ?? null;
                    $enteredQty = (float) ($item['qty'] ?? 0);

                    $resolved = $unitService->resolveUnit($itemId, $enteredUnitId);
                    $unitId = $resolved?->unit_id ?? $enteredUnitId;
                    $conversionFactor = $resolved?->conversion_factor ?? 1;
                    $qtyInBase = $unitService->toBase($itemId, $unitId, $enteredQty);

                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'item_id' => $itemId,
                        'unit_id' => $unitId,
                        'qty' => $item['qty'] ?? 0,
                        'conversion_factor' => $conversionFactor,
                        'base_quantity' => $qtyInBase,
                        'price' => $item['price'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'net_amount' => $item['net_amount'] ?? 0,
                    ]);
                }
            }

            return $invoice;
        });

        $invoice->load(['items.item', 'items.unit']);

        return response()->json($invoice, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Invoice) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier', 'purchaseReceipt', 'items.item', 'items.unit', 'createdByEmployee']);

        return response()->json($purchaseInvoice);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Invoice) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice', 'update', $purchaseInvoice));
        $items = $request->input('items');

        DB::transaction(function () use ($purchaseInvoice, $validated, $items) {
            if ($purchaseInvoice->isPosted()) {
                $purchaseInvoice->reverseStock();
            }

            $purchaseInvoice->update($validated);

            if (is_array($items)) {
                $purchaseInvoice->items()->delete();
                $unitService = app(\App\Services\UnitConversionService::class);

                foreach ($items as $item) {
                    $itemId = $item['item_id'] ?? null;
                    $enteredUnitId = $item['unit_id'] ?? null;
                    $enteredQty = (float) ($item['qty'] ?? 0);

                    $resolved = $unitService->resolveUnit($itemId, $enteredUnitId);
                    $unitId = $resolved?->unit_id ?? $enteredUnitId;
                    $conversionFactor = $resolved?->conversion_factor ?? 1;
                    $qtyInBase = $unitService->toBase($itemId, $unitId, $enteredQty);

                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $purchaseInvoice->id,
                        'item_id' => $itemId,
                        'unit_id' => $unitId,
                        'qty' => $item['qty'] ?? 0,
                        'conversion_factor' => $conversionFactor,
                        'base_quantity' => $qtyInBase,
                        'price' => $item['price'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'net_amount' => $item['net_amount'] ?? 0,
                    ]);
                }

                self::syncStock($purchaseInvoice, $items);
            }
        });

        $purchaseInvoice->load(['items.item', 'items.unit']);

        return response()->json($purchaseInvoice);
    }

    /**
     * حذف سجل من (Purchase Invoice) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        DB::transaction(function () use ($purchaseInvoice) {
            if ($purchaseInvoice->isPosted()) {
                $purchaseInvoice->cancel();
            }
            $purchaseInvoice->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Invoice) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseInvoice::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($model) {
            $model->restore();
            if ($model->isPosted()) {
                $model->post();
            }
        });

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Invoice) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseInvoice::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * دالة معالجة: post — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Purchase Invoice).
     */
    public function post(PurchaseInvoice $purchaseInvoice)
    {
        try {
            DB::transaction(function () use ($purchaseInvoice) {
                if ($purchaseInvoice->status === 'draft') {
                    $purchaseInvoice->update(['status' => 'posted', 'posted_at' => now()]);
                    return;
                }

                if ($purchaseInvoice->status === 'partial') {
                    throw new \DomainException(
                        'الفاتورة مستلمة جزئيًا. استخدم استلام الكمية المتبقية.'
                    );
                }

                $purchaseInvoice->post();
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($purchaseInvoice->fresh());
    }

    public function receive(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($purchaseInvoice, $data) {
                $received = collect($data['items'])->mapWithKeys(fn ($item) => [
                    (int) $item['item_id'] => (float) $item['qty'],
                ])->all();
                $purchaseInvoice->load('items');
                $purchaseInvoice->receiveQuantities($received);
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($purchaseInvoice->fresh()->load('items.item', 'items.unit'));
    }

    public function receipts(PurchaseInvoice $purchaseInvoice)
    {
        $transactions = InventoryTransaction::with('items.item')
            ->where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $purchaseInvoice->id)
            ->where('status', 'posted')
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return response()->json($transactions);
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Purchase Invoice).
     */
    public function cancel(PurchaseInvoice $purchaseInvoice)
    {
        try {
            DB::transaction(fn() => $purchaseInvoice->cancel());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($purchaseInvoice->fresh());
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Invoice).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_invoice', 'store');
    }

    /**
     * دالة معالجة: syncStock — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Purchase Invoice).
     */
    private static function syncStock(PurchaseInvoice $invoice, array $items): void
    {
        if (empty($items)) return;
        if (!$invoice->warehouse_id) return;

        $type = InventoryTransactionType::firstWhere('code', 'PURCHASE_RECEIPT') ?? null;
        if (!$type) {
            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'PURCHASE_RECEIPT'],
                ['name' => 'Purchase Receipt', 'effect' => 'addition', 'is_active' => true]
            );
        }

        $txn = InventoryTransaction::create([
            'company_id' => $invoice->company_id,
            'branch_id' => $invoice->branch_id,
            'transaction_type_id' => $type->id,
            'warehouse_id' => $invoice->warehouse_id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($invoice->company_id),
            'transaction_date' => $invoice->invoice_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => "ÙØ§ØªÙˆØ±Ø© Ù…Ø´ØªØ±ÙŠØ§Øª Ø±Ù‚Ù… {$invoice->invoice_no}",
            'status' => 'posted',
            'created_by' => $invoice->created_by,
        ]);

        $unitService = app(\App\Services\UnitConversionService::class);

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
            ]);
        }
    }
}
