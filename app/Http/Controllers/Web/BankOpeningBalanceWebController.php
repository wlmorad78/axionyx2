<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankOpeningBalance;
use App\Models\BankAccount;
use App\Models\FiscalYear;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankOpeningBalanceWebController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContext::id();

        $query = BankOpeningBalance::with(['bankAccount', 'fiscalYear']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('bankAccount', function ($q) use ($s) {
                $q->where('bank_name', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%")
                  ->orWhere('account_number', 'like', "%$s%");
            });
        }

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        if ($request->filled('min_balance')) {
            $query->where('opening_balance', '>=', $request->min_balance);
        }

        if ($request->filled('max_balance')) {
            $query->where('opening_balance', '<=', $request->max_balance);
        }

        $openingBalances = $query->orderByDesc('id')->paginate(15);

        $bankAccounts = BankAccount::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('bank_name')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        $stats = [
            'total' => $companyId ? BankOpeningBalance::where('company_id', $companyId)->count() : BankOpeningBalance::count(),
            'with_balance' => $companyId
                ? BankOpeningBalance::where('company_id', $companyId)->where('opening_balance', '>', 0)->count()
                : BankOpeningBalance::where('opening_balance', '>', 0)->count(),
            'total_amount' => $companyId
                ? BankOpeningBalance::where('company_id', $companyId)->sum('opening_balance')
                : BankOpeningBalance::sum('opening_balance'),
        ];

        return view('bank-opening-balances.index', compact('openingBalances', 'bankAccounts', 'fiscalYears', 'stats'));
    }

    public function create(Request $request)
    {
        $companyId = CompanyContext::id();

        $bankAccounts = BankAccount::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('bank_name')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        $selectedBankAccount = $request->get('bank_account_id');

        return view('bank-opening-balances.create', compact('bankAccounts', 'fiscalYears', 'selectedBankAccount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id ?? CompanyContext::id();

        DB::transaction(function () use ($data, $companyId) {
            $openingBalance = BankOpeningBalance::create([
                'company_id' => $companyId,
                'bank_account_id' => $data['bank_account_id'],
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'opening_balance' => $data['opening_balance'],
                'notes' => $data['notes'] ?? null,
            ]);

            $bankAccount = BankAccount::find($data['bank_account_id']);
            if ($bankAccount) {
                $bankAccount->update([
                    'opening_balance' => $data['opening_balance'],
                    'current_balance' => $data['opening_balance'],
                ]);
            }
        });

        return redirect()
            ->route('bank-opening-balances.index')
            ->with('success', 'تم إنشاء الرصيد الافتتاحي للبنك بنجاح');
    }

    public function show(BankOpeningBalance $bankOpeningBalance)
    {
        $bankOpeningBalance->load(['bankAccount', 'fiscalYear']);

        return view('bank-opening-balances.show', compact('bankOpeningBalance'));
    }

    public function edit(BankOpeningBalance $bankOpeningBalance)
    {
        $companyId = CompanyContext::id();

        $bankAccounts = BankAccount::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('bank_name')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        return view('bank-opening-balances.edit', compact('bankOpeningBalance', 'bankAccounts', 'fiscalYears'));
    }

    public function update(Request $request, BankOpeningBalance $bankOpeningBalance)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($bankOpeningBalance, $data) {
            $bankOpeningBalance->update([
                'bank_account_id' => $data['bank_account_id'],
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'opening_balance' => $data['opening_balance'],
                'notes' => $data['notes'] ?? null,
            ]);

            $bankAccount = BankAccount::find($data['bank_account_id']);
            if ($bankAccount) {
                $bankAccount->update([
                    'opening_balance' => $data['opening_balance'],
                    'current_balance' => $data['opening_balance'],
                ]);
            }
        });

        return redirect()
            ->route('bank-opening-balances.show', $bankOpeningBalance->id)
            ->with('success', 'تم تحديث الرصيد الافتتاحي بنجاح');
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

        return redirect()
            ->route('bank-opening-balances.index')
            ->with('success', 'تم حذف الرصيد الافتتاحي بنجاح');
    }
}
