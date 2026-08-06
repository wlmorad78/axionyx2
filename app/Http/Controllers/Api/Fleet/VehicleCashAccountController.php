<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleCashAccount;
use Illuminate\Http\Request;

class VehicleCashAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleCashAccount::with(['vehicle', 'treasury', 'transactions']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $accounts = $query->paginate($request->get('per_page', 15));

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'treasury_id' => 'required',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
        ]);

        $account = VehicleCashAccount::create($validated);

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']), 201);
    }

    public function show($id)
    {
        $account = VehicleCashAccount::with(['vehicle', 'treasury', 'transactions'])->findOrFail($id);

        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        $account = VehicleCashAccount::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'treasury_id' => 'required',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
        ]);

        $account->update($validated);

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']));
    }

    public function destroy($id)
    {
        $account = VehicleCashAccount::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Vehicle cash account deleted successfully']);
    }

    public function restore($id)
    {
        $account = VehicleCashAccount::withTrashed()->findOrFail($id);
        $account->restore();

        return response()->json($account->load(['vehicle', 'treasury', 'transactions']));
    }

    public function forceDelete($id)
    {
        $account = VehicleCashAccount::withTrashed()->findOrFail($id);
        $account->forceDelete();

        return response()->json(['message' => 'Vehicle cash account permanently deleted']);
    }
}
