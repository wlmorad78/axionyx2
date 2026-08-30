<?php
/**
 * =====================================================================
 * متحكم (Controller): CityController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): City
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "City" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * عرض قائمة سجلات (City) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = City::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->governorate_id) {
            $query->where('governorate_id', $request->governorate_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (City) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('city', 'store'));
        $city = City::create($data);

        return response()->json($city, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (City) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(City $city)
    {
        return $city;
    }

    /**
     * تحديث بيانات سجل موجود من (City) بناءً على المعرّف.
     */
    public function update(Request $request, City $city)
    {
        $data = $request->validate(ValidationRules::for('city', 'update', $city));
        $city->update($data);

        return response()->json($city);
    }

    /**
     * حذف سجل من (City) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(City $city)
    {
        $city->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (City) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $city = City::onlyTrashed()->findOrFail($id);
        $city->restore();

        return response()->json($city);
    }

    /**
     * حذف نهائي للسجل من (City) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $city = City::onlyTrashed()->findOrFail($id);
        $city->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (City).
     */
    public function schema()
    {
        return ValidationRules::for('city', 'store');
    }
}
