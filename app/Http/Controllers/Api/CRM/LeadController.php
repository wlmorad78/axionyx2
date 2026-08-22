<?php
/**
 * =====================================================================
 * متحكم (Controller): LeadController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Lead
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Lead" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * عرض قائمة سجلات (Lead) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('lead_code', 'like', "%{$s}%")
                    ->orWhere('lead_name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('source', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Lead) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('lead', 'create'));
        $lead = Lead::create($data);
        return response()->json($lead, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Lead) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Lead::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Lead) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        $data = $request->validate(ValidationRules::for('lead', 'update', $lead));
        $lead->update($data);
        return $lead;
    }

    /**
     * حذف سجل من (Lead) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Lead) وإعادته للعمل.
     */
    public function restore($id)
    {
        $lead = Lead::withTrashed()->findOrFail($id);
        $lead->restore();
        return $lead;
    }

    /**
     * حذف نهائي للسجل من (Lead) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $lead = Lead::withTrashed()->findOrFail($id);
        $lead->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
