<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CustomerSpecialPrice};
use App\Support\ValidationRules;

class CustomerSpecialPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerSpecialPrice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('price', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_special_price', 'create'));
        $customerSpecialPrice = CustomerSpecialPrice::create($data);
        return response()->json($customerSpecialPrice, 201);
    }

    public function show($id)
    {
        return CustomerSpecialPrice::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('customer_special_price', 'update', $customerSpecialPrice));
        $customerSpecialPrice->update($data);
        return $customerSpecialPrice;
    }

    public function destroy($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::findOrFail($id);
        $customerSpecialPrice->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::withTrashed()->findOrFail($id);
        $customerSpecialPrice->restore();
        return $customerSpecialPrice;
    }

    public function forceDelete($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::withTrashed()->findOrFail($id);
        $customerSpecialPrice->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
