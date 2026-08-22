<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryShiftTransactionController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Shift Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Shift Transaction" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryShiftTransaction;
use Illuminate\Http\Request;

class TreasuryShiftTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Shift Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['shift'];
        $query = TreasuryShiftTransaction::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_shift_id) {
            $query->where('treasury_shift_id', $request->treasury_shift_id);
        }
        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Shift Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'transaction_type' => 'required|in:RECEIPT,PAYMENT,DEPOSIT,WITHDRAWAL,TRANSFER,ADJUSTMENT',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable',
            'amount' => 'required|numeric',
            'transaction_datetime' => 'nullable|date',
            'notes' => 'nullable',
        ]);

        $transaction = TreasuryShiftTransaction::create($data);
        return response()->json($transaction, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Shift Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $transaction = TreasuryShiftTransaction::with(['shift'])->findOrFail($id);
        return response()->json($transaction);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Shift Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $transaction = TreasuryShiftTransaction::findOrFail($id);

        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'transaction_type' => 'required|in:RECEIPT,PAYMENT,DEPOSIT,WITHDRAWAL,TRANSFER,ADJUSTMENT',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable',
            'amount' => 'required|numeric',
            'transaction_datetime' => 'nullable|date',
            'notes' => 'nullable',
        ]);

        $transaction->update($data);
        return response()->json($transaction);
    }

    /**
     * حذف سجل من (Treasury Shift Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $transaction = TreasuryShiftTransaction::findOrFail($id);
        $transaction->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Shift Transaction) وإعادته للعمل.
     */
    public function restore($id)
    {
        $transaction = TreasuryShiftTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();
        return response()->json($transaction);
    }

    /**
     * حذف نهائي للسجل من (Treasury Shift Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $transaction = TreasuryShiftTransaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();
        return response()->json(null, 204);
    }
}
