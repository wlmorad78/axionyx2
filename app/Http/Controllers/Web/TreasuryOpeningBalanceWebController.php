<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TreasuryOpeningBalance;
use App\Models\Treasury;
use App\Models\FiscalYear;
use App\Models\Company\Branch;
use App\Services\CompanyContext;
use App\Services\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreasuryOpeningBalanceWebController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContext::id();
        $branchId = BranchContext::id();

        $query = TreasuryOpeningBalance::with(['treasury', 'fiscalYear']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('treasury', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                  ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
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

        $treasuries = Treasury::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name_ar')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        $stats = [
            'total' => $companyId ? TreasuryOpeningBalance::where('company_id', $companyId)->count() : TreasuryOpeningBalance::count(),
            'with_balance' => $companyId
                ? TreasuryOpeningBalance::where('company_id', $companyId)->where('opening_balance', '>', 0)->count()
                : TreasuryOpeningBalance::where('opening_balance', '>', 0)->count(),
            'total_amount' => $companyId
                ? TreasuryOpeningBalance::where('company_id', $companyId)->sum('opening_balance')
                : TreasuryOpeningBalance::sum('opening_balance'),
        ];

        return view('treasury-opening-balances.index', compact('openingBalances', 'treasuries', 'fiscalYears', 'stats'));
    }

    public function create(Request $request)
    {
        $companyId = CompanyContext::id();

        $treasuries = Treasury::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name_ar')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        $selectedTreasury = $request->get('treasury_id');

        return view('treasury-opening-balances.create', compact('treasuries', 'fiscalYears', 'selectedTreasury'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $companyId = Auth::user()->company_id ?? CompanyContext::id();

        DB::transaction(function () use ($data, $companyId) {
            $openingBalance = TreasuryOpeningBalance::create([
                'company_id' => $companyId,
                'treasury_id' => $data['treasury_id'],
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'opening_balance' => $data['opening_balance'],
            ]);

            $treasury = Treasury::find($data['treasury_id']);
            if ($treasury) {
                $treasury->update(['opening_balance' => $data['opening_balance']]);
            }
        });

        return redirect()
            ->route('treasury-opening-balances.index')
            ->with('success', 'تم إنشاء الرصيد الافتتاحي للخزنة بنجاح');
    }

    public function show(TreasuryOpeningBalance $treasuryOpeningBalance)
    {
        $treasuryOpeningBalance->load(['treasury', 'fiscalYear']);

        return view('treasury-opening-balances.show', compact('treasuryOpeningBalance'));
    }

    public function edit(TreasuryOpeningBalance $treasuryOpeningBalance)
    {
        $companyId = CompanyContext::id();

        $treasuries = Treasury::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name_ar')->get();

        $fiscalYears = FiscalYear::orderByDesc('year')->get();

        return view('treasury-opening-balances.edit', compact('treasuryOpeningBalance', 'treasuries', 'fiscalYears'));
    }

    public function update(Request $request, TreasuryOpeningBalance $treasuryOpeningBalance)
    {
        $data = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($treasuryOpeningBalance, $data) {
            $treasuryOpeningBalance->update([
                'treasury_id' => $data['treasury_id'],
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'opening_balance' => $data['opening_balance'],
            ]);

            $treasury = Treasury::find($data['treasury_id']);
            if ($treasury) {
                $treasury->update(['opening_balance' => $data['opening_balance']]);
            }
        });

        return redirect()
            ->route('treasury-opening-balances.show', $treasuryOpeningBalance->id)
            ->with('success', 'تم تحديث الرصيد الافتتاحي بنجاح');
    }

    public function destroy(TreasuryOpeningBalance $treasuryOpeningBalance)
    {
        DB::transaction(function () use ($treasuryOpeningBalance) {
            $treasury = Treasury::find($treasuryOpeningBalance->treasury_id);
            if ($treasury) {
                $treasury->update(['opening_balance' => 0]);
            }

            $treasuryOpeningBalance->delete();
        });

        return redirect()
            ->route('treasury-opening-balances.index')
            ->with('success', 'تم حذف الرصيد الافتتاحي بنجاح');
    }
}
