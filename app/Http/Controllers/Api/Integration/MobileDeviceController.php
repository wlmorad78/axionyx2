<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MobileDevice};
use App\Support\ValidationRules;

class MobileDeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = MobileDevice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('device_uuid', 'like', "%{$s}%")
                  ->orWhere('device_name', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('mobile_device', 'create'));
        $mobileDevice = MobileDevice::create($data);
        return response()->json($mobileDevice, 201);
    }

    public function show($id)
    {
        return MobileDevice::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $mobileDevice = MobileDevice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('mobile_device', 'update', $mobileDevice));
        $mobileDevice->update($data);
        return $mobileDevice;
    }

    public function destroy($id)
    {
        $mobileDevice = MobileDevice::findOrFail($id);
        $mobileDevice->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $mobileDevice = MobileDevice::withTrashed()->findOrFail($id);
        $mobileDevice->restore();
        return $mobileDevice;
    }

    public function forceDelete($id)
    {
        $mobileDevice = MobileDevice::withTrashed()->findOrFail($id);
        $mobileDevice->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
