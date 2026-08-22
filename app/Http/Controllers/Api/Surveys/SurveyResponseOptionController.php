<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyResponseOptionController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Response Option
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Response Option" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponseOption;
use Illuminate\Http\Request;

class SurveyResponseOptionController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Response Option) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyResponseOption::with(['answer', 'option']);

        if ($request->has('survey_response_answer_id')) {
            $query->where('survey_response_answer_id', $request->survey_response_answer_id);
        }

        $options = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($options);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Response Option) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_answer_id' => 'required|exists:survey_response_answers,id',
            'survey_question_option_id' => 'required|exists:survey_question_options,id',
        ]);

        $option = SurveyResponseOption::create($validated);

        return response()->json($option, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Response Option) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyResponseOption $surveyResponseOption)
    {
        $surveyResponseOption->load(['answer', 'option']);
        return response()->json($surveyResponseOption);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Response Option) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyResponseOption $surveyResponseOption)
    {
        $validated = $request->validate([
            'survey_question_option_id' => 'sometimes|exists:survey_question_options,id',
        ]);

        $surveyResponseOption->update($validated);

        return response()->json($surveyResponseOption);
    }

    /**
     * حذف سجل من (Survey Response Option) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyResponseOption $surveyResponseOption)
    {
        $surveyResponseOption->delete();
        return response()->json(['message' => 'Response option deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Response Option) وإعادته للعمل.
     */
    public function restore($id)
    {
        $option = SurveyResponseOption::withTrashed()->findOrFail($id);
        $option->restore();
        return response()->json(['message' => 'Response option restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Response Option) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $option = SurveyResponseOption::withTrashed()->findOrFail($id);
        $option->forceDelete();
        return response()->json(['message' => 'Response option permanently deleted']);
    }
}
