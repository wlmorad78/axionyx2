<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\ShiftType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShiftType::with($with);

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
        $data = $request->validate(ValidationRules::for('shift_type', 'store'));

        return response()->json(ShiftType::create($data), 201);
    }

    public function show(ShiftType $shiftType)
    {
        return $shiftType;
    }

    public function update(Request $request, ShiftType $shiftType)
    {
        $data = $request->validate(ValidationRules::for('shift_type', 'update', $shiftType));

        $shiftType->update($data);

        return response()->json($shiftType);
    }

    public function destroy(ShiftType $shiftType)
    {
        if ($shiftType->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $shiftType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $shiftType = ShiftType::onlyTrashed()->findOrFail($id);
        $shiftType->restore();

        return response()->json($shiftType);
    }

    public function forceDelete(int $id)
    {
        ShiftType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('shift_type', 'store');
    }
}
