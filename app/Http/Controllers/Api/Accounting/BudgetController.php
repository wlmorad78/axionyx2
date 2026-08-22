<?php
/**
 * =====================================================================
 * متحكم (Controller): BudgetController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Budget
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Budget" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Budget};
use App\Support\ValidationRules;

class BudgetController extends Controller
{
    /**
     * عرض قائمة سجلات (Budget) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Budget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('budget_code', 'like', "%{$s}%")
                  ->orWhere('budget_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Budget) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('budget', 'create'));
        $budget = Budget::create($data);
        return response()->json($budget, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Budget) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Budget::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Budget) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $budget = Budget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('budget', 'update', $budget));
        $budget->update($data);
        return $budget;
    }

    /**
     * حذف سجل من (Budget) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Budget) وإعادته للعمل.
     */
    public function restore($id)
    {
        $budget = Budget::withTrashed()->findOrFail($id);
        $budget->restore();
        return $budget;
    }

    /**
     * حذف نهائي للسجل من (Budget) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $budget = Budget::withTrashed()->findOrFail($id);
        $budget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
