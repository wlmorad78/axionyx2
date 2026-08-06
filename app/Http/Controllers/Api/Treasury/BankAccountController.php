<?php
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\BankAccount;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = BankAccount::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('account_name', 'like', "%$s%")
                    ->orWhere('account_number', 'like', "%$s%")
                    ->orWhere('bank_name', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        $accounts = $query->orderByDesc('id')->paginate($request->per_page ?? 15);

        $ids = $accounts->pluck('id')->toArray();
        if (!empty($ids)) {
            $balances = $this->calculateBalances($ids);
            foreach ($accounts as $account) {
                if (isset($balances[$account->id])) {
                    $account->calculated_current_balance = $balances[$account->id];
                } else {
                    $account->calculated_current_balance = $account->opening_balance ?? 0;
                }
            }
        }

        return $accounts;
    }

    private function calculateBalances(array $bankAccountIds): array
    {
        $results = [];

        $treasuryTransfers = DB::table('treasury_bank_transfers')
            ->whereIn('bank_account_id', $bankAccountIds)
            ->where('status', 'completed')
            ->select('bank_account_id', 'transfer_type', DB::raw('SUM(amount) as total'))
            ->groupBy('bank_account_id', 'transfer_type')
            ->get();

        foreach ($treasuryTransfers as $row) {
            $id = $row->bank_account_id;
            if (!isset($results[$id])) $results[$id] = 0;
            if ($row->transfer_type === 'treasury_to_bank') {
                $results[$id] += $row->total;
            } else {
                $results[$id] -= $row->total;
            }
        }

        $supplierPayments = DB::table('bank_supplier_payments')
            ->whereIn('bank_account_id', $bankAccountIds)
            ->where('status', 'completed')
            ->select('bank_account_id', DB::raw('SUM(amount) as total'))
            ->groupBy('bank_account_id')
            ->get();

        foreach ($supplierPayments as $row) {
            $id = $row->bank_account_id;
            if (!isset($results[$id])) $results[$id] = 0;
            $results[$id] -= $row->total;
        }

        $openingBalances = DB::table('bank_opening_balances')
            ->whereIn('bank_account_id', $bankAccountIds)
            ->select('bank_account_id', DB::raw('MAX(opening_balance) as opening_balance'))
            ->groupBy('bank_account_id')
            ->get();

        foreach ($openingBalances as $row) {
            $id = $row->bank_account_id;
            if (!isset($results[$id])) $results[$id] = 0;
            $results[$id] += $row->opening_balance;
        }

        return $results;
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('bank_account', 'store'));
        if (empty($data['account_number']) && !empty($data['account_no'])) {
            $data['account_number'] = $data['account_no'];
        }
        if (empty($data['account_no']) && !empty($data['account_number'])) {
            $data['account_no'] = $data['account_number'];
        }
        return response()->json(BankAccount::create($data), 201);
    }

    public function show(BankAccount $bankAccount)
    {
        return $bankAccount->load(['account', 'company', 'branch', 'bankTransfers', 'bankReconciliations']);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate(ValidationRules::for('bank_account', 'update', $bankAccount));
        if (isset($data['account_no']) && empty($data['account_number'])) {
            $data['account_number'] = $data['account_no'];
        }
        $bankAccount->update($data);
        return response()->json($bankAccount);
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = BankAccount::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        BankAccount::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('bank_account', 'store');
    }
}
