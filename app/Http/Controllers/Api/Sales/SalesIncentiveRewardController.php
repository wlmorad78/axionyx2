<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesIncentiveRewardController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Incentive Reward
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Incentive Reward" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveReward;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveRewardController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Incentive Reward) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveReward::with($with);

        if ($request->sales_incentive_id) {
            $query->where('sales_incentive_id', $request->sales_incentive_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reward_type', 'like', "%$s%")
                    ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Incentive Reward) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_reward', 'store'));
        return response()->json(SalesIncentiveReward::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Incentive Reward) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesIncentiveReward $salesIncentiveReward)
    {
        return $salesIncentiveReward->load(['salesIncentive']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Incentive Reward) بناءً على المعرّف.
     */
    public function update(Request $request, SalesIncentiveReward $salesIncentiveReward)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_reward', 'update', $salesIncentiveReward));
        $salesIncentiveReward->update($data);
        return response()->json($salesIncentiveReward);
    }

    /**
     * حذف سجل من (Sales Incentive Reward) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesIncentiveReward $salesIncentiveReward)
    {
        $salesIncentiveReward->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Incentive Reward) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesIncentiveReward::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Sales Incentive Reward) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesIncentiveReward::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Incentive Reward).
     */
    public function schema()
    {
        return ValidationRules::for('sales_incentive_reward', 'store');
    }
}
