<?php
/**
 * =====================================================================
 * متحكم (Controller): StockAdjustmentItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Stock Adjustment Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Stock Adjustment Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAdjustmentItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockAdjustmentItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Stock Adjustment Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockAdjustmentItem::with($with);
        if ($request->stock_adjustment_id) $query->where('stock_adjustment_id', $request->stock_adjustment_id);
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
     * إنشاء سجل جديد لـ (Stock Adjustment Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('stock_adjustment_item', 'store'));
        return response()->json(StockAdjustmentItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Stock Adjustment Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(StockAdjustmentItem $stockAdjustmentItem)
    {
        return $stockAdjustmentItem->load([
            'stockAdjustment', 'item', 'unit', 'batch',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Stock Adjustment Item) بناءً على المعرّف.
     */
    public function update(Request $request, StockAdjustmentItem $stockAdjustmentItem)
    {
        $data = $request->validate(ValidationRules::for('stock_adjustment_item', 'update', $stockAdjustmentItem));
        $stockAdjustmentItem->update($data);
        return response()->json($stockAdjustmentItem);
    }

    /**
     * حذف سجل من (Stock Adjustment Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(StockAdjustmentItem $stockAdjustmentItem)
    {
        $stockAdjustmentItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Stock Adjustment Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = StockAdjustmentItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Stock Adjustment Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        StockAdjustmentItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Stock Adjustment Item).
     */
    public function schema()
    {
        return ValidationRules::for('stock_adjustment_item', 'store');
    }
}
