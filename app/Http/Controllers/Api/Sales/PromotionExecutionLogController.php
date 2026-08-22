<?php
/**
 * =====================================================================
 * متحكم (Controller): PromotionExecutionLogController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Promotion Execution Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Promotion Execution Log" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionExecutionLog};
use App\Support\ValidationRules;

class PromotionExecutionLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Promotion Execution Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PromotionExecutionLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('condition_result', 'like', "%{$s}%")
                  ->orWhere('reward_result', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Promotion Execution Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('promotion_execution_log', 'create'));
        $promotionExecutionLog = PromotionExecutionLog::create($data);
        return response()->json($promotionExecutionLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Promotion Execution Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return PromotionExecutionLog::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Promotion Execution Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $promotionExecutionLog = PromotionExecutionLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('promotion_execution_log', 'update', $promotionExecutionLog));
        $promotionExecutionLog->update($data);
        return $promotionExecutionLog;
    }

    /**
     * حذف سجل من (Promotion Execution Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $promotionExecutionLog = PromotionExecutionLog::findOrFail($id);
        $promotionExecutionLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Promotion Execution Log) وإعادته للعمل.
     */
    public function restore($id)
    {
        $promotionExecutionLog = PromotionExecutionLog::withTrashed()->findOrFail($id);
        $promotionExecutionLog->restore();
        return $promotionExecutionLog;
    }

    /**
     * حذف نهائي للسجل من (Promotion Execution Log) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $promotionExecutionLog = PromotionExecutionLog::withTrashed()->findOrFail($id);
        $promotionExecutionLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
