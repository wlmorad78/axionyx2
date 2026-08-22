<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesIncentiveController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Incentive
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Incentive" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentive;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Incentive) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentive::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Incentive) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive', 'store'));
        return response()->json(SalesIncentive::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Incentive) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesIncentive $salesIncentive)
    {
        return $salesIncentive->load(['company', 'conditions', 'rewards']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Incentive) بناءً على المعرّف.
     */
    public function update(Request $request, SalesIncentive $salesIncentive)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive', 'update', $salesIncentive));
        $salesIncentive->update($data);
        return response()->json($salesIncentive);
    }

    /**
     * حذف سجل من (Sales Incentive) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesIncentive $salesIncentive)
    {
        $salesIncentive->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Incentive) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesIncentive::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Sales Incentive) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesIncentive::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Incentive).
     */
    public function schema()
    {
        return ValidationRules::for('sales_incentive', 'store');
    }
}
