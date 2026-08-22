<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryTypeController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Type" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class TreasuryTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = TreasuryType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('treasury_type', 'store'));
        $treasuryType = TreasuryType::create($data);
        return response()->json($treasuryType, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TreasuryType $treasuryType)
    {
        return $treasuryType;
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Type) بناءً على المعرّف.
     */
    public function update(Request $request, TreasuryType $treasuryType)
    {
        $data = $request->validate(ValidationRules::for('treasury_type', 'update', $treasuryType));
        $treasuryType->update($data);
        return response()->json($treasuryType);
    }

    /**
     * حذف سجل من (Treasury Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TreasuryType $treasuryType)
    {
        if ($treasuryType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع نظام'], 403);
        }
        $treasuryType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $treasuryType = TreasuryType::onlyTrashed()->findOrFail($id);
        $treasuryType->restore();
        return response()->json($treasuryType);
    }

    /**
     * حذف نهائي للسجل من (Treasury Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $treasuryType = TreasuryType::onlyTrashed()->findOrFail($id);
        $treasuryType->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Treasury Type).
     */
    public function schema()
    {
        return ValidationRules::for('treasury_type', 'store');
    }
}
