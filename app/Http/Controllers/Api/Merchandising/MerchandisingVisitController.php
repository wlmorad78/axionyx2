<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingVisitController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Visit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Visit" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingVisit;
use Illuminate\Http\Request;

class MerchandisingVisitController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Visit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingVisit::with(['salesRep', 'customer']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('visit_date', 'like', "%{$s}%")
                    ->orWhere('overall_score', 'like', "%{$s}%");
            });
        }

        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('sales_rep_id')) $query->where('sales_rep_id', $request->sales_rep_id);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Visit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'sales_rep_id' => 'required|exists:employees,id',
            'customer_id' => 'required|exists:customers,id',
            'visit_date' => 'required|date',
            'visit_time' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'overall_score' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $visit = MerchandisingVisit::create($data);
        return response()->json($visit, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Visit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MerchandisingVisit::with(['salesRep', 'customer', 'details.checklist', 'photos'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Visit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $visit = MerchandisingVisit::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'visit_date' => 'sometimes|required|date',
            'visit_time' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'overall_score' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $visit->update($data);
        return $visit;
    }

    /**
     * حذف سجل من (Merchandising Visit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $visit = MerchandisingVisit::findOrFail($id);
        $visit->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Visit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $visit = MerchandisingVisit::withTrashed()->findOrFail($id);
        $visit->restore();
        return $visit;
    }

    /**
     * حذف نهائي للسجل من (Merchandising Visit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $visit = MerchandisingVisit::withTrashed()->findOrFail($id);
        $visit->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
