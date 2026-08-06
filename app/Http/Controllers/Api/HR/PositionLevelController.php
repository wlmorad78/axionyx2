<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PositionLevel;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PositionLevelController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PositionLevel::with($with);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderBy('sort_order')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('position_level', 'store'));
        return response()->json(PositionLevel::create($data), 201);
    }

    public function show(PositionLevel $positionLevel) { return $positionLevel->load('jobPositions'); }

    public function update(Request $request, PositionLevel $positionLevel)
    {
        $data = $request->validate(ValidationRules::for('position_level', 'update', $positionLevel));
        $positionLevel->update($data);
        return response()->json($positionLevel);
    }

    public function destroy(PositionLevel $positionLevel) { $positionLevel->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $p = PositionLevel::onlyTrashed()->findOrFail($id); $p->restore(); return response()->json($p); }
    public function forceDelete(int $id) { PositionLevel::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = PositionLevel::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/PL-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'PL-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('position_level', 'store'); }
}
