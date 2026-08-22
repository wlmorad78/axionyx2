<?php
/**
 * =====================================================================
 * متحكم (Controller): OrganizationalLevelController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Organizational Level
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Organizational Level" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalLevel;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OrganizationalLevelController extends Controller
{
    /**
     * عرض قائمة سجلات (Organizational Level) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OrganizationalLevel::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->orderBy('level_order')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Organizational Level) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('organizational_level', 'store'));
        $level = OrganizationalLevel::create($data);
        return response()->json($level, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Organizational Level) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(OrganizationalLevel $organizationalLevel)
    {
        return $organizationalLevel;
    }

    /**
     * تحديث بيانات سجل موجود من (Organizational Level) بناءً على المعرّف.
     */
    public function update(Request $request, OrganizationalLevel $organizationalLevel)
    {
        $data = $request->validate(ValidationRules::for('organizational_level', 'update', $organizationalLevel));
        $organizationalLevel->update($data);
        return response()->json($organizationalLevel);
    }

    /**
     * حذف سجل من (Organizational Level) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(OrganizationalLevel $organizationalLevel)
    {
        if ($organizationalLevel->is_system) {
            return response()->json(['message' => 'لا يمكن حذف مستوى نظام'], 403);
        }
        $organizationalLevel->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Organizational Level) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $level = OrganizationalLevel::onlyTrashed()->findOrFail($id);
        $level->restore();
        return response()->json($level);
    }

    /**
     * حذف نهائي للسجل من (Organizational Level) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $level = OrganizationalLevel::onlyTrashed()->findOrFail($id);
        $level->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Organizational Level).
     */
    public function schema()
    {
        return ValidationRules::for('organizational_level', 'store');
    }
}
