<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryTransactionItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Transaction Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Transaction Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransactionItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Transaction Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransactionItem::with($with);
        if ($request->inventory_transaction_id) $query->where('inventory_transaction_id', $request->inventory_transaction_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")->orWhere('batch_no', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Inventory Transaction Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_item', 'store'));
        return response()->json(InventoryTransactionItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Transaction Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryTransactionItem $inventoryTransactionItem)
    {
        return $inventoryTransactionItem->load([
            'inventoryTransaction', 'item', 'unit', 'batch',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Transaction Item) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryTransactionItem $inventoryTransactionItem)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_item', 'update', $inventoryTransactionItem));
        $inventoryTransactionItem->update($data);
        return response()->json($inventoryTransactionItem);
    }

    /**
     * حذف سجل من (Inventory Transaction Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryTransactionItem $inventoryTransactionItem)
    {
        $inventoryTransactionItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Transaction Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryTransactionItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Transaction Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryTransactionItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Transaction Item).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_transaction_item', 'store');
    }
}
