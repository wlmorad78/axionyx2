<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransaction::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->transaction_type_id) $query->where('transaction_type_id', $request->transaction_type_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction', 'store'));
        if (empty($data['transaction_no'])) {
            $data['transaction_no'] = self::generateNextCode('INV', 'inventory_transactions', 'transaction_no');
        }
        return response()->json(InventoryTransaction::create($data), 201);
    }

    public function show(InventoryTransaction $inventoryTransaction)
    {
        return $inventoryTransaction->load([
            'company', 'branch', 'warehouse', 'transactionType',
            'items.item', 'items.unit', 'createdByEmployee',
        ]);
    }

    public function update(Request $request, InventoryTransaction $inventoryTransaction)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction', 'update', $inventoryTransaction));
        $inventoryTransaction->update($data);
        return response()->json($inventoryTransaction);
    }

    public function destroy(InventoryTransaction $inventoryTransaction)
    {
        $inventoryTransaction->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('INV', 'inventory_transactions', 'transaction_no')]);
    }

    public function restore(int $id)
    {
        $m = InventoryTransaction::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        InventoryTransaction::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('inventory_transaction', 'store');
    }

    protected static function generateNextCode(string $prefix, string $table, string $column): string
    {
        $last = \DB::table($table)->where($column, 'like', "$prefix-%")->orderByDesc($column)->value($column);
        if ($last) {
            $num = intval(substr($last, strlen($prefix) + 1)) + 1;
        } else {
            $num = 1;
        }
        return $prefix . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
