<?php
/**
 * =====================================================================
 * متحكم (Controller): ShelfAuditController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Shelf Audit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shelf Audit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfAudit;
use Illuminate\Http\Request;

class ShelfAuditController extends Controller
{
    /**
     * عرض قائمة سجلات (Shelf Audit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ShelfAudit::with(['location', 'items', 'competitorItems']);

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        $shelfAudits = $query->paginate($request->get('per_page', 15));

        return response()->json($shelfAudits);
    }

    /**
     * إنشاء سجل جديد لـ (Shelf Audit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'display_location_id' => 'required',
            'shelf_length' => 'nullable|numeric',
            'shelf_width' => 'nullable|numeric',
            'shelf_height' => 'nullable|numeric',
        ]);

        $shelfAudit = ShelfAudit::create($validated);

        return response()->json($shelfAudit->load(['location', 'items', 'competitorItems']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shelf Audit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $shelfAudit = ShelfAudit::with(['location', 'items', 'competitorItems'])->findOrFail($id);

        return response()->json($shelfAudit);
    }

    /**
     * تحديث بيانات سجل موجود من (Shelf Audit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $shelfAudit = ShelfAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'display_location_id' => 'required',
            'shelf_length' => 'nullable|numeric',
            'shelf_width' => 'nullable|numeric',
            'shelf_height' => 'nullable|numeric',
        ]);

        $shelfAudit->update($validated);

        return response()->json($shelfAudit->load(['location', 'items', 'competitorItems']));
    }

    /**
     * حذف سجل من (Shelf Audit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $shelfAudit = ShelfAudit::findOrFail($id);
        $shelfAudit->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shelf Audit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $shelfAudit = ShelfAudit::onlyTrashed()->findOrFail($id);
        $shelfAudit->restore();

        return response()->json($shelfAudit);
    }

    /**
     * حذف نهائي للسجل من (Shelf Audit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $shelfAudit = ShelfAudit::onlyTrashed()->findOrFail($id);
        $shelfAudit->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
