<?php
/**
 * =====================================================================
 * متحكم (Controller): ReturnOrderItemController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Return Order Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Return Order Item" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\ReturnOrderItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ReturnOrderItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Return Order Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ReturnOrderItem::with($with);
        if ($request->return_order_id) $query->where('return_order_id', $request->return_order_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Return Order Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('return_order_item', 'store'));
        return response()->json(ReturnOrderItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Return Order Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ReturnOrderItem $returnOrderItem)
    {
        return $returnOrderItem->load(['returnOrder', 'item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Return Order Item) بناءً على المعرّف.
     */
    public function update(Request $request, ReturnOrderItem $returnOrderItem)
    {
        $data = $request->validate(ValidationRules::for('return_order_item', 'update', $returnOrderItem));
        $returnOrderItem->update($data);
        return response()->json($returnOrderItem);
    }

    /**
     * حذف سجل من (Return Order Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ReturnOrderItem $returnOrderItem)
    {
        $returnOrderItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Return Order Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = ReturnOrderItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Return Order Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ReturnOrderItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Return Order Item).
     */
    public function schema()
    {
        return ValidationRules::for('return_order_item', 'store');
    }
}
