<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryAdjustmentController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Adjustment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Adjustment" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryAdjustment;
use Illuminate\Http\Request;

class TreasuryAdjustmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Adjustment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['treasury'];
        $query = TreasuryAdjustment::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_id) {
            $query->where('treasury_id', $request->treasury_id);
        }
        if ($request->adjustment_type) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Adjustment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_id' => 'required',
            'adjustment_no' => 'required|unique:treasury_adjustments,adjustment_no',
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|in:SHORTAGE,OVERAGE,CORRECTION',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable',
            'notes' => 'nullable',
        ]);

        $adjustment = TreasuryAdjustment::create($data);
        return response()->json($adjustment, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Adjustment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $adjustment = TreasuryAdjustment::with(['treasury'])->findOrFail($id);
        return response()->json($adjustment);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Adjustment) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $adjustment = TreasuryAdjustment::findOrFail($id);

        $data = $request->validate([
            'treasury_id' => 'required',
            'adjustment_no' => 'required|unique:treasury_adjustments,adjustment_no,' . $adjustment->id,
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|in:SHORTAGE,OVERAGE,CORRECTION',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable',
            'notes' => 'nullable',
        ]);

        $adjustment->update($data);
        return response()->json($adjustment);
    }

    /**
     * حذف سجل من (Treasury Adjustment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $adjustment = TreasuryAdjustment::findOrFail($id);
        $adjustment->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Adjustment) وإعادته للعمل.
     */
    public function restore($id)
    {
        $adjustment = TreasuryAdjustment::onlyTrashed()->findOrFail($id);
        $adjustment->restore();
        return response()->json($adjustment);
    }

    /**
     * حذف نهائي للسجل من (Treasury Adjustment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $adjustment = TreasuryAdjustment::onlyTrashed()->findOrFail($id);
        $adjustment->forceDelete();
        return response()->json(null, 204);
    }
}
