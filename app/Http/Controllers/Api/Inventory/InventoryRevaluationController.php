<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryRevaluation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryRevaluationController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryRevaluation::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('revaluation_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation', 'store'));
        if (empty($data['revaluation_no'])) {
            $data['revaluation_no'] = self::generateNextCode('IR', 'inventory_revaluations', 'revaluation_no');
        }
        return response()->json(InventoryRevaluation::create($data), 201);
    }

    public function show(InventoryRevaluation $inventoryRevaluation)
    {
        return $inventoryRevaluation->load([
            'company', 'branch', 'warehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    public function update(Request $request, InventoryRevaluation $inventoryRevaluation)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation', 'update', $inventoryRevaluation));
        $inventoryRevaluation->update($data);
        return response()->json($inventoryRevaluation);
    }

    public function destroy(InventoryRevaluation $inventoryRevaluation)
    {
        $inventoryRevaluation->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('IR', 'inventory_revaluations', 'revaluation_no')]);
    }

    public function restore(int $id)
    {
        $m = InventoryRevaluation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        InventoryRevaluation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('inventory_revaluation', 'store');
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
