<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverLicense};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverLicenseController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverLicense::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('license_number', 'like', "%{$s}%")
                  ->orWhere('license_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_license', 'create'));
        $item = DriverLicense::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverLicense::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverLicense::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_license', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = DriverLicense::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function restore($id)
    {
        $item = DriverLicense::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    public function forceDelete($id)
    {
        $item = DriverLicense::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
