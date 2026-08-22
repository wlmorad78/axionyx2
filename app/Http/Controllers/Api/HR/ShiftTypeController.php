<?php
/**
 * =====================================================================
 * متحكم (Controller): ShiftTypeController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Shift Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shift Type" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\ShiftType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Shift Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShiftType::with($with);

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
     * إنشاء سجل جديد لـ (Shift Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('shift_type', 'store'));

        return response()->json(ShiftType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shift Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ShiftType $shiftType)
    {
        return $shiftType;
    }

    /**
     * تحديث بيانات سجل موجود من (Shift Type) بناءً على المعرّف.
     */
    public function update(Request $request, ShiftType $shiftType)
    {
        $data = $request->validate(ValidationRules::for('shift_type', 'update', $shiftType));

        $shiftType->update($data);

        return response()->json($shiftType);
    }

    /**
     * حذف سجل من (Shift Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ShiftType $shiftType)
    {
        if ($shiftType->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $shiftType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shift Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $shiftType = ShiftType::onlyTrashed()->findOrFail($id);
        $shiftType->restore();

        return response()->json($shiftType);
    }

    /**
     * حذف نهائي للسجل من (Shift Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ShiftType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Shift Type).
     */
    public function schema()
    {
        return ValidationRules::for('shift_type', 'store');
    }
}
