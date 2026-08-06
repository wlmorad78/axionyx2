<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ForecastHistory};
use App\Support\ValidationRules;

class ForecastHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ForecastHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('actual_qty', 'like', "%{$s}%")
                  ->orWhere('forecast_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('forecast_history', 'create'));
        $forecastHistory = ForecastHistory::create($data);
        return response()->json($forecastHistory, 201);
    }

    public function show($id)
    {
        return ForecastHistory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $forecastHistory = ForecastHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('forecast_history', 'update', $forecastHistory));
        $forecastHistory->update($data);
        return $forecastHistory;
    }

    public function destroy($id)
    {
        $forecastHistory = ForecastHistory::findOrFail($id);
        $forecastHistory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $forecastHistory = ForecastHistory::withTrashed()->findOrFail($id);
        $forecastHistory->restore();
        return $forecastHistory;
    }

    public function forceDelete($id)
    {
        $forecastHistory = ForecastHistory::withTrashed()->findOrFail($id);
        $forecastHistory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
