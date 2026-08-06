<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankReconciliationWebController extends Controller
{
    public function index(Request $request)
    {
        $query = BankReconciliation::with(['bankAccount'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->paginate(15);

        return view('bank-reconciliations.index', compact('reconciliations'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('bank-reconciliations.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reconciliation_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'book_balance' => 'required|numeric',
            'system_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['system_balance'] = $data['system_balance'] ?? $data['book_balance'];
        $data['difference'] = $data['statement_balance'] - $data['system_balance'];
        $data['status'] = 'completed';

        $reconciliation = BankReconciliation::create($data);

        return redirect()
            ->route('bank-reconciliations.show', $reconciliation->id)
            ->with('success', 'تم إنشاء التسوية البنكية بنجاح');
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->load(['bankAccount']);

        return view('bank-reconciliations.show', compact('bankReconciliation'));
    }

    public function destroy(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->delete();

        return redirect()
            ->route('bank-reconciliations.index')
            ->with('success', 'تم حذف التسوية البنكية بنجاح');
    }
}
