<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAdjustment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockAdjustment::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->adjustment_type) $query->where('adjustment_type', $request->adjustment_type);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('adjustment_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('stock_adjustment', 'store'));
        if (empty($data['adjustment_no'])) {
            $data['adjustment_no'] = self::generateNextCode('SA', 'stock_adjustments', 'adjustment_no');
        }
        return response()->json(StockAdjustment::create($data), 201);
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $stockAdjustment->load([
            'company', 'branch', 'warehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment)
    {
        $data = $request->validate(ValidationRules::for('stock_adjustment', 'update', $stockAdjustment));
        $stockAdjustment->update($data);
        return response()->json($stockAdjustment);
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('SA', 'stock_adjustments', 'adjustment_no')]);
    }

    public function restore(int $id)
    {
        $m = StockAdjustment::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        StockAdjustment::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('stock_adjustment', 'store');
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
