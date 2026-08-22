<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleDailyExpenseController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Daily Expense
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Daily Expense" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDailyExpense;
use Illuminate\Http\Request;

class VehicleDailyExpenseController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Daily Expense) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleDailyExpense::with(['vehicle']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        if ($request->filled('expense_date_from')) {
            $query->where('expense_date', '>=', $request->expense_date_from);
        }

        if ($request->filled('expense_date_to')) {
            $query->where('expense_date', '<=', $request->expense_date_to);
        }

        $expenses = $query->paginate($request->get('per_page', 15));

        return response()->json($expenses);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Daily Expense) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'expense_date' => 'required|date',
            'expense_type' => 'required|in:FUEL,TOLL,MAINTENANCE,PARKING,OTHER',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $expense = VehicleDailyExpense::create($validated);

        return response()->json($expense->load('vehicle'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Daily Expense) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $expense = VehicleDailyExpense::with(['vehicle'])->findOrFail($id);

        return response()->json($expense);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Daily Expense) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'expense_date' => 'required|date',
            'expense_type' => 'required|in:FUEL,TOLL,MAINTENANCE,PARKING,OTHER',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $expense->update($validated);

        return response()->json($expense->load('vehicle'));
    }

    /**
     * حذف سجل من (Vehicle Daily Expense) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Vehicle daily expense deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Daily Expense) وإعادته للعمل.
     */
    public function restore($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->restore();

        return response()->json($expense->load('vehicle'));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Daily Expense) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->forceDelete();

        return response()->json(['message' => 'Vehicle daily expense permanently deleted']);
    }
}
