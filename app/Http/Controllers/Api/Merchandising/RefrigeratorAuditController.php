<?php
/**
 * =====================================================================
 * متحكم (Controller): RefrigeratorAuditController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Refrigerator Audit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Refrigerator Audit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\RefrigeratorAudit;
use Illuminate\Http\Request;

class RefrigeratorAuditController extends Controller
{
    /**
     * عرض قائمة سجلات (Refrigerator Audit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = RefrigeratorAudit::with('marketingAsset');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('working_status')) {
            $query->where('working_status', $request->working_status);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Refrigerator Audit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_asset_id' => 'required',
            'temperature' => 'numeric',
            'cleanliness_score' => 'numeric',
            'working_status' => 'required|in:WORKING,NEEDS_MAINTENANCE,OUT_OF_SERVICE',
            'notes' => 'nullable|string',
        ]);

        $item = RefrigeratorAudit::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Refrigerator Audit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = RefrigeratorAudit::with('marketingAsset')->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Refrigerator Audit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = RefrigeratorAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_asset_id' => 'required',
            'temperature' => 'numeric',
            'cleanliness_score' => 'numeric',
            'working_status' => 'required|in:WORKING,NEEDS_MAINTENANCE,OUT_OF_SERVICE',
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Refrigerator Audit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = RefrigeratorAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Refrigerator Audit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = RefrigeratorAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Refrigerator Audit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = RefrigeratorAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
