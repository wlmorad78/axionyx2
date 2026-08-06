<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponentType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalaryComponentTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalaryComponentType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salary_component_type', 'store'));

        return response()->json(SalaryComponentType::create($data), 201);
    }

    public function show(SalaryComponentType $salaryComponentType)
    {
        return $salaryComponentType;
    }

    public function update(Request $request, SalaryComponentType $salaryComponentType)
    {
        $data = $request->validate(ValidationRules::for('salary_component_type', 'update', $salaryComponentType));

        $salaryComponentType->update($data);

        return response()->json($salaryComponentType);
    }

    public function destroy(SalaryComponentType $salaryComponentType)
    {
        $salaryComponentType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $salaryComponentType = SalaryComponentType::onlyTrashed()->findOrFail($id);

        $salaryComponentType->restore();

        return response()->json($salaryComponentType);
    }

    public function forceDelete(int $id)
    {
        SalaryComponentType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salary_component_type', 'store');
    }
}
