<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesIncentiveConditionItemController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Incentive Condition Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Incentive Condition Item" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveConditionItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveConditionItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Incentive Condition Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveConditionItem::with($with);
        if ($request->condition_id) $query->where('condition_id', $request->condition_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Incentive Condition Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition_item', 'store'));
        return response()->json(SalesIncentiveConditionItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Incentive Condition Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesIncentiveConditionItem $item)
    {
        return $item->load(['condition', 'item']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Incentive Condition Item) بناءً على المعرّف.
     */
    public function update(Request $request, SalesIncentiveConditionItem $item)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition_item', 'update', $item));
        $item->update($data);
        return response()->json($item);
    }

    /**
     * حذف سجل من (Sales Incentive Condition Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesIncentiveConditionItem $item)
    {
        $item->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Incentive Condition Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SalesIncentiveConditionItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Sales Incentive Condition Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesIncentiveConditionItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Incentive Condition Item).
     */
    public function schema()
    {
        return ValidationRules::for('sales_incentive_condition_item', 'store');
    }
}
