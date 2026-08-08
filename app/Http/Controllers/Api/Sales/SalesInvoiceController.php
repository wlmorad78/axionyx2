<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionItem;
use App\Models\Inventory\InventoryTransactionType;
use App\Models\Treasury\TreasuryTransaction;
use Illuminate\Support\Facades\Log;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'warehouse', 'createdBy']);

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->filled('date_from')) $query->where('invoice_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->where('invoice_date', '<=', $request->date_to);
        if ($request->trashed) $query->onlyTrashed();

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('sales_invoice', 'store'));
        $items = $request->input('items', []);

        $invoice = DB::transaction(function () use ($validated, $items) {
            $validated['net_total'] = ($validated['subtotal'] ?? 0)
                - ($validated['item_discount_total'] ?? 0)
                - ($validated['invoice_discount_total'] ?? 0)
                + ($validated['tax_total'] ?? 0);
            $validated['remaining_amount'] = ($validated['net_total'] ?? 0) - ($validated['paid_amount'] ?? 0);

            $invoice = SalesInvoice::create($validated);

            if (!empty($items)) {
                foreach ($items as $item) {
                    $grossAmount = ($item['qty'] ?? 0) * ($item['price'] ?? 0);
                    $discountAmount = $item['discount_amount'] ?? 0;
                    $taxAmount = $item['tax_amount'] ?? 0;
                    $netAmount = $grossAmount - $discountAmount + $taxAmount;

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'item_id' => $item['item_id'] ?? null,
                        'unit_id' => $item['unit_id'] ?? null,
                        'warehouse_id' => $item['warehouse_id'] ?? $invoice->warehouse_id,
                        'qty' => $item['qty'] ?? 0,
                        'bonus_qty' => $item['bonus_qty'] ?? 0,
                        'price' => $item['price'] ?? 0,
                        'gross_amount' => $grossAmount,
                        'discount_amount' => $discountAmount,
                        'tax_percent' => $item['tax_rate'] ?? 0,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }
            // Note: stock and treasury side-effects are applied when the document is posted
            // via the SalesInvoice::post() lifecycle which calls onPost().

            return $invoice;
        });

        $invoice->load(['items.item', 'items.unit']);

        return response()->json($invoice, 201);
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['customer', 'warehouse', 'items.item', 'items.unit', 'createdBy']);

        return response()->json($salesInvoice);
    }

    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate(ValidationRules::for('sales_invoice', 'update', $salesInvoice));
        $items = $request->input('items');

        DB::transaction(function () use ($salesInvoice, $validated, $items) {
            // Reverse old effects if posted
            if ($salesInvoice->isPosted()) {
                $salesInvoice->reverseStock();
            }

            $validated['net_total'] = ($validated['subtotal'] ?? $salesInvoice->subtotal)
                - ($validated['item_discount_total'] ?? $salesInvoice->item_discount_total)
                - ($validated['invoice_discount_total'] ?? $salesInvoice->invoice_discount_total)
                + ($validated['tax_total'] ?? $salesInvoice->tax_total);
            $validated['remaining_amount'] = ($validated['net_total'] ?? 0) - ($validated['paid_amount'] ?? $salesInvoice->paid_amount);

            $salesInvoice->update($validated);

            if (is_array($items)) {
                $salesInvoice->items()->delete();
                foreach ($items as $item) {
                    $grossAmount = ($item['qty'] ?? 0) * ($item['price'] ?? 0);
                    $discountAmount = $item['discount_amount'] ?? 0;
                    $taxAmount = $item['tax_amount'] ?? 0;
                    $netAmount = $grossAmount - $discountAmount + $taxAmount;

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $salesInvoice->id,
                        'item_id' => $item['item_id'] ?? null,
                        'unit_id' => $item['unit_id'] ?? null,
                        'warehouse_id' => $item['warehouse_id'] ?? $salesInvoice->warehouse_id,
                        'qty' => $item['qty'] ?? 0,
                        'bonus_qty' => $item['bonus_qty'] ?? 0,
                        'price' => $item['price'] ?? 0,
                        'gross_amount' => $grossAmount,
                        'discount_amount' => $discountAmount,
                        'tax_percent' => $item['tax_rate'] ?? 0,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }
            // Stock/treasury sync happens on post() (SalesInvoice::onPost)
        });

        $salesInvoice->load(['items.item', 'items.unit']);

        return response()->json($salesInvoice);
    }

    public function post(SalesInvoice $salesInvoice)
    {
        try {
            DB::transaction(fn() => $salesInvoice->post());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($salesInvoice->fresh());
    }

    public function cancel(SalesInvoice $salesInvoice)
    {
        try {
            DB::transaction(fn() => $salesInvoice->cancel());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($salesInvoice->fresh());
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        DB::transaction(function () use ($salesInvoice) {
            if ($salesInvoice->isPosted()) {
                $salesInvoice->cancel();
            }
            $salesInvoice->delete();
        });

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesInvoice::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            $m->restore();
            if ($m->isPosted()) {
                $m->post();
            }
        });

        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesInvoice::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_invoice', 'store');
    }

    private static function syncTreasury(SalesInvoice $invoice): void
    {
        Log::info('SalesInvoiceController::syncTreasury called but deprecated; use model lifecycle', ['invoice_id' => $invoice->id]);
        return;
    }

    private static function syncStock(SalesInvoice $invoice, array $items): void
    {
        Log::info('SalesInvoiceController::syncStock called but deprecated; use model lifecycle', ['invoice_id' => $invoice->id]);
        return;
    }
}
