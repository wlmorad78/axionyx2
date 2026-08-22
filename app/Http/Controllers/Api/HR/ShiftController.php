<?php
/**
 * =====================================================================
 * متحكم (Controller): ShiftController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Shift
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shift" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * عرض قائمة سجلات (Shift) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Shift::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->shift_type_id) $query->where('shift_type_id', $request->shift_type_id);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Shift) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('shift', 'store'));

        return response()->json(Shift::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shift) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Shift $shift)
    {
        return $shift->load(['company', 'shiftType']);
    }

    /**
     * تحديث بيانات سجل موجود من (Shift) بناءً على المعرّف.
     */
    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate(ValidationRules::for('shift', 'update', $shift));

        $shift->update($data);

        return response()->json($shift);
    }

    /**
     * حذف سجل من (Shift) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Shift $shift)
    {
        $shift->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shift) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $shift = Shift::onlyTrashed()->findOrFail($id);
        $shift->restore();

        return response()->json($shift);
    }

    /**
     * حذف نهائي للسجل من (Shift) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Shift::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Shift).
     */
    public function schema()
    {
        return ValidationRules::for('shift', 'store');
    }
}
