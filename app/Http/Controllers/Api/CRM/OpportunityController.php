<?php
/**
 * =====================================================================
 * متحكم (Controller): OpportunityController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Opportunity
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Opportunity" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Opportunity;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    /**
     * عرض قائمة سجلات (Opportunity) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Opportunity::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('opportunity_name', 'like', "%{$s}%")
                    ->orWhere('stage', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Opportunity) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opportunity', 'create'));
        $opportunity = Opportunity::create($data);
        return response()->json($opportunity, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Opportunity) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Opportunity::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Opportunity) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $data = $request->validate(ValidationRules::for('opportunity', 'update', $opportunity));
        $opportunity->update($data);
        return $opportunity;
    }

    /**
     * حذف سجل من (Opportunity) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $opportunity->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Opportunity) وإعادته للعمل.
     */
    public function restore($id)
    {
        $opportunity = Opportunity::withTrashed()->findOrFail($id);
        $opportunity->restore();
        return $opportunity;
    }

    /**
     * حذف نهائي للسجل من (Opportunity) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $opportunity = Opportunity::withTrashed()->findOrFail($id);
        $opportunity->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
