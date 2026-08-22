<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyResponsePhotoController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Response Photo
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Response Photo" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponsePhoto;
use Illuminate\Http\Request;

class SurveyResponsePhotoController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Response Photo) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyResponsePhoto::with(['response', 'question']);

        if ($request->search) {
            $query->where('file_path', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_response_id')) {
            $query->where('survey_response_id', $request->survey_response_id);
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $photos = $query->orderBy('taken_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($photos);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Response Photo) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_id' => 'required|exists:survey_responses,id',
            'survey_question_id' => 'nullable|exists:survey_questions,id',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo = SurveyResponsePhoto::create($validated);

        return response()->json($photo, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Response Photo) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyResponsePhoto $surveyResponsePhoto)
    {
        $surveyResponsePhoto->load(['response', 'question']);
        return response()->json($surveyResponsePhoto);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Response Photo) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyResponsePhoto $surveyResponsePhoto)
    {
        $validated = $request->validate([
            'survey_question_id' => 'nullable|exists:survey_questions,id',
            'file_path' => 'sometimes|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $surveyResponsePhoto->update($validated);

        return response()->json($surveyResponsePhoto);
    }

    /**
     * حذف سجل من (Survey Response Photo) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyResponsePhoto $surveyResponsePhoto)
    {
        $surveyResponsePhoto->delete();
        return response()->json(['message' => 'Photo deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Response Photo) وإعادته للعمل.
     */
    public function restore($id)
    {
        $photo = SurveyResponsePhoto::withTrashed()->findOrFail($id);
        $photo->restore();
        return response()->json(['message' => 'Photo restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Response Photo) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $photo = SurveyResponsePhoto::withTrashed()->findOrFail($id);
        $photo->forceDelete();
        return response()->json(['message' => 'Photo permanently deleted']);
    }
}
