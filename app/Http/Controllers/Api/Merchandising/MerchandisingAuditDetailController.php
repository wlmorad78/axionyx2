<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingAuditDetailController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Audit Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Audit Detail" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAuditDetail;
use Illuminate\Http\Request;

class MerchandisingAuditDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Audit Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingAuditDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        $details = $query->paginate($request->get('per_page', 15));

        return response()->json($details);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Audit Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'merchandising_standard_item_id' => 'required',
            'score' => 'nullable|numeric',
            'remarks' => 'nullable',
        ]);

        $detail = MerchandisingAuditDetail::create($validated);

        return response()->json($detail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Audit Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);

        return response()->json($detail);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Audit Detail) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'merchandising_standard_item_id' => 'required',
            'score' => 'nullable|numeric',
            'remarks' => 'nullable',
        ]);

        $detail->update($validated);

        return response()->json($detail);
    }

    /**
     * حذف سجل من (Merchandising Audit Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Audit Detail) وإعادته للعمل.
     */
    public function restore($id)
    {
        $detail = MerchandisingAuditDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();

        return response()->json($detail);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Audit Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $detail = MerchandisingAuditDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
