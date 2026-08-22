<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleCashTransactionController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Cash Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Cash Transaction" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleCashTransaction;
use Illuminate\Http\Request;

class VehicleCashTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Cash Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleCashTransaction::with(['cashAccount']);

        if ($request->filled('vehicle_cash_account_id')) {
            $query->where('vehicle_cash_account_id', $request->vehicle_cash_account_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('transaction_date_from')) {
            $query->where('transaction_date', '>=', $request->transaction_date_from);
        }

        if ($request->filled('transaction_date_to')) {
            $query->where('transaction_date', '<=', $request->transaction_date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Cash Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_cash_account_id' => 'required',
            'transaction_type' => 'required|in:COLLECTION,EXPENSE,DEPOSIT,SETTLEMENT',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $transaction = VehicleCashTransaction::create($validated);

        return response()->json($transaction->load('cashAccount'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Cash Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $transaction = VehicleCashTransaction::with(['cashAccount'])->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Cash Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $transaction = VehicleCashTransaction::findOrFail($id);

        $validated = $request->validate([
            'vehicle_cash_account_id' => 'required',
            'transaction_type' => 'required|in:COLLECTION,EXPENSE,DEPOSIT,SETTLEMENT',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load('cashAccount'));
    }

    /**
     * حذف سجل من (Vehicle Cash Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $transaction = VehicleCashTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Vehicle cash transaction deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Cash Transaction) وإعادته للعمل.
     */
    public function restore($id)
    {
        $transaction = VehicleCashTransaction::withTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json($transaction->load('cashAccount'));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Cash Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $transaction = VehicleCashTransaction::withTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return response()->json(['message' => 'Vehicle cash transaction permanently deleted']);
    }
}
