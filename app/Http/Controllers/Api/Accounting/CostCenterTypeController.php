<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CostCenterType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CostCenterTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CostCenterType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('cost_center_type', 'store'));
        $type = CostCenterType::create($data);
        return response()->json($type, 201);
    }

    public function show(CostCenterType $costCenterType)
    {
        return $costCenterType;
    }

    public function update(Request $request, CostCenterType $costCenterType)
    {
        $data = $request->validate(ValidationRules::for('cost_center_type', 'update', $costCenterType));
        $costCenterType->update($data);
        return response()->json($costCenterType);
    }

    public function destroy(CostCenterType $costCenterType)
    {
        if ($costCenterType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع مركز تكلفة نظام'], 403);
        }
        $costCenterType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $type = CostCenterType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    public function forceDelete(int $id)
    {
        $type = CostCenterType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('cost_center_type', 'store');
    }
}
