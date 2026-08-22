<?php
/**
 * =====================================================================
 * متحكم (Controller): BudgetLineController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Budget Line
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Budget Line" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{BudgetLine};
use App\Support\ValidationRules;

class BudgetLineController extends Controller
{
    /**
     * عرض قائمة سجلات (Budget Line) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BudgetLine::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('planned_amount', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Budget Line) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('budget_line', 'create'));
        $budgetLine = BudgetLine::create($data);
        return response()->json($budgetLine, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Budget Line) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return BudgetLine::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Budget Line) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $budgetLine = BudgetLine::findOrFail($id);
        $data = $request->validate(ValidationRules::for('budget_line', 'update', $budgetLine));
        $budgetLine->update($data);
        return $budgetLine;
    }

    /**
     * حذف سجل من (Budget Line) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $budgetLine = BudgetLine::findOrFail($id);
        $budgetLine->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Budget Line) وإعادته للعمل.
     */
    public function restore($id)
    {
        $budgetLine = BudgetLine::withTrashed()->findOrFail($id);
        $budgetLine->restore();
        return $budgetLine;
    }

    /**
     * حذف نهائي للسجل من (Budget Line) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $budgetLine = BudgetLine::withTrashed()->findOrFail($id);
        $budgetLine->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
