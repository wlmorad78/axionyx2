<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Driver;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('license_no', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver', 'create'));
        $driver = Driver::create($data);
        return response()->json($driver, 201);
    }

    public function show($id)
    {
        return Driver::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver', 'update', $driver));
        $driver->update($data);
        return $driver;
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $driver = Driver::withTrashed()->findOrFail($id);
        $driver->restore();
        return $driver;
    }

    public function forceDelete($id)
    {
        $driver = Driver::withTrashed()->findOrFail($id);
        $driver->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
