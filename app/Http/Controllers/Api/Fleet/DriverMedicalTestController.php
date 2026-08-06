<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverMedicalTest};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverMedicalTestController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverMedicalTest::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('test_type', 'like', "%{$s}%")
                  ->orWhere('doctor_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_medical_test', 'create'));
        $item = DriverMedicalTest::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverMedicalTest::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverMedicalTest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_medical_test', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = DriverMedicalTest::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function restore($id)
    {
        $item = DriverMedicalTest::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    public function forceDelete($id)
    {
        $item = DriverMedicalTest::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
