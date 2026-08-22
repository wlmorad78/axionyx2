<?php
/**
 * =====================================================================
 * متحكم (Controller): StockCountItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Stock Count Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Stock Count Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockCountItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockCountItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Stock Count Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockCountItem::with($with);
        if ($request->stock_count_id) $query->where('stock_count_id', $request->stock_count_id);
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
     * إنشاء سجل جديد لـ (Stock Count Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('stock_count_item', 'store'));
        return response()->json(StockCountItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Stock Count Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(StockCountItem $stockCountItem)
    {
        return $stockCountItem->load([
            'stockCount', 'item', 'unit', 'batch',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Stock Count Item) بناءً على المعرّف.
     */
    public function update(Request $request, StockCountItem $stockCountItem)
    {
        $data = $request->validate(ValidationRules::for('stock_count_item', 'update', $stockCountItem));
        $stockCountItem->update($data);
        return response()->json($stockCountItem);
    }

    /**
     * حذف سجل من (Stock Count Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(StockCountItem $stockCountItem)
    {
        $stockCountItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Stock Count Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = StockCountItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Stock Count Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        StockCountItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Stock Count Item).
     */
    public function schema()
    {
        return ValidationRules::for('stock_count_item', 'store');
    }
}
