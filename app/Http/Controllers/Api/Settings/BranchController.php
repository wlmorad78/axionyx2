<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Branch::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('branch', 'store'));
        $branch = Branch::create($data);
        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        return $branch->load(['company', 'country', 'governorate', 'city', 'area']);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate(ValidationRules::for('branch', 'update', $branch));
        $branch->update($data);
        return response()->json($branch);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();
        return response()->json($branch);
    }

    public function forceDelete(int $id)
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('branch', 'store');
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = Branch::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $lastCode = $query->orderByRaw("CAST(SUBSTR(code, 4) AS INTEGER) DESC")->value('code');
        if ($lastCode && preg_match('/^BR-(\d+)$/', $lastCode, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'BR-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }
}
