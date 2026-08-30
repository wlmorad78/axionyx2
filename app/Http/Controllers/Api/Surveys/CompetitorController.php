<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\Competitor;
use Illuminate\Http\Request;

class CompetitorController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Competitor::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('competitor_code', 'like', "%$s%")
                    ->orWhere('competitor_name', 'like', "%$s%")
                    ->orWhere('contact_person', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'competitor_code' => 'required|string|max:255',
            'competitor_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Competitor::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Competitor $competitor)
    {
        return $competitor->load(['company', 'brands', 'products', 'promotions', 'newProducts', 'photos']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor) بناءً على المعرّف.
     */
    public function update(Request $request, Competitor $competitor)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'competitor_code' => 'sometimes|required|string|max:255',
            'competitor_name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitor->update($data);
        return response()->json($competitor);
    }

    /**
     * حذف سجل من (Competitor) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Competitor $competitor)
    {
        $competitor->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = Competitor::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Competitor::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
