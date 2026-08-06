<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverBehaviorScore};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverBehaviorScoreController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverBehaviorScore::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_behavior_score', 'create'));
        $item = DriverBehaviorScore::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverBehaviorScore::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverBehaviorScore::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_behavior_score', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = DriverBehaviorScore::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
