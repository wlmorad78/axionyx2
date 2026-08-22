<?php
/**
 * =====================================================================
 * متحكم (Controller): SurveyCategoryController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Survey Category
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Survey Category" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyCategory;
use Illuminate\Http\Request;

class SurveyCategoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Survey Category) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SurveyCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($categories);
    }

    /**
     * إنشاء سجل جديد لـ (Survey Category) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:255|unique:survey_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = SurveyCategory::create($validated);

        return response()->json($category, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Survey Category) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SurveyCategory $surveyCategory)
    {
        $surveyCategory->load('surveys');
        return response()->json($surveyCategory);
    }

    /**
     * تحديث بيانات سجل موجود من (Survey Category) بناءً على المعرّف.
     */
    public function update(Request $request, SurveyCategory $surveyCategory)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:survey_categories,code,' . $surveyCategory->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $surveyCategory->update($validated);

        return response()->json($surveyCategory);
    }

    /**
     * حذف سجل من (Survey Category) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SurveyCategory $surveyCategory)
    {
        $surveyCategory->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Survey Category) وإعادته للعمل.
     */
    public function restore($id)
    {
        $category = SurveyCategory::withTrashed()->findOrFail($id);
        $category->restore();
        return response()->json(['message' => 'Category restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Survey Category) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $category = SurveyCategory::withTrashed()->findOrFail($id);
        $category->forceDelete();
        return response()->json(['message' => 'Category permanently deleted']);
    }
}
