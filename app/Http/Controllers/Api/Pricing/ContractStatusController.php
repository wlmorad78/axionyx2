<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ContractStatus;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ContractStatusController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ContractStatus::with($with);

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
        $data = $request->validate(ValidationRules::for('contract_status', 'store'));

        return response()->json(ContractStatus::create($data), 201);
    }

    public function show(ContractStatus $contractStatus)
    {
        return $contractStatus;
    }

    public function update(Request $request, ContractStatus $contractStatus)
    {
        $data = $request->validate(ValidationRules::for('contract_status', 'update', $contractStatus));

        $contractStatus->update($data);

        return response()->json($contractStatus);
    }

    public function destroy(ContractStatus $contractStatus)
    {
        if ($contractStatus->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $contractStatus->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $contractStatus = ContractStatus::onlyTrashed()->findOrFail($id);
        $contractStatus->restore();

        return response()->json($contractStatus);
    }

    public function forceDelete(int $id)
    {
        ContractStatus::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('contract_status', 'store');
    }
}
