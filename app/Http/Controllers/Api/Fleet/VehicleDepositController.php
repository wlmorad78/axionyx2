<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleDepositController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Deposit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Deposit" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDeposit;
use Illuminate\Http\Request;

class VehicleDepositController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Deposit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleDeposit::with(['vehicle', 'treasury', 'bankAccount']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('deposit_date_from')) {
            $query->where('deposit_date', '>=', $request->deposit_date_from);
        }

        if ($request->filled('deposit_date_to')) {
            $query->where('deposit_date', '<=', $request->deposit_date_to);
        }

        $deposits = $query->paginate($request->get('per_page', 15));

        return response()->json($deposits);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Deposit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'deposit_no' => 'required|unique:vehicle_deposits,deposit_no',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric',
            'treasury_id' => 'nullable',
            'bank_account_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $deposit = VehicleDeposit::create($validated);

        return response()->json($deposit->load(['vehicle', 'treasury', 'bankAccount']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Deposit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $deposit = VehicleDeposit::with(['vehicle', 'treasury', 'bankAccount'])->findOrFail($id);

        return response()->json($deposit);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Deposit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $deposit = VehicleDeposit::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'deposit_no' => 'required|unique:vehicle_deposits,deposit_no,' . $id,
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric',
            'treasury_id' => 'nullable',
            'bank_account_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $deposit->update($validated);

        return response()->json($deposit->load(['vehicle', 'treasury', 'bankAccount']));
    }

    /**
     * حذف سجل من (Vehicle Deposit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $deposit = VehicleDeposit::findOrFail($id);
        $deposit->delete();

        return response()->json(['message' => 'Vehicle deposit deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Deposit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $deposit = VehicleDeposit::withTrashed()->findOrFail($id);
        $deposit->restore();

        return response()->json($deposit->load(['vehicle', 'treasury', 'bankAccount']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Deposit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $deposit = VehicleDeposit::withTrashed()->findOrFail($id);
        $deposit->forceDelete();

        return response()->json(['message' => 'Vehicle deposit permanently deleted']);
    }
}
