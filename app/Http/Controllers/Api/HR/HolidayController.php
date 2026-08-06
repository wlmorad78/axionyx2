<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Holiday::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('holiday', 'store'));

        return response()->json(Holiday::create($data), 201);
    }

    public function show(Holiday $holiday)
    {
        return $holiday->load(['company']);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate(ValidationRules::for('holiday', 'update', $holiday));

        $holiday->update($data);

        return response()->json($holiday);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $holiday = Holiday::onlyTrashed()->findOrFail($id);
        $holiday->restore();

        return response()->json($holiday);
    }

    public function forceDelete(int $id)
    {
        Holiday::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('holiday', 'store');
    }
}
