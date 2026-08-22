<?php
/**
 * =====================================================================
 * متحكم (Controller): LeadActivityController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Lead Activity
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Lead Activity" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    /**
     * عرض قائمة سجلات (Lead Activity) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = LeadActivity::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('activity_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Lead Activity) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('lead_activity', 'create'));
        $leadActivity = LeadActivity::create($data);
        return response()->json($leadActivity, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Lead Activity) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return LeadActivity::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Lead Activity) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $leadActivity = LeadActivity::findOrFail($id);
        $data = $request->validate(ValidationRules::for('lead_activity', 'update', $leadActivity));
        $leadActivity->update($data);
        return $leadActivity;
    }

    /**
     * حذف سجل من (Lead Activity) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $leadActivity = LeadActivity::findOrFail($id);
        $leadActivity->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Lead Activity) وإعادته للعمل.
     */
    public function restore($id)
    {
        $leadActivity = LeadActivity::withTrashed()->findOrFail($id);
        $leadActivity->restore();
        return $leadActivity;
    }

    /**
     * حذف نهائي للسجل من (Lead Activity) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $leadActivity = LeadActivity::withTrashed()->findOrFail($id);
        $leadActivity->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
