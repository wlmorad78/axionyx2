<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransactionType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransactionType::with($with);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_type', 'store'));
        return response()->json(InventoryTransactionType::create($data), 201);
    }

    public function show(InventoryTransactionType $inventoryTransactionType)
    {
        return $inventoryTransactionType->load([]);
    }

    public function update(Request $request, InventoryTransactionType $inventoryTransactionType)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_type', 'update', $inventoryTransactionType));
        $inventoryTransactionType->update($data);
        return response()->json($inventoryTransactionType);
    }

    public function destroy(InventoryTransactionType $inventoryTransactionType)
    {
        $inventoryTransactionType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = InventoryTransactionType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        InventoryTransactionType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('inventory_transaction_type', 'store');
    }
}
