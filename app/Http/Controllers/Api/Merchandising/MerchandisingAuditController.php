<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingAuditController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Audit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Audit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAudit;
use Illuminate\Http\Request;

class MerchandisingAuditController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Audit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingAudit::with(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->filled('audit_date_from') && $request->filled('audit_date_to')) {
            $query->whereBetween('audit_date', [$request->audit_date_from, $request->audit_date_to]);
        } elseif ($request->filled('audit_date_from')) {
            $query->where('audit_date', '>=', $request->audit_date_from);
        } elseif ($request->filled('audit_date_to')) {
            $query->where('audit_date', '<=', $request->audit_date_to);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Audit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'visit_id' => 'nullable',
            'audit_date' => 'required|date',
            'audit_time' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'overall_score' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $audit = MerchandisingAudit::create($validated);

        return response()->json($audit->load(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Audit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $audit = MerchandisingAudit::with(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos'])->findOrFail($id);

        return response()->json($audit);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Audit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $audit = MerchandisingAudit::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'visit_id' => 'nullable',
            'audit_date' => 'required|date',
            'audit_time' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'overall_score' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $audit->update($validated);

        return response()->json($audit->load(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']));
    }

    /**
     * حذف سجل من (Merchandising Audit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $audit = MerchandisingAudit::findOrFail($id);
        $audit->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Audit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $audit = MerchandisingAudit::onlyTrashed()->findOrFail($id);
        $audit->restore();

        return response()->json($audit);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Audit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $audit = MerchandisingAudit::onlyTrashed()->findOrFail($id);
        $audit->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
