<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryRevaluationItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Revaluation Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Revaluation Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryRevaluationItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryRevaluationItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Revaluation Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryRevaluationItem::with($with);
        if ($request->inventory_revaluation_id) $query->where('inventory_revaluation_id', $request->inventory_revaluation_id);
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
     * إنشاء سجل جديد لـ (Inventory Revaluation Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation_item', 'store'));
        return response()->json(InventoryRevaluationItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Revaluation Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryRevaluationItem $inventoryRevaluationItem)
    {
        return $inventoryRevaluationItem->load([
            'inventoryRevaluation', 'item', 'unit', 'batch',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Revaluation Item) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryRevaluationItem $inventoryRevaluationItem)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation_item', 'update', $inventoryRevaluationItem));
        $inventoryRevaluationItem->update($data);
        return response()->json($inventoryRevaluationItem);
    }

    /**
     * حذف سجل من (Inventory Revaluation Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryRevaluationItem $inventoryRevaluationItem)
    {
        $inventoryRevaluationItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Revaluation Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryRevaluationItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Revaluation Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryRevaluationItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Revaluation Item).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_revaluation_item', 'store');
    }
}
