<?php
/**
 * =====================================================================
 * متحكم (Controller): AvailabilityAuditController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Availability Audit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Availability Audit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityAudit;
use Illuminate\Http\Request;

class AvailabilityAuditController extends Controller
{
    /**
     * عرض قائمة سجلات (Availability Audit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AvailabilityAudit::with('item');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Availability Audit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'item_id' => 'required',
            'is_available' => 'boolean',
            'stock_qty' => 'integer',
        ]);

        $item = AvailabilityAudit::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Availability Audit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = AvailabilityAudit::with('item')->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Availability Audit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = AvailabilityAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'item_id' => 'required',
            'is_available' => 'boolean',
            'stock_qty' => 'integer',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Availability Audit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = AvailabilityAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Availability Audit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = AvailabilityAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Availability Audit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = AvailabilityAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
