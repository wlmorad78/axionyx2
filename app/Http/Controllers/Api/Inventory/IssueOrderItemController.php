<?php
/**
 * =====================================================================
 * متحكم (Controller): IssueOrderItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Issue Order Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Issue Order Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\IssueOrderItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IssueOrderItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Issue Order Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = IssueOrderItem::with($with);
        if ($request->issue_order_id) $query->where('issue_order_id', $request->issue_order_id);
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
     * إنشاء سجل جديد لـ (Issue Order Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('issue_order_item', 'store'));
        return response()->json(IssueOrderItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Issue Order Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(IssueOrderItem $issueOrderItem)
    {
        return $issueOrderItem->load(['issueOrder', 'item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Issue Order Item) بناءً على المعرّف.
     */
    public function update(Request $request, IssueOrderItem $issueOrderItem)
    {
        $data = $request->validate(ValidationRules::for('issue_order_item', 'update', $issueOrderItem));
        $issueOrderItem->update($data);
        return response()->json($issueOrderItem);
    }

    /**
     * حذف سجل من (Issue Order Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(IssueOrderItem $issueOrderItem)
    {
        $issueOrderItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Issue Order Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = IssueOrderItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Issue Order Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        IssueOrderItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Issue Order Item).
     */
    public function schema()
    {
        return ValidationRules::for('issue_order_item', 'store');
    }
}