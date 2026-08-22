<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryCountController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Count
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Count" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCount;
use Illuminate\Http\Request;

class TreasuryCountController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Count) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['details', 'countedByEmployee'];
        $query = TreasuryCount::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_shift_id) {
            $query->where('treasury_shift_id', $request->treasury_shift_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Count) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'count_no' => 'required|unique:treasury_counts,count_no',
            'count_date' => 'required|date',
            'counted_by' => 'nullable',
            'expected_amount' => 'nullable|numeric',
            'actual_amount' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $count = TreasuryCount::create($data);
        return response()->json($count, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Count) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $count = TreasuryCount::with(['details', 'countedByEmployee'])->findOrFail($id);
        return response()->json($count);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Count) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $count = TreasuryCount::findOrFail($id);

        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'count_no' => 'required|unique:treasury_counts,count_no,' . $count->id,
            'count_date' => 'required|date',
            'counted_by' => 'nullable',
            'expected_amount' => 'nullable|numeric',
            'actual_amount' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $count->update($data);
        return response()->json($count);
    }

    /**
     * حذف سجل من (Treasury Count) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $count = TreasuryCount::findOrFail($id);
        $count->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Count) وإعادته للعمل.
     */
    public function restore($id)
    {
        $count = TreasuryCount::onlyTrashed()->findOrFail($id);
        $count->restore();
        return response()->json($count);
    }

    /**
     * حذف نهائي للسجل من (Treasury Count) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $count = TreasuryCount::onlyTrashed()->findOrFail($id);
        $count->forceDelete();
        return response()->json(null, 204);
    }
}
