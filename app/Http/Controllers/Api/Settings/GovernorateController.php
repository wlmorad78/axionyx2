<?php
/**
 * =====================================================================
 * متحكم (Controller): GovernorateController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Governorate
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Governorate" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    /**
     * عرض قائمة سجلات (Governorate) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Governorate::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->country_id) {
            $query->where('country_id', $request->country_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Governorate) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('governorate', 'store'));
        $governorate = Governorate::create($data);

        return response()->json($governorate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Governorate) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Governorate $governorate)
    {
        return $governorate;
    }

    /**
     * تحديث بيانات سجل موجود من (Governorate) بناءً على المعرّف.
     */
    public function update(Request $request, Governorate $governorate)
    {
        $data = $request->validate(ValidationRules::for('governorate', 'update', $governorate));
        $governorate->update($data);

        return response()->json($governorate);
    }

    /**
     * حذف سجل من (Governorate) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Governorate $governorate)
    {
        $governorate->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Governorate) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $governorate = Governorate::onlyTrashed()->findOrFail($id);
        $governorate->restore();

        return response()->json($governorate);
    }

    /**
     * حذف نهائي للسجل من (Governorate) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $governorate = Governorate::onlyTrashed()->findOrFail($id);
        $governorate->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Governorate).
     */
    public function schema()
    {
        return ValidationRules::for('governorate', 'store');
    }
}
