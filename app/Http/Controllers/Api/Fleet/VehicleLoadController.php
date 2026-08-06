<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoad;
use Illuminate\Http\Request;

class VehicleLoadController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleLoad::with(['items', 'vehicle', 'loadRequest']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('load_date_from')) {
            $query->where('load_date', '>=', $request->load_date_from);
        }
        if ($request->filled('load_date_to')) {
            $query->where('load_date', '<=', $request->load_date_to);
        }

        $loads = $query->paginate($request->get('per_page', 15));

        return response()->json($loads);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'required',
            'load_request_id' => 'nullable',
            'issue_order_id' => 'nullable',
            'load_no' => 'required|unique:vehicle_loads,load_no',
            'load_date' => 'required|date',
            'loaded_value' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'created_by' => 'nullable',
        ]);

        $load = VehicleLoad::create($validated);

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']), 201);
    }

    public function show($id)
    {
        $load = VehicleLoad::with(['items', 'vehicle', 'loadRequest'])->findOrFail($id);

        return response()->json($load);
    }

    public function update(Request $request, $id)
    {
        $load = VehicleLoad::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required',
            'vehicle_id' => 'sometimes|required',
            'load_request_id' => 'nullable',
            'issue_order_id' => 'nullable',
            'load_no' => 'sometimes|required|unique:vehicle_loads,load_no,' . $id,
            'load_date' => 'sometimes|required|date',
            'loaded_value' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'created_by' => 'nullable',
        ]);

        $load->update($validated);

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']));
    }

    public function destroy($id)
    {
        $load = VehicleLoad::findOrFail($id);
        $load->delete();

        return response()->json(['message' => 'Vehicle load deleted successfully']);
    }

    public function restore($id)
    {
        $load = VehicleLoad::onlyTrashed()->findOrFail($id);
        $load->restore();

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']));
    }

    public function forceDelete($id)
    {
        $load = VehicleLoad::onlyTrashed()->findOrFail($id);
        $load->forceDelete();

        return response()->json(['message' => 'Vehicle load permanently deleted']);
    }
}
