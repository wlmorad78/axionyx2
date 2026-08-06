<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = FiscalYear::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
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
        $data = $request->validate(ValidationRules::for('fiscal_year', 'store'));
        return response()->json(FiscalYear::create($data), 201);
    }

    public function show(FiscalYear $fiscalYear)
    {
        return $fiscalYear->load(['company', 'accountingPeriods', 'journalEntries']);
    }

    public function update(Request $request, FiscalYear $fiscalYear)
    {
        $data = $request->validate(ValidationRules::for('fiscal_year', 'update', $fiscalYear));
        $fiscalYear->update($data);
        return response()->json($fiscalYear);
    }

    public function destroy(FiscalYear $fiscalYear)
    {
        $fiscalYear->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = FiscalYear::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        FiscalYear::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('fiscal_year', 'store');
    }
}
