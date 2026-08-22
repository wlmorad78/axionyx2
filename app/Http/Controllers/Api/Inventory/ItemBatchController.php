<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemBatchController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Item Batch
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Batch" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemBatch;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemBatchController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Batch) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ItemBatch::with($with);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('batch_no', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Item Batch) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_batch', 'store'));
        return response()->json(ItemBatch::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Batch) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ItemBatch $itemBatch)
    {
        return $itemBatch->load([
            'company', 'item', 'warehouse',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Item Batch) بناءً على المعرّف.
     */
    public function update(Request $request, ItemBatch $itemBatch)
    {
        $data = $request->validate(ValidationRules::for('item_batch', 'update', $itemBatch));
        $itemBatch->update($data);
        return response()->json($itemBatch);
    }

    /**
     * حذف سجل من (Item Batch) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ItemBatch $itemBatch)
    {
        $itemBatch->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Item Batch) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = ItemBatch::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Item Batch) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ItemBatch::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Item Batch).
     */
    public function schema()
    {
        return ValidationRules::for('item_batch', 'store');
    }
}
