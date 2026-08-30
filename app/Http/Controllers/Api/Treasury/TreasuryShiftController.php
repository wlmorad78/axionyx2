<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryShiftController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Shift
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Shift" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryShift;
use Illuminate\Http\Request;

class TreasuryShiftController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Shift) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['treasury', 'cashier'];
        $query = TreasuryShift::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->treasury_id) {
            $query->where('treasury_id', $request->treasury_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->cashier_id) {
            $query->where('cashier_id', $request->cashier_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Shift) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'treasury_id' => 'required',
            'shift_no' => 'required|unique:treasury_shifts,shift_no',
            'cashier_id' => 'nullable',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'opening_balance' => 'nullable|numeric',
            'closing_balance' => 'nullable|numeric',
            'actual_balance' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'status' => 'required|in:OPEN,PENDING_APPROVAL,CLOSED,CANCELLED',
        ]);

        $treasuryShift = TreasuryShift::create($data);
        return response()->json($treasuryShift, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Shift) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $treasuryShift = TreasuryShift::with(['treasury', 'cashier', 'transactions'])->findOrFail($id);
        return response()->json($treasuryShift);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Shift) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $treasuryShift = TreasuryShift::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'treasury_id' => 'required',
            'shift_no' => 'required|unique:treasury_shifts,shift_no,' . $treasuryShift->id,
            'cashier_id' => 'nullable',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'opening_balance' => 'nullable|numeric',
            'closing_balance' => 'nullable|numeric',
            'actual_balance' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'status' => 'required|in:OPEN,PENDING_APPROVAL,CLOSED,CANCELLED',
        ]);

        $treasuryShift->update($data);
        return response()->json($treasuryShift);
    }

    /**
     * حذف سجل من (Treasury Shift) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $treasuryShift = TreasuryShift::findOrFail($id);
        $treasuryShift->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Shift) وإعادته للعمل.
     */
    public function restore($id)
    {
        $treasuryShift = TreasuryShift::onlyTrashed()->findOrFail($id);
        $treasuryShift->restore();
        return response()->json($treasuryShift);
    }

    /**
     * حذف نهائي للسجل من (Treasury Shift) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $treasuryShift = TreasuryShift::onlyTrashed()->findOrFail($id);
        $treasuryShift->forceDelete();
        return response()->json(null, 204);
    }
}
