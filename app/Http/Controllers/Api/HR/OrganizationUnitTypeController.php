<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnitType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OrganizationUnitTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OrganizationUnitType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->orderBy('sort_order')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('organization_unit_type', 'store'));
        $type = OrganizationUnitType::create($data);
        return response()->json($type, 201);
    }

    public function show(OrganizationUnitType $organizationUnitType)
    {
        return $organizationUnitType;
    }

    public function update(Request $request, OrganizationUnitType $organizationUnitType)
    {
        $data = $request->validate(ValidationRules::for('organization_unit_type', 'update', $organizationUnitType));
        $organizationUnitType->update($data);
        return response()->json($organizationUnitType);
    }

    public function destroy(OrganizationUnitType $organizationUnitType)
    {
        if ($organizationUnitType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع وحدة نظام'], 403);
        }
        $organizationUnitType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $type = OrganizationUnitType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    public function forceDelete(int $id)
    {
        $type = OrganizationUnitType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('organization_unit_type', 'store');
    }
}
