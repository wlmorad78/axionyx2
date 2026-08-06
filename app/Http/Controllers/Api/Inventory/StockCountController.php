<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockCount;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockCount::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->count_type) $query->where('count_type', $request->count_type);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('count_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('stock_count', 'store'));
        if (empty($data['count_no'])) {
            $data['count_no'] = self::generateNextCode('SC', 'stock_counts', 'count_no');
        }
        return response()->json(StockCount::create($data), 201);
    }

    public function show(StockCount $stockCount)
    {
        return $stockCount->load([
            'company', 'branch', 'warehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'countedByEmployee', 'approvedByEmployee',
        ]);
    }

    public function update(Request $request, StockCount $stockCount)
    {
        $data = $request->validate(ValidationRules::for('stock_count', 'update', $stockCount));
        $stockCount->update($data);
        return response()->json($stockCount);
    }

    public function destroy(StockCount $stockCount)
    {
        $stockCount->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('SC', 'stock_counts', 'count_no')]);
    }

    public function restore(int $id)
    {
        $m = StockCount::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        StockCount::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('stock_count', 'store');
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
