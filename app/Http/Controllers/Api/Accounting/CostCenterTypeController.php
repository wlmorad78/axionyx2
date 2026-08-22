<?php
/**
 * =====================================================================
 * متحكم (Controller): CostCenterTypeController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Cost Center Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Cost Center Type" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CostCenterType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CostCenterTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Cost Center Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CostCenterType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Cost Center Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('cost_center_type', 'store'));
        $type = CostCenterType::create($data);
        return response()->json($type, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Cost Center Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CostCenterType $costCenterType)
    {
        return $costCenterType;
    }

    /**
     * تحديث بيانات سجل موجود من (Cost Center Type) بناءً على المعرّف.
     */
    public function update(Request $request, CostCenterType $costCenterType)
    {
        $data = $request->validate(ValidationRules::for('cost_center_type', 'update', $costCenterType));
        $costCenterType->update($data);
        return response()->json($costCenterType);
    }

    /**
     * حذف سجل من (Cost Center Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CostCenterType $costCenterType)
    {
        if ($costCenterType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع مركز تكلفة نظام'], 403);
        }
        $costCenterType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Cost Center Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $type = CostCenterType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    /**
     * حذف نهائي للسجل من (Cost Center Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $type = CostCenterType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Cost Center Type).
     */
    public function schema()
    {
        return ValidationRules::for('cost_center_type', 'store');
    }
}
