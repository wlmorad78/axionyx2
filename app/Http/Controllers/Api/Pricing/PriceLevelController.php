<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PriceLevel};
use App\Support\ValidationRules;

class PriceLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = PriceLevel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('level_code', 'like', "%{$s}%")
                  ->orWhere('level_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('price_level', 'create'));
        $priceLevel = PriceLevel::create($data);
        return response()->json($priceLevel, 201);
    }

    public function show($id)
    {
        return PriceLevel::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $priceLevel = PriceLevel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('price_level', 'update', $priceLevel));
        $priceLevel->update($data);
        return $priceLevel;
    }

    public function destroy($id)
    {
        $priceLevel = PriceLevel::findOrFail($id);
        $priceLevel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $priceLevel = PriceLevel::withTrashed()->findOrFail($id);
        $priceLevel->restore();
        return $priceLevel;
    }

    public function forceDelete($id)
    {
        $priceLevel = PriceLevel::withTrashed()->findOrFail($id);
        $priceLevel->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
