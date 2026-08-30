<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnOrderSettlement;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Services\ReturnOrderSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnOrderSettlementController extends Controller
{
    public function __construct(
        private readonly ReturnOrderSettlementService $service
    ) {}

    public function create(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id') ?? $request->header('X-Company-Id');
        $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id');

        $settledReturnOrderIds = ReturnOrderSettlement::where('status', '!=', 'cancelled')
            ->whereNotNull('return_order_id')
            ->pluck('return_order_id')
            ->toArray();

        $query = ReturnOrder::with(['items.item', 'items.unit', 'employee', 'warehouse'])
            ->where('status_id', 'approved')
            ->whereNotIn('id', $settledReturnOrderIds);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $returnOrders = $query->orderByDesc('id')->get();

        $orders = $returnOrders->map(function ($order) {
            $items = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item->name ?? '',
                    'item_no' => $item->item->item_no ?? $item->item->code ?? '',
                    'unit' => $item->unit->name ?? $item->unit->unit_name ?? '',
                    'unit_id' => $item->item_unit_id,
                    'unit_price' => $item->sales_price ?? 0,
                    'quantity_loaded' => $item->loaded_qty ?? 0,
                    'quantity_sold' => $item->sold_quantity ?? 0,
                    'quantity_returned' => $item->returned_quantity ?? 0,
                    'received_quantity' => $item->returned_quantity ?? 0,
                    'line_total' => $item->line_total ?? 0,
                ];
            });

            $employee = $order->employee;
            $employeeName = '';
            if ($employee) {
                $employeeName = trim(($employee->first_name_ar ?? '') . ' ' . ($employee->last_name_ar ?? ''));
                if (empty($employeeName)) {
                    $employeeName = $employee->full_name_ar ?? $employee->name ?? '';
                }
            }

            return [
                'return_order_id' => $order->id,
                'return_no' => $order->return_no,
                'return_date' => $order->return_date?->format('Y-m-d'),
                'user_id' => $order->user_id,
                'employee_name' => $employeeName,
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'full_name_ar' => $employee->full_name_ar ?? trim(($employee->first_name_ar ?? '') . ' ' . ($employee->last_name_ar ?? '')),
                ] : null,
                'warehouse_id' => $order->warehouse_id,
                'warehouse_name' => $order->warehouse->name ?? '',
                'warehouse' => $order->warehouse ? ['id' => $order->warehouse->id, 'name' => $order->warehouse->name] : null,
                'load_request_id' => $order->load_request_id,
                'load_request_no' => $order->loadRequest->request_no ?? '',
                'total_items_count' => $order->total_items_count,
                'total_quantity' => $order->total_quantity,
                'items' => $items,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,submitted,approved,cancelled,all',
            'user_id' => 'nullable|integer',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ReturnOrderSettlement::with('items')->orderByDesc('id');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'submitted') $status = 'pending';
            $query->where('status', $status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('settlement_no', 'like', "%{$search}%")
                  ->orWhere('load_request_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        $settlements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $settlements->items(),
            'current_page' => $settlements->currentPage(),
            'last_page' => $settlements->lastPage(),
            'total' => $settlements->total(),
            'per_page' => $settlements->perPage(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'return_order_id' => 'nullable|integer',
            'load_request_id' => 'nullable|integer',
            'user_id' => 'required|integer',
            'warehouse_id' => 'nullable|integer',
            'load_request_no' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.item_id' => 'required|integer',
            'items.*.unit_id' => 'nullable|integer',
            'items.*.loaded_quantity' => 'nullable|numeric|min:0',
            'items.*.sold_quantity' => 'nullable|numeric|min:0',
            'items.*.returned_quantity' => 'nullable|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.replacement_item_id' => 'nullable|integer',
            'items.*.replacement_quantity' => 'nullable|numeric|min:0',
            'items.*.replacements' => 'nullable|array',
            'items.*.replacements.*.original_item_id' => 'required_with:items.*.replacements|integer',
            'items.*.replacements.*.replacement_item_id' => 'required_with:items.*.replacements|integer',
            'items.*.replacements.*.quantity' => 'required_with:items.*.replacements|numeric|min:0',
            'items.*.replacements.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if (empty($validated['return_order_id']) && !empty($validated['load_request_id'])) {
            $returnOrder = ReturnOrder::where('load_request_id', $validated['load_request_id'])->first();
            if ($returnOrder) {
                $validated['return_order_id'] = $returnOrder->id;
                if (empty($validated['warehouse_id'])) {
                    $validated['warehouse_id'] = $returnOrder->warehouse_id;
                }
            }
        }

        if (!empty($validated['return_order_id']) && empty($validated['warehouse_id'])) {
            $returnOrder = ReturnOrder::find($validated['return_order_id']);
            if ($returnOrder) {
                $validated['warehouse_id'] = $returnOrder->warehouse_id;
            }
        }

        foreach ($validated['items'] as &$item) {
            if (empty($item['loaded_quantity']) || $item['loaded_quantity'] <= 0) {
                $item['loaded_quantity'] = 0;
                $item['sold_quantity'] = 0;
                $item['returned_quantity'] = 0;
                $item['unit_price'] = 0;

                $returnItemId = $item['id'] ?? null;
                if ($returnItemId) {
                    $returnItem = ReturnOrderItem::find($returnItemId);
                    if ($returnItem) {
                        $item['loaded_quantity'] = $returnItem->loaded_qty ?? 0;
                        $item['sold_quantity'] = $returnItem->sold_quantity ?? 0;
                        $item['returned_quantity'] = $returnItem->returned_quantity ?? 0;
                        $item['unit_price'] = $returnItem->sales_price ?? 0;
                        if (empty($item['unit_id'])) {
                            $item['unit_id'] = $returnItem->item_unit_id;
                        }
                    }
                }
            }
        }
        unset($item);

        $settlement = $this->service->createSettlement($validated);

        return response()->json([
            'success' => true,
            'data' => $settlement->load('items'),
            'message' => 'تم إنشاء التسوية بنجاح',
        ], 201);
    }

    public function show(ReturnOrderSettlement $settlement): JsonResponse
    {
        $settlement->load('items.replacements');

        $employee = \App\Models\Employee::find($settlement->user_id);
        $warehouse = \App\Models\Warehouse::find($settlement->warehouse_id);

        return response()->json([
            'success' => true,
            'data' => array_merge($settlement->toArray(), [
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'full_name_ar' => $employee->full_name_ar ?? trim(($employee->first_name_ar ?? '') . ' ' . ($employee->last_name_ar ?? '')),
                ] : null,
                'employee_name' => $employee ? ($employee->full_name_ar ?? trim(($employee->first_name_ar ?? '') . ' ' . ($employee->last_name_ar ?? ''))) : '',
                'warehouse' => $warehouse ? ['id' => $warehouse->id, 'name' => $warehouse->name] : null,
                'warehouse_name' => $warehouse->name ?? '',
            ]),
        ]);
    }

    public function approve(ReturnOrderSettlement $settlement): JsonResponse
    {
        if ($settlement->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الاعتماد على تسوية غير معلقة',
            ], 422);
        }

        $settlement = $this->service->approveSettlement($settlement);

        return response()->json([
            'success' => true,
            'data' => $settlement->load('items'),
            'message' => 'تم الاعتماد بنجاح',
        ]);
    }

    public function cancel(ReturnOrderSettlement $settlement): JsonResponse
    {
        if ($settlement->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'التسوية ملغاة بالفعل',
            ], 422);
        }

        $settlement = $this->service->cancelSettlement($settlement);

        return response()->json([
            'success' => true,
            'data' => $settlement,
            'message' => 'تم إلغاء التسوية',
        ]);
    }
}
