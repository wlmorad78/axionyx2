<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesmanAccountController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Salesman Account
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salesman Account" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesmanAccount;
use App\Models\Sales\SalesmanAccountMovement;
use App\Models\Sales\SalesmanDebt;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanAccountController extends Controller
{
    /**
     * عرض قائمة سجلات (Salesman Account) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanAccount::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('account_code', 'like', "%$s%")
                  ->orWhereHas('salesman', fn($q2) => $q2->where('first_name_ar', 'like', "%$s%")
                                                      ->orWhere('last_name_ar', 'like', "%$s%"));
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salesman Account) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesmanAccount $salesmanAccount)
    {
        return $salesmanAccount->load([
            'salesman', 'company', 'branch',
            'movements' => fn($q) => $q->orderByDesc('movement_date')->limit(50),
            'debts' => fn($q) => $q->where('status', '!=', 'fully_paid')->orderByDesc('debt_date'),
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Salesman Account) بناءً على المعرّف.
     */
    public function update(SalesmanAccount $salesmanAccount, Request $request)
    {
        $data = $request->validate([
            'current_balance' => ['sometimes', 'numeric', 'min:0'],
            'total_debts' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $salesmanAccount->update($data);
        return response()->json($salesmanAccount->fresh());
    }

    /**
     * دالة معالجة: ledger — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Salesman Account).
     */
    public function ledger(SalesmanAccount $salesmanAccount, Request $request)
    {
        $query = SalesmanAccountMovement::where('salesman_account_id', $salesmanAccount->id);

        if ($request->from_date) {
            $query->whereDate('movement_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('movement_date', '<=', $request->to_date);
        }

        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        return $query->orderByDesc('movement_date')->paginate($request->per_page ?? 50);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salesman Account).
     */
    public function schema()
    {
        return ValidationRules::for('salesman_account', 'store');
    }
}