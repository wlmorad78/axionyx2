<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankOpeningBalance;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankOpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = BankOpeningBalance::with(['bankAccount', 'fiscalYear']);
        $companyId = $request->company_id ?? $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if ($companyId) $query->where('company_id', $companyId);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('bankAccount', function ($q) use ($s) {
                $q->where('bank_name', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%")
                  ->orWhere('account_number', 'like', "%$s%");
            });
        }
        return $query->orderByDesc('id')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $companyId = $request->user()->company_id ?? auth()->user()->company_id;
        $data['company_id'] = $companyId;

        $record = DB::transaction(function () use ($data) {
            $record = BankOpeningBalance::create($data);

            $bankAccount = BankAccount::find($data['bank_account_id']);
            if ($bankAccount) {
                $bankAccount->update([
                    'opening_balance' => $data['opening_balance'],
                    'current_balance' => $data['opening_balance'],
                ]);
            }

            return $record;
        });

        return response()->json($record->load(['bankAccount', 'fiscalYear']), 201);
    }

    public function show(BankOpeningBalance $bankOpeningBalance)
    {
        return $bankOpeningBalance->load(['bankAccount', 'fiscalYear']);
    }

    public function update(Request $request, BankOpeningBalance $bankOpeningBalance)
    {
        $data = $request->validate([
            'bank_account_id' => 'sometimes|exists:bank_accounts,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($bankOpeningBalance, $data) {
            $bankOpeningBalance->update($data);

            $bankAccount = BankAccount::find($data['bank_account_id'] ?? $bankOpeningBalance->bank_account_id);
            if ($bankAccount) {
                $bankAccount->update([
                    'opening_balance' => $data['opening_balance'] ?? $bankOpeningBalance->opening_balance,
                    'current_balance' => $data['opening_balance'] ?? $bankOpeningBalance->opening_balance,
                ]);
            }
        });

        return response()->json($bankOpeningBalance->load(['bankAccount', 'fiscalYear']));
    }

    public function destroy(BankOpeningBalance $bankOpeningBalance)
    {
        DB::transaction(function () use ($bankOpeningBalance) {
            $bankAccount = BankAccount::find($bankOpeningBalance->bank_account_id);
            if ($bankAccount) {
                $bankAccount->update([
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]);
            }
            $bankOpeningBalance->delete();
        });

        return response()->json(null, 204);
    }
}
