<?php
/**
 * =====================================================================
 * متحكم (Controller): OwnerAccountController
 * الوحدة (Module): حساب المالك (Owner Account)
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة حساب المالك الموحد. يوفر عمليات إيداع وسحب الأموال
 * وإرسال وسحب البضائع مع القيود اليومية التلقائية.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\OwnerAccount;

use App\Http\Controllers\Controller;
use App\Models\OwnerTransaction;
use App\Services\OwnerAccountService;
use Illuminate\Http\Request;

class OwnerAccountController extends Controller
{
    /**
     * عرض قائمة حركات المالك مع دعم الفلترة والبحث والصفحات.
     */
    public function index(Request $request)
    {
        $query = OwnerTransaction::with(['branch', 'treasury', 'warehouse', 'item', 'createdBy']);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->date_from) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 15);
    }

    /**
     * إيداع أموال من المالك للخزينة
     */
    public function depositCash(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'treasury_id' => ['required', 'exists:treasuries,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $transaction = OwnerAccountService::depositCash(
            $data['company_id'],
            $data['branch_id'],
            $data['treasury_id'],
            $data['amount'],
            $data['description'] ?? 'إيداع من المالك',
            null,
            $data['transaction_date'] ?? now()->toDateString()
        );

        return response()->json([
            'message' => 'تم إيداع الأموال بنجاح',
            'transaction' => $transaction->load(['branch', 'treasury']),
        ], 201);
    }

    /**
     * سحب أموال من الخزينة للمالك
     */
    public function withdrawCash(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'treasury_id' => ['required', 'exists:treasuries,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $transaction = OwnerAccountService::withdrawCash(
            $data['company_id'],
            $data['branch_id'],
            $data['treasury_id'],
            $data['amount'],
            $data['description'] ?? 'سحب من المالك',
            null,
            $data['transaction_date'] ?? now()->toDateString()
        );

        return response()->json([
            'message' => 'تم سحب الأموال بنجاح',
            'transaction' => $transaction->load(['branch', 'treasury']),
        ], 201);
    }

    /**
     * إرسال بضاعة من محل للمخزن
     */
    public function dispatchGoods(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $transaction = OwnerAccountService::dispatchGoods(
            $data['company_id'],
            $data['branch_id'],
            $data['from_warehouse_id'],
            $data['to_warehouse_id'],
            $data['item_id'],
            $data['quantity'],
            $data['unit_cost'],
            $data['description'] ?? 'إرسال بضاعة من المالك',
            null,
            $data['transaction_date'] ?? now()->toDateString()
        );

        return response()->json([
            'message' => 'تم إرسال البضاعة بنجاح',
            'transaction' => $transaction->load(['branch', 'warehouse', 'item']),
        ], 201);
    }

    /**
     * سحب بضاعة من المخزن لمحل
     */
    public function receiveGoods(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $transaction = OwnerAccountService::receiveGoods(
            $data['company_id'],
            $data['branch_id'],
            $data['from_warehouse_id'],
            $data['to_warehouse_id'],
            $data['item_id'],
            $data['quantity'],
            $data['unit_cost'],
            $data['description'] ?? 'سحب بضاعة للمالك',
            null,
            $data['transaction_date'] ?? now()->toDateString()
        );

        return response()->json([
            'message' => 'تم سحب البضاعة بنجاح',
            'transaction' => $transaction->load(['branch', 'warehouse', 'item']),
        ], 201);
    }

    /**
     * عرض كشف حساب المالك
     */
    public function statement(Request $request)
    {
        $statement = OwnerAccountService::getStatement(
            $request->company_id,
            $request->branch_id,
            $request->date_from,
            $request->date_to
        );

        return response()->json($statement);
    }

    /**
     * عرض رصيد المالك الإجمالي
     */
    public function balance(Request $request)
    {
        $balance = OwnerAccountService::getBalance($request->company_id);

        return response()->json($balance);
    }

    /**
     * عرض تفاصيل حركة محددة
     */
    public function show($id)
    {
        $transaction = OwnerTransaction::with(['branch', 'treasury', 'warehouse', 'item', 'createdBy'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * حذف حركة (Soft Delete)
     */
    public function destroy($id)
    {
        $transaction = OwnerTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json([
            'message' => 'تم حذف الحركة بنجاح',
        ]);
    }

    /**
     * استعادة حركة محذوفة
     */
    public function restore($id)
    {
        $transaction = OwnerTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json([
            'message' => 'تم استعادة الحركة بنجاح',
            'transaction' => $transaction,
        ]);
    }
}
