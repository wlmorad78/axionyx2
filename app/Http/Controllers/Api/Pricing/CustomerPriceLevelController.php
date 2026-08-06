<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CustomerPriceLevel};
use App\Support\ValidationRules;

class CustomerPriceLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPriceLevel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_price_level', 'create'));
        $customerPriceLevel = CustomerPriceLevel::create($data);
        return response()->json($customerPriceLevel, 201);
    }

    public function show($id)
    {
        return CustomerPriceLevel::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $customerPriceLevel = CustomerPriceLevel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('customer_price_level', 'update', $customerPriceLevel));
        $customerPriceLevel->update($data);
        return $customerPriceLevel;
    }

    public function destroy($id)
    {
        $customerPriceLevel = CustomerPriceLevel::findOrFail($id);
        $customerPriceLevel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $customerPriceLevel = CustomerPriceLevel::withTrashed()->findOrFail($id);
        $customerPriceLevel->restore();
        return $customerPriceLevel;
    }

    public function forceDelete($id)
    {
        $customerPriceLevel = CustomerPriceLevel::withTrashed()->findOrFail($id);
        $customerPriceLevel->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
