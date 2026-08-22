<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyQuestionRuleController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Question Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Question Rule" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestionRule;
use Illuminate\Http\Request;

class SurveyQuestionRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Question Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyQuestionRule::with(['question', 'parentQuestion']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('operator', 'like', "%{$request->search}%")
                  ->orWhere('action_type', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $rules = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($rules);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Question Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_question_id' => 'required|exists:survey_questions,id',
            'parent_question_id' => 'required|exists:survey_questions,id',
            'operator' => 'required|in:=,!=,>,<,>=,<=',
            'expected_value' => 'required|string',
            'action_type' => 'required|in:SHOW,HIDE,REQUIRE',
        ]);

        $rule = SurveyQuestionRule::create($validated);

        return response()->json($rule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Question Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyQuestionRule $surveyQuestionRule)
    {
        $surveyQuestionRule->load(['question', 'parentQuestion']);
        return response()->json($surveyQuestionRule);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Question Rule) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyQuestionRule $surveyQuestionRule)
    {
        $validated = $request->validate([
            'parent_question_id' => 'sometimes|exists:survey_questions,id',
            'operator' => 'sometimes|in:=,!=,>,<,>=,<=',
            'expected_value' => 'sometimes|string',
            'action_type' => 'sometimes|in:SHOW,HIDE,REQUIRE',
        ]);

        $surveyQuestionRule->update($validated);

        return response()->json($surveyQuestionRule);
    }

    /**
     * حذف سجل من (Survey Question Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyQuestionRule $surveyQuestionRule)
    {
        $surveyQuestionRule->delete();
        return response()->json(['message' => 'Rule deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Question Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $rule = SurveyQuestionRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Rule restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Question Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $rule = SurveyQuestionRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Rule permanently deleted']);
    }
}
