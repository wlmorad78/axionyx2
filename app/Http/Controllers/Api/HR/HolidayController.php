<?php
/**
 * =====================================================================
 * متحكم (Controller): HolidayController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Holiday
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Holiday" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * عرض قائمة سجلات (Holiday) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Holiday::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Holiday) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('holiday', 'store'));

        return response()->json(Holiday::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Holiday) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Holiday $holiday)
    {
        return $holiday->load(['company']);
    }

    /**
     * تحديث بيانات سجل موجود من (Holiday) بناءً على المعرّف.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate(ValidationRules::for('holiday', 'update', $holiday));

        $holiday->update($data);

        return response()->json($holiday);
    }

    /**
     * حذف سجل من (Holiday) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Holiday) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $holiday = Holiday::onlyTrashed()->findOrFail($id);
        $holiday->restore();

        return response()->json($holiday);
    }

    /**
     * حذف نهائي للسجل من (Holiday) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Holiday::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Holiday).
     */
    public function schema()
    {
        return ValidationRules::for('holiday', 'store');
    }
}
