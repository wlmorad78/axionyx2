<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReturnOrder;
use App\Models\Employee;
use App\Services\UnitConversionService;
use App\Support\RoleNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnOrderWebController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnOrder::with(['employee', 'warehouse', 'items.item'])
            ->orderByDesc('id');

        if ($request->status) {
            $query->where('status_id', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('return_no', 'like', "%$s%")
                  ->orWhereHas('employee', fn($eq) => $eq->where('name', 'like', "%$s%"));
            });
        }

        $returnOrders = $query->paginate(15);

        return view('return-orders.index', compact('returnOrders'));
    }

    public function show(ReturnOrder $returnOrder)
    {
        $returnOrder->load([
            'employee', 'warehouse', 'items.item', 'items.itemUnit',
            'approvedByEmployee', 'receivedByEmployee',
        ]);

        return view('return-orders.show', compact('returnOrder'));
    }

    public function approve(ReturnOrder $returnOrder)
    {
        $user = Auth::user();
        $isWarehouseKeeper = $user->hasRole(RoleNames::WAREHOUSE_KEEPER);
        $isAdmin = $user->isAdmin();

        if (!$isWarehouseKeeper && !$isAdmin) {
            return back()->with('error', 'ليس لديك صلاحية الموافقة على طلبات الارتجاع');
        }

        $returnOrder->load(['employee', 'warehouse', 'items.item', 'items.itemUnit']);

        return view('return-orders.approve', compact('returnOrder'));
    }

    public function processApproval(Request $request, ReturnOrder $returnOrder)
    {
        $user = Auth::user();
        $isWarehouseKeeper = $user->hasRole(RoleNames::WAREHOUSE_KEEPER);
        $isAdmin = $user->isAdmin();

        if (!$isWarehouseKeeper && !$isAdmin) {
            return back()->with('error', 'ليس لديك صلاحية تنفيذ هذه العملية');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::where('email', $user->email)->first();
        $unitService = app(UnitConversionService::class);

        if ($request->action === 'reject') {
            $returnOrder->update([
                'status_id' => 'cancelled',
                'notes' => $request->notes ?? 'مرفوض من أمين المخزن',
            ]);
            return redirect()
                ->route('return-orders.show', $returnOrder->id)
                ->with('error', 'تم رفض طلب الارتجاع');
        }

        DB::transaction(function () use ($returnOrder, $request, $employee, $user, $unitService) {
            $returnOrder->update([
                'status_id' => 'approved',
                'approved_by' => $employee?->id,
                'approved_at' => now(),
                'notes' => $request->notes ?? 'تمت الموافقة من أمين المخزن',
            ]);

            if ($returnOrder->load_request_id) {
                \App\Models\LoadRequest::where('id', $returnOrder->load_request_id)
                    ->update(['status' => 'closed']);
            }

            $type = \App\Models\InventoryTransactionType::firstOrCreate(
                ['code' => 'RETURN'],
                ['name' => 'Return Order', 'effect' => 'addition', 'is_active' => true]
            );

            $txn = \App\Models\InventoryTransaction::create([
                'company_id' => $returnOrder->company_id,
                'warehouse_id' => $returnOrder->warehouse_id,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\InventoryTransaction::nextTransactionNo($returnOrder->company_id),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => \App\Models\ReturnOrder::class,
                'reference_id' => $returnOrder->id,
                'notes' => "ارتجاع من المندوب {$returnOrder->return_no}",
                'status' => 'posted',
                'created_by' => $employee?->id,
            ]);

            foreach ($returnOrder->items as $item) {
                $unitId = $item->item_unit_id;
                if (!$unitId) {
                    $unitId = \App\Models\Unit::first()?->id;
                }
                $conversionFactor = $unitService->getConversionFactor($item->item_id, $unitId);
                $qtyInBase = $unitService->toBase($item->item_id, $unitId, $item->returned_quantity);
                \App\Models\InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $unitId,
                    'conversion_factor' => $conversionFactor,
                    'qty' => $qtyInBase,
                    'unit_cost' => $item->sales_price,
                    'total_cost' => $item->line_total,
                    'from_location_type' => 'rep',
                    'from_location_id'   => $returnOrder->employee_id,
                    'to_location_type'   => 'warehouse',
                    'to_location_id'     => $returnOrder->warehouse_id,
                ]);
            }
        });

        return redirect()
            ->route('return-orders.show', $returnOrder->id)
            ->with('success', 'تمت الموافقة على الارتجاع بنجاح');
    }

    public function reopen(ReturnOrder $returnOrder)
    {
        $user = Auth::user();
        $isWarehouseKeeper = $user->hasRole(RoleNames::WAREHOUSE_KEEPER);
        $isAdmin = $user->isAdmin();

        if (!$isWarehouseKeeper && !$isAdmin) {
            return back()->with('error', 'ليس لديك صلاحية إعادة فتح طلبات الارتجاع');
        }

        if ($returnOrder->status_id !== 'approved') {
            return back()->with('error', 'لا يمكن إعادة فتح إلا الطلبات المعتمدة');
        }

        DB::transaction(function () use ($returnOrder) {
            \App\Models\InventoryTransaction::where('reference_type', \App\Models\ReturnOrder::class)
                ->where('reference_id', $returnOrder->id)
                ->each(function ($txn) {
                    $txn->items()->delete();
                    $txn->forceDelete();
                });

            $returnOrder->update([
                'status_id' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'notes' => 'تمت إعادة الفتح',
            ]);
        });

        return redirect()
            ->route('return-orders.show', $returnOrder->id)
            ->with('success', 'تمت إعادة فتح طلب الارتجاع');
    }
}
