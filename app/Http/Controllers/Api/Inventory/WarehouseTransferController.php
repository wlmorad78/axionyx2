<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseTransfer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTransferController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseTransfer::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->from_warehouse_id) $query->where('from_warehouse_id', $request->from_warehouse_id);
        if ($request->to_warehouse_id) $query->where('to_warehouse_id', $request->to_warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer', 'store'));
        if (empty($data['transfer_no'])) {
            $data['transfer_no'] = self::generateNextCode('WT', 'warehouse_transfers', 'transfer_no');
        }
        return response()->json(WarehouseTransfer::create($data), 201);
    }

    public function show(WarehouseTransfer $warehouseTransfer)
    {
        return $warehouseTransfer->load([
            'company', 'branch',
            'fromWarehouse', 'toWarehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    public function update(Request $request, WarehouseTransfer $warehouseTransfer)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer', 'update', $warehouseTransfer));
        $warehouseTransfer->update($data);
        return response()->json($warehouseTransfer);
    }

    public function destroy(WarehouseTransfer $warehouseTransfer)
    {
        $warehouseTransfer->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('WT', 'warehouse_transfers', 'transfer_no')]);
    }

    public function restore(int $id)
    {
        $m = WarehouseTransfer::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        WarehouseTransfer::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('warehouse_transfer', 'store');
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
