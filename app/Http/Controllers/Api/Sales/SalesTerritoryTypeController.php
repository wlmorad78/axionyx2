<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesTerritoryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesTerritoryTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesTerritoryType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_territory_type', 'store'));
        $type = SalesTerritoryType::create($data);
        return response()->json($type, 201);
    }

    public function show(SalesTerritoryType $salesTerritoryType)
    {
        return $salesTerritoryType;
    }

    public function update(Request $request, SalesTerritoryType $salesTerritoryType)
    {
        $data = $request->validate(ValidationRules::for('sales_territory_type', 'update', $salesTerritoryType));
        $salesTerritoryType->update($data);
        return response()->json($salesTerritoryType);
    }

    public function destroy(SalesTerritoryType $salesTerritoryType)
    {
        if ($salesTerritoryType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع منطقة مبيعات نظام'], 403);
        }
        $salesTerritoryType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $type = SalesTerritoryType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    public function forceDelete(int $id)
    {
        $type = SalesTerritoryType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_territory_type', 'store');
    }
}
