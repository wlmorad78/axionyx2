<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Survey::with(['category', 'createdBy']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('survey_code', 'like', "%{$request->search}%")
                  ->orWhere('survey_name', 'like', "%{$request->search}%")
                  ->orWhere('status', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('survey_category_id')) {
            $query->where('survey_category_id', $request->survey_category_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($surveys);
    }

    /**
     * إنشاء سجل جديد لـ (Survey) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'survey_category_id' => 'required|exists:survey_categories,id',
            'survey_code' => 'required|string|max:255',
            'survey_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_mandatory' => 'boolean',
            'status' => 'in:DRAFT,ACTIVE,INACTIVE,CLOSED',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $survey = Survey::create($validated);

        return response()->json($survey, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Survey $survey)
    {
        $survey->load(['category', 'questions.options', 'createdBy', 'scoringRules', 'assignments']);
        return response()->json($survey);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey) بناءً على المعرّف.
     */
    public function update(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'survey_category_id' => 'sometimes|exists:survey_categories,id',
            'survey_code' => 'sometimes|string|max:255',
            'survey_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_mandatory' => 'boolean',
            'status' => 'in:DRAFT,ACTIVE,INACTIVE,CLOSED',
        ]);

        $survey->update($validated);

        return response()->json($survey);
    }

    /**
     * حذف سجل من (Survey) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Survey $survey)
    {
        $survey->delete();
        return response()->json(['message' => 'Survey deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey) وإعادته للعمل.
     */
    public function restore($id)
    {
        $survey = Survey::withTrashed()->findOrFail($id);
        $survey->restore();
        return response()->json(['message' => 'Survey restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $survey = Survey::withTrashed()->findOrFail($id);
        $survey->forceDelete();
        return response()->json(['message' => 'Survey permanently deleted']);
    }
}
