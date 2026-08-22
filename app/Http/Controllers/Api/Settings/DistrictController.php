<?php
/**
 * =====================================================================
 * متحكم (Controller): DistrictController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): District
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "District" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\District;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    /**
     * عرض قائمة سجلات (District) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = District::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (District) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('district', 'store'));
        $district = District::create($data);

        return response()->json($district, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (District) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(District $district)
    {
        return $district;
    }

    /**
     * تحديث بيانات سجل موجود من (District) بناءً على المعرّف.
     */
    public function update(Request $request, District $district)
    {
        $data = $request->validate(ValidationRules::for('district', 'update', $district));
        $district->update($data);

        return response()->json($district);
    }

    /**
     * حذف سجل من (District) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(District $district)
    {
        $district->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (District) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $district = District::onlyTrashed()->findOrFail($id);
        $district->restore();

        return response()->json($district);
    }

    /**
     * حذف نهائي للسجل من (District) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $district = District::onlyTrashed()->findOrFail($id);
        $district->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (District).
     */
    public function schema()
    {
        return ValidationRules::for('district', 'store');
    }
}
