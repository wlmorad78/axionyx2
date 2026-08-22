<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesTargetController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Target
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Target" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SalesTarget};
use App\Support\ValidationRules;

class SalesTargetController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Target) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SalesTarget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('year', 'like', "%{$s}%")
                  ->orWhere('month', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Target) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_target', 'create'));
        $salesTarget = SalesTarget::create($data);
        return response()->json($salesTarget, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Target) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return SalesTarget::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Target) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $salesTarget = SalesTarget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sales_target', 'update', $salesTarget));
        $salesTarget->update($data);
        return $salesTarget;
    }

    /**
     * حذف سجل من (Sales Target) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $salesTarget = SalesTarget::findOrFail($id);
        $salesTarget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Target) وإعادته للعمل.
     */
    public function restore($id)
    {
        $salesTarget = SalesTarget::withTrashed()->findOrFail($id);
        $salesTarget->restore();
        return $salesTarget;
    }

    /**
     * حذف نهائي للسجل من (Sales Target) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $salesTarget = SalesTarget::withTrashed()->findOrFail($id);
        $salesTarget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
