<?php
/**
 * =====================================================================
 * متحكم (Controller): ShelfShareSurveyController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Shelf Share Survey
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shelf Share Survey" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfShareSurvey;
use Illuminate\Http\Request;

class ShelfShareSurveyController extends Controller
{
    /**
     * عرض قائمة سجلات (Shelf Share Survey) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShelfShareSurvey::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('survey_date', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Shelf Share Survey) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'sales_rep_id' => 'required|exists:employees,id',
            'survey_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(ShelfShareSurvey::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shelf Share Survey) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ShelfShareSurvey $shelfShareSurvey)
    {
        return $shelfShareSurvey->load(['company', 'customer', 'salesRep', 'items']);
    }

    /**
     * تحديث بيانات سجل موجود من (Shelf Share Survey) بناءً على المعرّف.
     */
    public function update(Request $request, ShelfShareSurvey $shelfShareSurvey)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'survey_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $shelfShareSurvey->update($data);
        return response()->json($shelfShareSurvey);
    }

    /**
     * حذف سجل من (Shelf Share Survey) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ShelfShareSurvey $shelfShareSurvey)
    {
        $shelfShareSurvey->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shelf Share Survey) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ShelfShareSurvey::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Shelf Share Survey) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ShelfShareSurvey::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
