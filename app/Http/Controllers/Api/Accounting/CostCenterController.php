<?php
/**
 * =====================================================================
 * متحكم (Controller): CostCenterController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Cost Center
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Cost Center" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    /**
     * عرض قائمة سجلات (Cost Center) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CostCenter::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->cost_center_type_id) {
            $query->where('cost_center_type_id', $request->cost_center_type_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->parent_id !== null) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Cost Center) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('cost_center', 'store'));
        $center = CostCenter::create($data);
        return response()->json($center, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Cost Center) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CostCenter $costCenter)
    {
        return $costCenter->load(['company', 'costCenterType', 'parent', 'branch', 'children']);
    }

    /**
     * تحديث بيانات سجل موجود من (Cost Center) بناءً على المعرّف.
     */
    public function update(Request $request, CostCenter $costCenter)
    {
        $data = $request->validate(ValidationRules::for('cost_center', 'update', $costCenter));
        $costCenter->update($data);
        return response()->json($costCenter);
    }

    /**
     * حذف سجل من (Cost Center) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CostCenter $costCenter)
    {
        $costCenter->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Cost Center) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $center = CostCenter::onlyTrashed()->findOrFail($id);
        $center->restore();
        return response()->json($center);
    }

    /**
     * حذف نهائي للسجل من (Cost Center) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $center = CostCenter::onlyTrashed()->findOrFail($id);
        $center->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Cost Center).
     */
    public function nextCode(Request $request)
    {
        $last = CostCenter::orderBy('id', 'desc')->first();
        if ($last && preg_match('/CC-(\d+)/', $last->code, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'CC-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Cost Center).
     */
    public function schema()
    {
        return ValidationRules::for('cost_center', 'store');
    }
}
