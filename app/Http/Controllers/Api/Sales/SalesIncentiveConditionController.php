<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesIncentiveConditionController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Incentive Condition
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Incentive Condition" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveCondition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveConditionController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Incentive Condition) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveCondition::with($with);

        if ($request->sales_incentive_id) {
            $query->where('sales_incentive_id', $request->sales_incentive_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('condition_type', 'like', "%$s%")
                    ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Incentive Condition) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition', 'store'));
        return response()->json(SalesIncentiveCondition::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Incentive Condition) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesIncentiveCondition $salesIncentiveCondition)
    {
        return $salesIncentiveCondition->load(['salesIncentive']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Incentive Condition) بناءً على المعرّف.
     */
    public function update(Request $request, SalesIncentiveCondition $salesIncentiveCondition)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition', 'update', $salesIncentiveCondition));
        $salesIncentiveCondition->update($data);
        return response()->json($salesIncentiveCondition);
    }

    /**
     * حذف سجل من (Sales Incentive Condition) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesIncentiveCondition $salesIncentiveCondition)
    {
        $salesIncentiveCondition->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Incentive Condition) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesIncentiveCondition::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Sales Incentive Condition) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesIncentiveCondition::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Incentive Condition).
     */
    public function schema()
    {
        return ValidationRules::for('sales_incentive_condition', 'store');
    }
}
