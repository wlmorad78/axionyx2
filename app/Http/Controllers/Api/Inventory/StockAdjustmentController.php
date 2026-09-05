<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if ($request->filled('date_from')) {
            $query->where('adjustment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('adjustment_date', '<=', $request->date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('adjustment_no', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%")
                  ->orWhere('reason', 'like', "%$s%");
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

        $user = Auth::user();
        $data['company_id'] = $data['company_id'] ?? $user->company_id;
        $data['branch_id'] = $data['branch_id'] ?? $user->branch_id ?? null;
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';

        $adjustment = DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create(collect($data)->only([
                'company_id', 'branch_id', 'warehouse_id', 'adjustment_no',
                'adjustment_date', 'reason', 'notes', 'status', 'created_by',
            ])->toArray());

            $items = request('items', []);
            foreach ($items as $itemData) {
                $validated = \Validator::make($itemData, ValidationRules::for('stock_adjustment_item', 'store'))->validate();
                $diff = ($validated['actual_qty'] ?? 0) - ($validated['system_qty'] ?? 0);
                $cf = $validated['conversion_factor'] ?? 1;

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'item_id' => $validated['item_id'],
                    'unit_id' => $validated['unit_id'] ?? null,
                    'conversion_factor' => $cf,
                    'base_quantity' => ($validated['actual_qty'] ?? 0) * $cf,
                    'system_qty' => $validated['system_qty'] ?? 0,
                    'actual_qty' => $validated['actual_qty'] ?? 0,
                    'difference_qty' => $diff,
                    'unit_cost' => $validated['unit_cost'] ?? 0,
                    'difference_value' => $diff * ($validated['unit_cost'] ?? 0),
                ]);
            }

            return $adjustment;
        });

        $adjustment->load(['warehouse', 'items.item', 'items.unit']);

        return response()->json([
            'message' => "تم إنشاء تعديل المخزون {$adjustment->adjustment_no} بنجاح",
            'data' => $adjustment,
        ], 201);
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $stockAdjustment->load([
            'company', 'branch', 'warehouse',
            'items.item.unit', 'items.unit',
            'createdBy', 'approvedBy',
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status === 'approved') {
            return response()->json(['message' => 'لا يمكن تعديل سجل معتمد'], 422);
        }

        $data = $request->validate(ValidationRules::for('stock_adjustment', 'update', $stockAdjustment));

        DB::transaction(function () use ($stockAdjustment, $data) {
            $stockAdjustment->update(collect($data)->only([
                'warehouse_id', 'adjustment_date', 'reason', 'notes', 'status',
            ])->toArray());

            if ($request->has('items')) {
                $stockAdjustment->items()->delete();

                foreach ($request->items as $itemData) {
                    $validated = \Validator::make($itemData, ValidationRules::for('stock_adjustment_item', 'store'))->validate();
                    $diff = ($validated['actual_qty'] ?? 0) - ($validated['system_qty'] ?? 0);
                    $cf = $validated['conversion_factor'] ?? 1;

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $stockAdjustment->id,
                        'item_id' => $validated['item_id'],
                        'unit_id' => $validated['unit_id'] ?? null,
                        'conversion_factor' => $cf,
                        'base_quantity' => ($validated['actual_qty'] ?? 0) * $cf,
                        'system_qty' => $validated['system_qty'] ?? 0,
                        'actual_qty' => $validated['actual_qty'] ?? 0,
                        'difference_qty' => $diff,
                        'unit_cost' => $validated['unit_cost'] ?? 0,
                        'difference_value' => $diff * ($validated['unit_cost'] ?? 0),
                    ]);
                }
            }
        });

        $stockAdjustment->load(['items.item', 'items.unit']);

        return response()->json([
            'message' => "تم تحديث تعديل المخزون {$stockAdjustment->adjustment_no} بنجاح",
            'data' => $stockAdjustment,
        ]);
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status === 'approved') {
            return response()->json(['message' => 'لا يمكن حذف سجل معتمد'], 422);
        }

        $stockAdjustment->delete();

        return response()->json(null, 204);
    }

    public function approve(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status !== 'draft') {
            return response()->json(['message' => 'فقط المسودات يمكن اعتمادها'], 422);
        }

        $stockAdjustment->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $stockAdjustment->load(['warehouse', 'items.item', 'items.unit', 'createdBy', 'approvedBy']);

        return response()->json([
            'message' => "تم اعتماد تعديل المخزون {$stockAdjustment->adjustment_no} بنجاح",
            'data' => $stockAdjustment,
        ]);
    }

    public function cancel(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status !== 'draft') {
            return response()->json(['message' => 'فقط المسودات يمكن إلغاؤها'], 422);
        }

        $stockAdjustment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => "تم إلغاء تعديل المخزون {$stockAdjustment->adjustment_no}",
            'data' => $stockAdjustment,
        ]);
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
