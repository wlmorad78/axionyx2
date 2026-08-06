<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class TreasuryTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = TreasuryType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('treasury_type', 'store'));
        $treasuryType = TreasuryType::create($data);
        return response()->json($treasuryType, 201);
    }

    public function show(TreasuryType $treasuryType)
    {
        return $treasuryType;
    }

    public function update(Request $request, TreasuryType $treasuryType)
    {
        $data = $request->validate(ValidationRules::for('treasury_type', 'update', $treasuryType));
        $treasuryType->update($data);
        return response()->json($treasuryType);
    }

    public function destroy(TreasuryType $treasuryType)
    {
        if ($treasuryType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع نظام'], 403);
        }
        $treasuryType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $treasuryType = TreasuryType::onlyTrashed()->findOrFail($id);
        $treasuryType->restore();
        return response()->json($treasuryType);
    }

    public function forceDelete(int $id)
    {
        $treasuryType = TreasuryType::onlyTrashed()->findOrFail($id);
        $treasuryType->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('treasury_type', 'store');
    }
}
