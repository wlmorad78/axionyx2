<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Company\Branch;
use App\Models\Accounting\Account;
use App\Models\Settings\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountWebController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::with(['company', 'currency'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('bank_name', 'like', "%$s%")
                  ->orWhere('account_number', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $bankAccounts = $query->paginate(15);

        return view('bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('name')->get();

        return view('bank-accounts.create', compact('branches', 'accounts', 'currencies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_no' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20',
            'currency_id' => 'nullable|exists:currencies,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['is_active'] = $request->boolean('is_active', true);

        $bankAccount = BankAccount::create($data);

        return redirect()
            ->route('bank-accounts.show', $bankAccount->id)
            ->with('success', "تم إنشاء حساب البنك {$bankAccount->bank_name} بنجاح");
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load(['company', 'currency', 'branch', 'account']);

        return view('bank-accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('name')->get();

        return view('bank-accounts.edit', compact('bankAccount', 'branches', 'accounts', 'currencies'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_no' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20',
            'currency_id' => 'nullable|exists:currencies,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $bankAccount->update($data);

        return redirect()
            ->route('bank-accounts.show', $bankAccount->id)
            ->with('success', 'تم تحديث حساب البنك بنجاح');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'تم حذف حساب البنك بنجاح');
    }
}
