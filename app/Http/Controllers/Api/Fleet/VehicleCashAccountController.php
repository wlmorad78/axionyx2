<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleCashAccountController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Cash Account
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Cash Account" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleCashAccount;
use Illuminate\Http\Request;

class VehicleCashAccountController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Cash Account) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleCashAccount::with(['vehicle', 'treasury', 'transactions']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $accounts = $query->paginate($request->get('per_page', 15));

        return response()->json($accounts);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Cash Account) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'treasury_id' => 'required',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
        ]);

        $account = VehicleCashAccount::create($validated);

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Cash Account) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $account = VehicleCashAccount::with(['vehicle', 'treasury', 'transactions'])->findOrFail($id);

        return response()->json($account);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Cash Account) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $account = VehicleCashAccount::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'treasury_id' => 'required',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
        ]);

        $account->update($validated);

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']));
    }

    /**
     * حذف سجل من (Vehicle Cash Account) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $account = VehicleCashAccount::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Vehicle cash account deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Cash Account) وإعادته للعمل.
     */
    public function restore($id)
    {
        $account = VehicleCashAccount::withTrashed()->findOrFail($id);
        $account->restore();

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Cash Account) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $account = VehicleCashAccount::withTrashed()->findOrFail($id);
        $account->forceDelete();

        return response()->json(['message' => 'Vehicle cash account permanently deleted']);
    }
}
