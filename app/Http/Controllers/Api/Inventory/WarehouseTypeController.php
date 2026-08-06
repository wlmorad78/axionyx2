<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse_type', 'store'));
        $warehouseType = WarehouseType::create($data);

        return response()->json($warehouseType, 201);
    }

    public function show(WarehouseType $warehouseType)
    {
        return $warehouseType;
    }

    public function update(Request $request, WarehouseType $warehouseType)
    {
        $data = $request->validate(ValidationRules::for('warehouse_type', 'update', $warehouseType));
        $warehouseType->update($data);

        return response()->json($warehouseType);
    }

    public function destroy(WarehouseType $warehouseType)
    {
        $warehouseType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $warehouseType = WarehouseType::onlyTrashed()->findOrFail($id);
        $warehouseType->restore();

        return response()->json($warehouseType);
    }

    public function forceDelete(int $id)
    {
        $warehouseType = WarehouseType::onlyTrashed()->findOrFail($id);
        $warehouseType->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('warehouse_type', 'store');
    }
}
