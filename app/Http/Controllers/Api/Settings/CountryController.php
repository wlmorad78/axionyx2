<?php
/**
 * =====================================================================
 * متحكم (Controller): CountryController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Country
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Country" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * عرض قائمة سجلات (Country) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Country::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Country) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('country', 'store'));
        $country = Country::create($data);

        return response()->json($country, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Country) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Country $country)
    {
        return $country;
    }

    /**
     * تحديث بيانات سجل موجود من (Country) بناءً على المعرّف.
     */
    public function update(Request $request, Country $country)
    {
        $data = $request->validate(ValidationRules::for('country', 'update', $country));
        $country->update($data);

        return response()->json($country);
    }

    /**
     * حذف سجل من (Country) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Country $country)
    {
        $country->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Country) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $country = Country::onlyTrashed()->findOrFail($id);
        $country->restore();

        return response()->json($country);
    }

    /**
     * حذف نهائي للسجل من (Country) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $country = Country::onlyTrashed()->findOrFail($id);
        $country->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Country).
     */
    public function schema()
    {
        return ValidationRules::for('country', 'store');
    }
}
