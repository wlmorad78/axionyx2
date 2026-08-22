<?php
/**
 * =====================================================================
 * متحكم (Controller): StreetController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Street
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Street" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Street;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StreetController extends Controller
{
    /**
     * عرض قائمة سجلات (Street) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Street::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Street) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('street', 'store'));
        $street = Street::create($data);

        return response()->json($street, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Street) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Street $street)
    {
        return $street;
    }

    /**
     * تحديث بيانات سجل موجود من (Street) بناءً على المعرّف.
     */
    public function update(Request $request, Street $street)
    {
        $data = $request->validate(ValidationRules::for('street', 'update', $street));
        $street->update($data);

        return response()->json($street);
    }

    /**
     * حذف سجل من (Street) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Street $street)
    {
        $street->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Street) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $street = Street::onlyTrashed()->findOrFail($id);
        $street->restore();

        return response()->json($street);
    }

    /**
     * حذف نهائي للسجل من (Street) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $street = Street::onlyTrashed()->findOrFail($id);
        $street->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Street).
     */
    public function schema()
    {
        return ValidationRules::for('street', 'store');
    }
}
