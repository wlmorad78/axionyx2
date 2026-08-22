<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryTransferController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Transfer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Transfer" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryTransfer;
use Illuminate\Http\Request;

class TreasuryTransferController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Transfer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['fromTreasury', 'toTreasury'];
        $query = TreasuryTransfer::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->from_treasury_id) {
            $query->where('from_treasury_id', $request->from_treasury_id);
        }
        if ($request->to_treasury_id) {
            $query->where('to_treasury_id', $request->to_treasury_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Transfer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'from_treasury_id' => 'required',
            'to_treasury_id' => 'required',
            'transfer_no' => 'required|unique:treasury_transfers,transfer_no',
            'transfer_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'notes' => 'nullable',
            'status' => 'nullable',
        ]);

        $transfer = TreasuryTransfer::create($data);
        return response()->json($transfer, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Transfer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $transfer = TreasuryTransfer::with(['fromTreasury', 'toTreasury'])->findOrFail($id);
        return response()->json($transfer);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Transfer) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $transfer = TreasuryTransfer::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'required',
            'from_treasury_id' => 'required',
            'to_treasury_id' => 'required',
            'transfer_no' => 'required|unique:treasury_transfers,transfer_no,' . $transfer->id,
            'transfer_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'notes' => 'nullable',
            'status' => 'nullable',
        ]);

        $transfer->update($data);
        return response()->json($transfer);
    }

    /**
     * حذف سجل من (Treasury Transfer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $transfer = TreasuryTransfer::findOrFail($id);
        $transfer->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Transfer) وإعادته للعمل.
     */
    public function restore($id)
    {
        $transfer = TreasuryTransfer::onlyTrashed()->findOrFail($id);
        $transfer->restore();
        return response()->json($transfer);
    }

    /**
     * حذف نهائي للسجل من (Treasury Transfer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $transfer = TreasuryTransfer::onlyTrashed()->findOrFail($id);
        $transfer->forceDelete();
        return response()->json(null, 204);
    }
}
