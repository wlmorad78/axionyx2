<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleExpenseController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Expense
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Expense" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\VehicleDailyExpense;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleExpenseController extends Controller
{
    /**
     * عرض قائمة سجلات المصروفات اليومية مع دعم الفلترة والبحث والصفحات.
     */
    public function index(Request $request)
    {
        $query = VehicleDailyExpense::with(['vehicle', 'creator']);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('expense_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('expense_date')) {
            $query->whereDate('expense_date', $request->expense_date);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد للمصروف اليومي.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_daily_expense', 'create'));
        $data['created_by'] = $request->user()->id;
        $expense = VehicleDailyExpense::create($data);
        return response()->json($expense, 201);
    }

    /**
     * عرض تفاصيل سجل محدد.
     */
    public function show($id)
    {
        return VehicleDailyExpense::with(['vehicle', 'creator'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود.
     */
    public function update(Request $request, $id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_daily_expense', 'update', $expense));
        $expense->update($data);
        return $expense;
    }

    /**
     * حذف سجل.
     */
    public function destroy($id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);
        $expense->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف.
     */
    public function restore($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->restore();
        return $expense;
    }

    /**
     * حذف نهائي للسجل.
     */
    public function forceDelete($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
