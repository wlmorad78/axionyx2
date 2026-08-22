<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryCustodyTransactionController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Custody Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Custody Transaction" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCustodyTransaction;
use Illuminate\Http\Request;

class TreasuryCustodyTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Custody Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryCustodyTransaction::with(['custody']);

        if ($request->filled('treasury_custody_id')) {
            $query->where('treasury_custody_id', $request->treasury_custody_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Custody Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_custody_id' => 'required|exists:treasury_custodies,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:ISSUE,RETURN,SETTLEMENT,ADJUSTMENT',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $transaction = TreasuryCustodyTransaction::create($validated);

        return response()->json($transaction->load('custody'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Custody Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $transaction = TreasuryCustodyTransaction::with(['custody'])->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Custody Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $transaction = TreasuryCustodyTransaction::findOrFail($id);

        $validated = $request->validate([
            'treasury_custody_id' => 'required|exists:treasury_custodies,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:ISSUE,RETURN,SETTLEMENT,ADJUSTMENT',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load('custody'));
    }

    /**
     * حذف سجل من (Treasury Custody Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $transaction = TreasuryCustodyTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Treasury custody transaction deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Custody Transaction) وإعادته للعمل.
     */
    public function restore($id)
    {
        $transaction = TreasuryCustodyTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json($transaction->load('custody'));
    }

    /**
     * حذف نهائي للسجل من (Treasury Custody Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $transaction = TreasuryCustodyTransaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return response()->json(['message' => 'Treasury custody transaction permanently deleted']);
    }
}
