<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\OpeningBalance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OpeningBalance::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->accounting_period_id) $query->where('accounting_period_id', $request->accounting_period_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opening_balance', 'store'));
        return response()->json(OpeningBalance::create($data), 201);
    }

    public function show(OpeningBalance $openingBalance)
    {
        return $openingBalance->load(['account', 'company', 'branch', 'fiscalYear', 'accountingPeriod', 'createdByEmployee']);
    }

    public function update(Request $request, OpeningBalance $openingBalance)
    {
        $data = $request->validate(ValidationRules::for('opening_balance', 'update', $openingBalance));
        $openingBalance->update($data);
        return response()->json($openingBalance);
    }

    public function destroy(OpeningBalance $openingBalance)
    {
        $openingBalance->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = OpeningBalance::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        OpeningBalance::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('opening_balance', 'store');
    }
}
