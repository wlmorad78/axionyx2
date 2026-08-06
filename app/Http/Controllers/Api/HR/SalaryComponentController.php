<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalaryComponent::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->salary_component_type_id) {
            $query->where('salary_component_type_id', $request->salary_component_type_id);
        }

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
        $data = $request->validate(ValidationRules::for('salary_component', 'store'));

        return response()->json(SalaryComponent::create($data), 201);
    }

    public function show(SalaryComponent $salaryComponent)
    {
        return $salaryComponent->load(['company', 'salaryComponentType']);
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $data = $request->validate(ValidationRules::for('salary_component', 'update', $salaryComponent));

        $salaryComponent->update($data);

        return response()->json($salaryComponent);
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $salaryComponent = SalaryComponent::onlyTrashed()->findOrFail($id);

        $salaryComponent->restore();

        return response()->json($salaryComponent);
    }

    public function forceDelete(int $id)
    {
        SalaryComponent::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salary_component', 'store');
    }
}
