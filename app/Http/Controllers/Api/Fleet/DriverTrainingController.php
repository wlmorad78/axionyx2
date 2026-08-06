<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverTraining};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverTrainingController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverTraining::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('training_name', 'like', "%{$s}%")
                  ->orWhere('training_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_training', 'create'));
        $item = DriverTraining::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverTraining::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverTraining::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_training', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = DriverTraining::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function restore($id)
    {
        $item = DriverTraining::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    public function forceDelete($id)
    {
        $item = DriverTraining::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
