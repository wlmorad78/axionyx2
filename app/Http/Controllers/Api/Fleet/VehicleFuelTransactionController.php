<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleFuelTransactionController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Fuel Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Fuel Transaction" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleFuelTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Fuel Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleFuelTransaction::with(['vehicle']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Fuel Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_transaction', 'create'));
        $vehicleFuelTransaction = VehicleFuelTransaction::create($data);
        return response()->json($vehicleFuelTransaction, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Fuel Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleFuelTransaction::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Fuel Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_transaction', 'update', $vehicleFuelTransaction));
        $vehicleFuelTransaction->update($data);
        return $vehicleFuelTransaction;
    }

    /**
     * حذف سجل من (Vehicle Fuel Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::findOrFail($id);
        $vehicleFuelTransaction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Fuel Transaction) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::withTrashed()->findOrFail($id);
        $vehicleFuelTransaction->restore();
        return $vehicleFuelTransaction;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Fuel Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::withTrashed()->findOrFail($id);
        $vehicleFuelTransaction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
