<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ContractType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('contract_type', 'store'));

        return response()->json(ContractType::create($data), 201);
    }

    public function show(ContractType $contractType)
    {
        return $contractType;
    }

    public function update(Request $request, ContractType $contractType)
    {
        $data = $request->validate(ValidationRules::for('contract_type', 'update', $contractType));

        $contractType->update($data);

        return response()->json($contractType);
    }

    public function destroy(ContractType $contractType)
    {
        if ($contractType->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $contractType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);
        $contractType->restore();

        return response()->json($contractType);
    }

    public function forceDelete(int $id)
    {
        ContractType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('contract_type', 'store');
    }
}
