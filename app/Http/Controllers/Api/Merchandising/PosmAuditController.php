<?php
/**
 * =====================================================================
 * متحكم (Controller): PosmAuditController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Posm Audit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Posm Audit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\PosmAudit;
use Illuminate\Http\Request;

class PosmAuditController extends Controller
{
    /**
     * عرض قائمة سجلات (Posm Audit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PosmAudit::with('marketingMaterial');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('condition_status')) {
            $query->where('condition_status', $request->condition_status);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Posm Audit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_material_id' => 'required',
            'is_available' => 'boolean',
            'condition_status' => 'required|in:GOOD,DAMAGED,MISSING',
        ]);

        $item = PosmAudit::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Posm Audit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = PosmAudit::with('marketingMaterial')->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Posm Audit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = PosmAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_material_id' => 'required',
            'is_available' => 'boolean',
            'condition_status' => 'required|in:GOOD,DAMAGED,MISSING',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Posm Audit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = PosmAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Posm Audit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = PosmAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Posm Audit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = PosmAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
