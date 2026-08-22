<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesTargetDetailController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Target Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Target Detail" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SalesTargetDetail};
use App\Support\ValidationRules;

class SalesTargetDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Target Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SalesTargetDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_amount', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Target Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_target_detail', 'create'));
        $salesTargetDetail = SalesTargetDetail::create($data);
        return response()->json($salesTargetDetail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Target Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return SalesTargetDetail::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Target Detail) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $salesTargetDetail = SalesTargetDetail::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sales_target_detail', 'update', $salesTargetDetail));
        $salesTargetDetail->update($data);
        return $salesTargetDetail;
    }

    /**
     * حذف سجل من (Sales Target Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $salesTargetDetail = SalesTargetDetail::findOrFail($id);
        $salesTargetDetail->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Target Detail) وإعادته للعمل.
     */
    public function restore($id)
    {
        $salesTargetDetail = SalesTargetDetail::withTrashed()->findOrFail($id);
        $salesTargetDetail->restore();
        return $salesTargetDetail;
    }

    /**
     * حذف نهائي للسجل من (Sales Target Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $salesTargetDetail = SalesTargetDetail::withTrashed()->findOrFail($id);
        $salesTargetDetail->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
