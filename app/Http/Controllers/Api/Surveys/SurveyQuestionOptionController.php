<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyQuestionOptionController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Question Option
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Question Option" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\Request;

class SurveyQuestionOptionController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Question Option) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyQuestionOption::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $query->where('option_text', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $options = $query->orderBy('display_order')->paginate($request->get('per_page', 15));

        return response()->json($options);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Question Option) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_question_id' => 'required|exists:survey_questions,id',
            'option_text' => 'required|string|max:255',
            'option_value' => 'required|string|max:255',
            'display_order' => 'integer',
        ]);

        $option = SurveyQuestionOption::create($validated);

        return response()->json($option, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Question Option) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyQuestionOption $surveyQuestionOption)
    {
        $surveyQuestionOption->load('question');
        return response()->json($surveyQuestionOption);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Question Option) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyQuestionOption $surveyQuestionOption)
    {
        $validated = $request->validate([
            'option_text' => 'sometimes|string|max:255',
            'option_value' => 'sometimes|string|max:255',
            'display_order' => 'integer',
        ]);

        $surveyQuestionOption->update($validated);

        return response()->json($surveyQuestionOption);
    }

    /**
     * حذف سجل من (Survey Question Option) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyQuestionOption $surveyQuestionOption)
    {
        $surveyQuestionOption->delete();
        return response()->json(['message' => 'Option deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Question Option) وإعادته للعمل.
     */
    public function restore($id)
    {
        $option = SurveyQuestionOption::withTrashed()->findOrFail($id);
        $option->restore();
        return response()->json(['message' => 'Option restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Question Option) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $option = SurveyQuestionOption::withTrashed()->findOrFail($id);
        $option->forceDelete();
        return response()->json(['message' => 'Option permanently deleted']);
    }
}
