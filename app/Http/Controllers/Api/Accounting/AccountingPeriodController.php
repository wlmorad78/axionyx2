<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AccountingPeriod::with($with);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('accounting_period', 'store'));
        return response()->json(AccountingPeriod::create($data), 201);
    }

    public function show(AccountingPeriod $accountingPeriod)
    {
        return $accountingPeriod->load(['fiscalYear', 'company', 'journalEntries', 'openingBalances']);
    }

    public function update(Request $request, AccountingPeriod $accountingPeriod)
    {
        $data = $request->validate(ValidationRules::for('accounting_period', 'update', $accountingPeriod));
        $accountingPeriod->update($data);
        return response()->json($accountingPeriod);
    }

    public function destroy(AccountingPeriod $accountingPeriod)
    {
        $accountingPeriod->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = AccountingPeriod::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        AccountingPeriod::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('accounting_period', 'store');
    }
}
