<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyAssignmentController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Assignment" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyAssignment;
use Illuminate\Http\Request;

class SurveyAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyAssignment::with(['survey', 'salesRep', 'route', 'customer']);

        if ($request->search) {
            $query->where('status', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->has('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_date_from')) {
            $query->where('assigned_date', '>=', $request->assigned_date_from);
        }

        if ($request->has('assigned_date_to')) {
            $query->where('assigned_date', '<=', $request->assigned_date_to);
        }

        $assignments = $query->orderBy('assigned_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($assignments);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'route_id' => 'nullable|exists:routes,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_date' => 'required|date',
            'status' => 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
        ]);

        $assignment = SurveyAssignment::create($validated);

        return response()->json($assignment, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyAssignment $surveyAssignment)
    {
        $surveyAssignment->load(['survey', 'salesRep', 'route', 'customer']);
        return response()->json($surveyAssignment);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyAssignment $surveyAssignment)
    {
        $validated = $request->validate([
            'sales_rep_id' => 'nullable|exists:employees,id',
            'route_id' => 'nullable|exists:routes,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_date' => 'sometimes|date',
            'status' => 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
        ]);

        $surveyAssignment->update($validated);

        return response()->json($surveyAssignment);
    }

    /**
     * حذف سجل من (Survey Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyAssignment $surveyAssignment)
    {
        $surveyAssignment->delete();
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Assignment) وإعادته للعمل.
     */
    public function restore($id)
    {
        $assignment = SurveyAssignment::withTrashed()->findOrFail($id);
        $assignment->restore();
        return response()->json(['message' => 'Assignment restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $assignment = SurveyAssignment::withTrashed()->findOrFail($id);
        $assignment->forceDelete();
        return response()->json(['message' => 'Assignment permanently deleted']);
    }
}
