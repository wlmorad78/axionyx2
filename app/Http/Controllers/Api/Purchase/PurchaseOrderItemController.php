<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseOrderItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Order Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Order Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Order Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseOrderItem::with(['item', 'unit']);

        if ($request->filled('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        $items = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Order Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $id = null;
        $isUpdate = false;
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'item_id' => ['required', 'exists:items,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'qty' => ['sometimes', 'numeric', 'min:0'],
            'received_qty' => ['sometimes', 'numeric', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'net_amount' => ['sometimes', 'numeric'],
        ]);

        $item = PurchaseOrderItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Order Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseOrderItem $purchaseOrderItem)
    {
        $purchaseOrderItem->load(['item', 'unit']);

        return response()->json($purchaseOrderItem);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Order Item) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseOrderItem $purchaseOrderItem)
    {
        $id = $purchaseOrderItem->id;
        $isUpdate = true;
        $validated = $request->validate([
            'purchase_order_id' => ['sometimes', 'exists:purchase_orders,id'],
            'item_id' => ['sometimes', 'exists:items,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'qty' => ['sometimes', 'numeric', 'min:0'],
            'received_qty' => ['sometimes', 'numeric', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'net_amount' => ['sometimes', 'numeric'],
        ]);

        $purchaseOrderItem->update($validated);

        return response()->json($purchaseOrderItem);
    }

    /**
     * حذف سجل من (Purchase Order Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseOrderItem $purchaseOrderItem)
    {
        $purchaseOrderItem->delete();

        return response()->json(['message' => 'Purchase order item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Order Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $purchaseOrderItem = PurchaseOrderItem::withTrashed()->findOrFail($id);
        $purchaseOrderItem->restore();

        return response()->json(['message' => 'Purchase order item restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Purchase Order Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $purchaseOrderItem = PurchaseOrderItem::withTrashed()->findOrFail($id);
        $purchaseOrderItem->forceDelete();

        return response()->json(['message' => 'Purchase order item permanently deleted']);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Order Item).
     */
    public function schema()
    {
        return response()->json([
            'columns' => [
                'id' => 'bigint',
                'purchase_order_id' => 'bigint',
                'item_id' => 'bigint',
                'unit_id' => 'bigint',
                'qty' => 'decimal',
                'received_qty' => 'decimal',
                'price' => 'decimal',
                'discount_amount' => 'decimal',
                'tax_amount' => 'decimal',
                'net_amount' => 'decimal',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
                'deleted_at' => 'timestamp',
            ],
        ]);
    }
}
