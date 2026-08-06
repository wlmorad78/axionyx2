<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{DemandForecast};
use App\Support\ValidationRules;

class DemandForecastController extends Controller
{
    public function index(Request $request)
    {
        $query = DemandForecast::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('forecast_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('demand_forecast', 'create'));
        $demandForecast = DemandForecast::create($data);
        return response()->json($demandForecast, 201);
    }

    public function show($id)
    {
        return DemandForecast::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $demandForecast = DemandForecast::findOrFail($id);
        $data = $request->validate(ValidationRules::for('demand_forecast', 'update', $demandForecast));
        $demandForecast->update($data);
        return $demandForecast;
    }

    public function destroy($id)
    {
        $demandForecast = DemandForecast::findOrFail($id);
        $demandForecast->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $demandForecast = DemandForecast::withTrashed()->findOrFail($id);
        $demandForecast->restore();
        return $demandForecast;
    }

    public function forceDelete($id)
    {
        $demandForecast = DemandForecast::withTrashed()->findOrFail($id);
        $demandForecast->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
