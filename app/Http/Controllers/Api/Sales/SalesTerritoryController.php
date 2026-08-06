<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesTerritory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesTerritoryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['territoryType', 'governorate', 'branch', 'warehouse', 'treasury'];
        $query = SalesTerritory::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->sales_territory_type_id) {
            $query->where('sales_territory_type_id', $request->sales_territory_type_id);
        }
        if ($request->parent_id !== null) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_territory', 'store'));
        if (empty($data['company_id'])) {
            $data['company_id'] = $request->header('X-Company-Id') ?? $request->user()?->company_id;
        }
        $territory = SalesTerritory::create($data);
        return response()->json($territory->load(['territoryType', 'governorate', 'branch', 'warehouse', 'treasury']), 201);
    }

    public function show(SalesTerritory $salesTerritory)
    {
        return $salesTerritory->load(['company', 'branch', 'territoryType', 'governorate', 'children', 'warehouse', 'treasury']);
    }

    public function update(Request $request, SalesTerritory $salesTerritory)
    {
        $data = $request->validate(ValidationRules::for('sales_territory', 'update', $salesTerritory));
        $salesTerritory->update($data);
        return response()->json($salesTerritory);
    }

    public function destroy(SalesTerritory $salesTerritory)
    {
        $salesTerritory->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $territory = SalesTerritory::onlyTrashed()->findOrFail($id);
        $territory->restore();
        return response()->json($territory);
    }

    public function forceDelete(int $id)
    {
        $territory = SalesTerritory::onlyTrashed()->findOrFail($id);
        $territory->forceDelete();
        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $last = SalesTerritory::withTrashed()->orderBy('id', 'desc')->first();
        if ($last && preg_match('/ST-(\d+)/', $last->code, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'ST-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('sales_territory', 'store');
    }
}
