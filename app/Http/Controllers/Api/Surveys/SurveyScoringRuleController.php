<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyScoringRuleController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Scoring Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Scoring Rule" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyScoringRule;
use Illuminate\Http\Request;

class SurveyScoringRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Scoring Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyScoringRule::with(['survey', 'question']);

        if ($request->search) {
            $query->where('expected_answer', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $rules = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($rules);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Scoring Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'survey_question_id' => 'required|exists:survey_questions,id',
            'expected_answer' => 'required|string',
            'score' => 'integer',
        ]);

        $rule = SurveyScoringRule::create($validated);

        return response()->json($rule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Scoring Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyScoringRule $surveyScoringRule)
    {
        $surveyScoringRule->load(['survey', 'question']);
        return response()->json($surveyScoringRule);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Scoring Rule) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyScoringRule $surveyScoringRule)
    {
        $validated = $request->validate([
            'survey_question_id' => 'sometimes|exists:survey_questions,id',
            'expected_answer' => 'sometimes|string',
            'score' => 'integer',
        ]);

        $surveyScoringRule->update($validated);

        return response()->json($surveyScoringRule);
    }

    /**
     * حذف سجل من (Survey Scoring Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyScoringRule $surveyScoringRule)
    {
        $surveyScoringRule->delete();
        return response()->json(['message' => 'Scoring rule deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Scoring Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $rule = SurveyScoringRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Scoring rule restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Scoring Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $rule = SurveyScoringRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Scoring rule permanently deleted']);
    }
}
