<?php
/**
 * =====================================================================
 * متحكم (Controller): Handheld2Controller
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): Handheld2
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Handheld2" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoicePaymentMethod;
use App\Models\RepItemDistribution;
use App\Models\Device;
use App\Models\Warehouse;
use App\Services\RepresentativeTransferService;
use App\Models\RepresentativeTransfer;

class Handheld2Controller extends Controller
{
    public function representatives(Request $request)
    {
        $user = $request->user();
        $currentEmployeeId = DB::table('employees')->where('id', $user->id)->value('id');
        $employees = DB::table('employees')->where('company_id', $user->company_id)
            ->where('is_active', true)->where('id', '!=', $currentEmployeeId)
            ->orderBy('first_name_ar')->get(['id', 'employee_code', 'first_name_ar', 'second_name_ar', 'third_name_ar', 'last_name_ar']);
        return response()->json(['data' => $employees->map(fn ($e) => [
            'id' => $e->id,
            'name' => trim(implode(' ', array_filter([$e->first_name_ar, $e->second_name_ar, $e->third_name_ar, $e->last_name_ar]))),
            'code' => $e->employee_code,
        ])]);
    }

    public function representativeStock(Request $request, int $employeeId)
    {
        $user = $request->user();
        $rows = DB::table('rep_item_distributions as d')->join('items as i', 'i.id', '=', 'd.item_id')
            ->where('d.company_id', $user->company_id)->where('d.user_id', $employeeId)
            ->where('d.status', 'active')->where('d.remaining_qty', '>', 0)
            ->select('d.item_id', 'i.code', 'i.name_ar', 'i.name_en', DB::raw('SUM(d.remaining_qty) as available_qty'))
            ->groupBy('d.item_id', 'i.code', 'i.name_ar', 'i.name_en')->orderBy('i.name_ar')->get();
        return response()->json(['data' => $rows->map(fn ($r) => [
            'item_id' => $r->item_id, 'item_code' => $r->code,
            'item_name' => $r->name_ar ?? $r->name_en, 'available_qty' => (float) $r->available_qty,
        ])]);
    }

    public function representativeStockSummary(Request $request)
    {
        $companyId = $request->user()->company_id;
        $employeeId = (int) DB::table('employees')->where('id', $request->user()->id)->value('id');
        $base = fn (int $itemId) => DB::table('inventory_transaction_items as iti')
            ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
            ->where('it.company_id', $companyId)->where('it.status', 'posted')
            ->whereNull('it.deleted_at')->where('iti.item_id', $itemId);

        $rows = DB::table('items as i')->where('i.company_id', $companyId)->where('i.is_active', true)
            ->where(function ($q) use ($employeeId, $companyId) {
                $q->whereExists(function ($sub) use ($employeeId, $companyId) {
                    $sub->selectRaw('1')->from('inventory_transaction_items as x')->join('inventory_transactions as t', 't.id', '=', 'x.inventory_transaction_id')
                        ->whereColumn('x.item_id', 'i.id')->where('t.company_id', $companyId)->where('t.status', 'posted')
                        ->where(function ($w) use ($employeeId) {
                            $w->where(function ($z) use ($employeeId) { $z->where('x.to_location_type', 'rep')->where('x.to_location_id', $employeeId); })
                              ->orWhere(function ($z) use ($employeeId) { $z->where('x.from_location_type', 'rep')->where('x.from_location_id', $employeeId); });
                        });
                })->orWhereExists(function ($sub) use ($employeeId, $companyId) {
                    $sub->selectRaw('1')->from('rep_item_distributions as d')->whereColumn('d.item_id', 'i.id')
                        ->where('d.company_id', $companyId)->where('d.user_id', $employeeId);
                });
            })->orderBy('i.id')->get(['i.id', 'i.code', 'i.name_ar', 'i.name_en']);

        $itemIds = $rows->pluck('id')->toArray();
        $unitPriceMap = [];
        if (!empty($itemIds)) {
            $units = DB::table('item_units')->whereIn('item_id', $itemIds)->get();
            foreach ($units as $u) {
                if ((float) $u->sale_price > 0) {
                    if ($u->is_sales_unit && !isset($unitPriceMap[$u->item_id])) {
                        $unitPriceMap[$u->item_id] = (float) $u->sale_price;
                    } elseif (!isset($unitPriceMap[$u->item_id])) {
                        $unitPriceMap[$u->item_id] = (float) $u->sale_price;
                    }
                }
            }
        }

        $data = $rows->map(function ($item) use ($base, $employeeId, $companyId, $unitPriceMap) {
            $load = (float) $base($item->id)->where('iti.from_location_type', 'warehouse')->where('iti.to_location_type', 'rep')->where('iti.to_location_id', $employeeId)->sum(DB::raw('ABS(iti.qty)'));
            $loadReturn = (float) $base($item->id)->where('iti.from_location_type', 'rep')->where('iti.to_location_type', 'warehouse')->where('iti.from_location_id', $employeeId)->sum(DB::raw('ABS(iti.qty)'));
            $load = max(0, $load - $loadReturn);
            $tin = (float) $base($item->id)->where('iti.from_location_type', 'rep')->where('iti.to_location_type', 'rep')->where('iti.to_location_id', $employeeId)->sum(DB::raw('ABS(iti.qty)'));
            $tout = (float) $base($item->id)->where('iti.from_location_type', 'rep')->where('iti.to_location_type', 'rep')->where('iti.from_location_id', $employeeId)->sum(DB::raw('ABS(iti.qty)'));
            $sales = (float) RepItemDistribution::where('company_id', $companyId)->where('user_id', $employeeId)->where('item_id', $item->id)->sum('sold_qty');
            return [
                'item_id' => $item->id, 'item_code' => $item->code,
                'item_name' => $item->name_ar ?? $item->name_en,
                'code' => $item->code, 'name' => $item->name_ar ?? $item->name_en,
                'load_qty' => $load, 't_in_qty' => $tin, 't_out_qty' => $tout,
                'sales_qty' => $sales, 'remaining_qty' => max(0, $load + $tin - $tout - $sales),
                'unit_price' => $unitPriceMap[$item->id] ?? 0,
            ];
        })->filter(fn ($row) => $row['load_qty'] > 0 || $row['t_in_qty'] > 0 || $row['t_out_qty'] > 0 || $row['sales_qty'] > 0)
            ->sortBy('item_id')->values();

        return response()->json(['data' => $data]);
    }

    public function representativeTransfer(Request $request)
    {
        $currentEmployeeId = (int) DB::table('employees')->where('id', $request->user()->id)->value('id');
        $data = $request->validate([
            'from_user_id' => 'required|integer', 'to_user_id' => 'required|integer',
            'client_uuid' => 'nullable|uuid', 'branch_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer', 'notes' => 'nullable|string',
            'items' => 'required|array|min:1', 'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|gt:0', 'items.*.base_quantity' => 'nullable|numeric|gt:0',
            'items.*.unit_id' => 'nullable|integer', 'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string', 'items.*.expiry_date' => 'nullable|date',
        ]);
        if ((int) $data['from_user_id'] !== $currentEmployeeId) {
            return response()->json(['message' => 'لا يمكن تحويل مخزون مندوب آخر من هذا الحساب.'], 403);
        }
        return response()->json(['data' => app(RepresentativeTransferService::class)->post($request->user(), $data)], 201);
    }

    public function incomingRepresentativeTransfers(Request $request)
    {
        $employeeId = (int) DB::table('employees')->where('id', $request->user()->id)->value('id');
        $transfers = RepresentativeTransfer::with(['fromEmployee', 'items.item'])
            ->where('company_id', $request->user()->company_id)
            ->where('to_user_id', $employeeId)
            ->whereIn('status', ['posted', 'received'])
            ->latest('id')->get();

        return response()->json(['data' => $transfers->map(fn ($transfer) => [
            'id' => $transfer->id,
            'transfer_no' => $transfer->transfer_no,
            'status' => $transfer->status,
            'from_employee_name' => $transfer->fromEmployee?->full_name_ar,
            'created_at' => $transfer->created_at,
            'items' => $transfer->items->map(fn ($item) => [
                'item_id' => $item->item_id,
                'item_name' => $item->item?->name_ar ?? $item->item?->name_en,
                'item_code' => $item->item?->code,
                'quantity' => (float) $item->quantity,
                'base_quantity' => (float) $item->base_quantity,
                'unit_id' => $item->unit_id,
                'unit_price' => (float) $item->unit_cost,
                'line_total' => (float) $item->unit_cost * (float) $item->quantity,
            ]),
        ])]);
    }

    public function receiveRepresentativeTransfer(Request $request, int $id)
    {
        $employeeId = (int) DB::table('employees')->where('id', $request->user()->id)->value('id');
        $transfer = RepresentativeTransfer::where('company_id', $request->user()->company_id)
            ->where('to_user_id', $employeeId)->findOrFail($id);
        if ($transfer->status === 'posted') {
            $transfer->update(['status' => 'received']);
        }
        return response()->json(['data' => $transfer->fresh()->load('items.item')]);
    }
    /**
     * دالة معالجة: login — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function login(Request $request)
    {
        $request->validate([
            'usercode' => 'required|numeric',
            'password' => 'required',
        ]);

        $user = User::where('usercode', $request->usercode)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'usercode' => ['اسم المستخدم أو الباسورد غير صحيح.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'usercode' => ['الحساب غير مفعل.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('handheld2-token')->plainTextToken;

        $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();
        $resources = $this->getAssignmentResources($user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'usercode' => $user->usercode,
                'name' => $user->name,
            ],
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name_ar ?? $user->company->name,
            ] : null,
            'branch' => $defaultBranch ? [
                'id' => $defaultBranch->id,
                'name' => $defaultBranch->name_ar ?? $defaultBranch->name,
            ] : null,
            'token' => $token,
        ]);
    }

    /**
     * دالة معالجة: downloadData — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function downloadData(Request $request)
    {
        $user = $request->user();
        $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();
        $resources = $this->getAssignmentResources($user->id);
        $counts = $this->getCounts($user, $defaultBranch, $resources);

        $routeIds = $resources['route_ids'] ?? collect();
        $employeeId = $resources['user_id'];

        $routes = [];
        $customersList = [];
        $customerBalances = $this->customerBalances($user->company_id);
        if ($routeIds->isNotEmpty()) {
            $routes = DB::table('routes')
                ->whereIn('routes.id', $routeIds)
                ->where('routes.is_active', true)
                ->whereNull('routes.deleted_at')
                ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
                ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'routes.sales_territory_id', 'sales_territories.name_ar as territory_name')
                ->get()
                ->map(function ($route) use ($customerBalances) {
                    $customerIds = DB::table('route_customers')
                        ->where('route_id', $route->id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->pluck('customer_id');

                    $customers = DB::table('customers')
                        ->leftJoin('customer_types', 'customers.customer_type_id', '=', 'customer_types.id')
                        ->whereIn('customers.id', $customerIds)
                        ->where('customers.is_active', true)
                        ->whereNull('customers.deleted_at')
                        ->get(['customers.id', 'customers.code', 'customers.name_ar', 'customers.name_en', 'customers.phone', 'customers.mobile', 'customers.address_line', 'customers.latitude', 'customers.longitude', 'customers.customer_type_id', 'customer_types.name_en as type_name_en', 'customer_types.name_ar as type_name_ar'])
                        ->map(function ($c) use ($customerBalances) {
                            return [
                                'id' => $c->id,
                                'code' => $c->code,
                                'name' => $c->name_ar ?? $c->name_en,
                                'phone' => $c->phone,
                                'mobile' => $c->mobile,
                                'address' => $c->address_line,
                                'latitude' => $c->latitude,
                                'longitude' => $c->longitude,
                                'customer_type_id' => $c->customer_type_id ?? 0,
                                'customer_type' => $c->type_name_en ?? $c->type_name_ar ?? '',
                                'debit_amount' => $customerBalances[(int) $c->id]['debit_amount'] ?? 0,
                                'credit_amount' => $customerBalances[(int) $c->id]['credit_amount'] ?? 0,
                                'balance' => $customerBalances[(int) $c->id]['balance'] ?? 0,
                            ];
                        });

                    return [
                        'id' => $route->id,
                        'code' => $route->code,
                        'name' => $route->name_ar ?? $route->name_en,
                        'territory_name' => $route->territory_name,
                        'sales_territory_id' => $route->sales_territory_id ?? 0,
                        'customers' => $customers,
                    ];
                })
                ->toArray();

            $allCustomerIds = DB::table('route_customers')
                ->whereIn('route_id', $routeIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('customer_id')
                ->unique();

            $customersList = DB::table('customers')
                ->leftJoin('customer_types', 'customers.customer_type_id', '=', 'customer_types.id')
                ->whereIn('customers.id', $allCustomerIds)
                ->where('customers.is_active', true)
                ->whereNull('customers.deleted_at')
                ->get(['customers.id', 'customers.code', 'customers.name_ar', 'customers.name_en', 'customers.phone', 'customers.mobile', 'customers.address_line', 'customers.latitude', 'customers.longitude', 'customers.customer_type_id', 'customer_types.name_en as type_name_en', 'customer_types.name_ar as type_name_ar'])
                ->map(function ($c) use ($customerBalances) {
                    return [
                        'id' => $c->id,
                        'code' => $c->code,
                        'name' => $c->name_ar ?? $c->name_en,
                        'phone' => $c->phone,
                        'mobile' => $c->mobile,
                        'address' => $c->address_line,
                        'latitude' => $c->latitude,
                        'longitude' => $c->longitude,
                        'customer_type_id' => $c->customer_type_id ?? 0,
                        'customer_type' => $c->type_name_en ?? $c->type_name_ar ?? '',
                        'debit_amount' => $customerBalances[(int) $c->id]['debit_amount'] ?? 0,
                        'credit_amount' => $customerBalances[(int) $c->id]['credit_amount'] ?? 0,
                        'balance' => $customerBalances[(int) $c->id]['balance'] ?? 0,
                    ];
                })
                ->toArray();
        }

            $loadRequests = [];
        if ($employeeId) {
            $loadRequests = DB::table('load_requests')
                ->where('user_id', $employeeId)
                ->where('status', 'loaded')
                ->whereNull('deleted_at')
                ->orderByDesc('request_date')
                ->get(['id', 'request_no', 'status', 'total_items_count', 'total_quantity', 'total_amount', 'request_date'])
                ->map(function ($lr) {
                    $items = DB::table('load_request_items')
                        ->where('load_request_id', $lr->id)
                        ->whereNull('deleted_at')
                        ->get();

                    $itemsData = $items->map(function ($item) {
                        $product = DB::table('items')->where('id', $item->item_id)->first();
                        return [
                            'item_id' => $item->item_id,
                            'item_name' => $product ? ($product->name_ar ?? $product->name_en ?? '') : '',
                            'item_code' => $product ? $product->code : '',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    });

                    return [
                        'id' => $lr->id,
                        'request_no' => $lr->request_no,
                        'status' => $lr->status,
                        'total_items_count' => $lr->total_items_count,
                        'total_quantity' => $lr->total_quantity,
                        'total_amount' => $lr->total_amount,
                        'request_date' => $lr->request_date,
                        'items' => $itemsData,
                    ];
                })
                ->toArray();
        }

        $vehicle = null;
        if ($employeeId) {
            $vehicleAssignment = DB::table('vehicle_assignments')
                ->where(function ($q) use ($employeeId) {
                    $q->where('sales_rep_id', $employeeId)
                      ->orWhere('driver_id', $employeeId);
                })
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->latest('id')
                ->first();

            if ($vehicleAssignment) {
                $v = DB::table('vehicles')->where('id', $vehicleAssignment->vehicle_id)->first();
                if ($v) {
                    $lastShift = DB::table('vehicle_daily_shifts')
                        ->where('vehicle_id', $v->id)
                        ->where('status', 'COMPLETED')
                        ->whereNull('deleted_at')
                        ->orderByDesc('shift_date')
                        ->first();

                    $vehicle = [
                        'id' => $v->id,
                        'vehicle_code' => $v->vehicle_code,
                        'plate_number' => $v->plate_number,
                        'model' => $v->model,
                        'year' => $v->year,
                        'last_end_km' => $lastShift ? (float) $lastShift->end_km : 0,
                    ];
                }
            }
        }

        $loadedItemIds = collect();
        if (!empty($loadRequests)) {
            $loadRequestIds = array_column($loadRequests, 'id');
            $loadedItemIds = DB::table('load_request_items')
                ->whereIn('load_request_id', $loadRequestIds)
                ->whereNull('deleted_at')
                ->pluck('item_id');
        }

        if ($employeeId) {
            $distItemIds = DB::table('rep_item_distributions')
                ->where('user_id', $employeeId)
                ->where('company_id', $user->company_id)
                ->where('status', 'active')
                ->where('remaining_qty', '>', 0)
                ->pluck('item_id');
            $loadedItemIds = $loadedItemIds->merge($distItemIds);
        }

        $loadedItemIds = $loadedItemIds->unique()->filter();

        $items = [];
        if ($loadedItemIds->isNotEmpty()) {
            $items = DB::table('items')
                ->whereIn('items.id', $loadedItemIds)
                ->where('items.company_id', $user->company_id)
                ->where('items.is_active', true)
                ->whereNull('items.deleted_at')
                ->leftJoin('item_units', function ($join) {
                    $join->on('items.id', '=', 'item_units.item_id')
                         ->where('item_units.is_sales_unit', '=', true);
                })
                ->get(['items.id', 'items.code', 'items.name_ar', 'items.name_en', 'item_units.purchase_price', 'item_units.sale_price'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name_ar ?? $item->name_en,
                        'purchase_price' => (float) ($item->purchase_price ?? 0),
                        'sale_price' => (float) ($item->sale_price ?? 0),
                    ];
                })
                ->toArray();
        }

        $repTempCustomers = [];
        if ($employeeId) {
            $repTempCustomers = DB::table('rep_temp_customers as rtc')
                ->join('customers as c', 'c.id', '=', 'rtc.customer_id')
                ->where('rtc.employee_id', $employeeId)
                ->whereNull('rtc.deleted_at')
                ->where('c.is_active', true)
                ->whereNull('c.deleted_at')
                ->select('rtc.customer_id', 'c.code', 'c.name_ar', 'c.phone', 'c.mobile')
                ->get()
                ->map(fn ($row) => [
                    'customer_id' => $row->customer_id,
                    'code' => $row->code,
                    'name' => $row->name_ar,
                    'phone' => $row->phone,
                    'mobile' => $row->mobile,
                ])
                ->toArray();
        }

        return response()->json([
            'branch' => $defaultBranch ? [
                'id' => $defaultBranch->id,
                'name' => $defaultBranch->name_ar ?? $defaultBranch->name,
            ] : null,
            'warehouse' => $resources['warehouse'],
            'salesman' => $user->name,
            'routes' => $routes,
            'customers' => $customersList,
            'rep_temp_customers' => $repTempCustomers,
            'load_requests' => $loadRequests,
            'items' => $items,
            'vehicle' => $vehicle,
            'counts' => [
                'territories_count' => $this->getTerritoryRoutesCounts($resources) ? count($this->getTerritoryRoutesCounts($resources)) : 0,
                'routes_count' => $counts['routes'],
                'customers_count' => $counts['customers'],
                'items_count' => count($items),
                'load_requests_count' => count($loadRequests),
            ],
            'downloaded_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Returns customer debit/credit totals using invoices and approved receipts.
     * Payments linked to invoices are already represented by paid_amount.
     */
    private function customerBalances(int $companyId): array
    {
        $invoices = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('customer_id, COALESCE(SUM(net_total), 0) AS debit_amount, COALESCE(SUM(paid_amount), 0) AS invoice_credit')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $receipts = DB::table('collections')
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNull('sales_invoice_id')
            ->selectRaw('customer_id, COALESCE(SUM(amount), 0) AS standalone_credit')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $ledger = DB::table('customer_ledger')
            ->selectRaw('customer_id, COALESCE(SUM(debit), 0) AS ledger_debit, COALESCE(SUM(credit), 0) AS ledger_credit')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $balances = [];
        $customerIds = $invoices->keys()
            ->merge($receipts->keys())
            ->merge($ledger->keys())
            ->unique();

        foreach ($customerIds as $customerId) {
            $debit = (float) ($invoices[$customerId]->debit_amount ?? 0)
                + (float) ($ledger[$customerId]->ledger_debit ?? 0);
            $credit = (float) ($invoices[$customerId]->invoice_credit ?? 0)
                + (float) ($receipts[$customerId]->standalone_credit ?? 0)
                + (float) ($ledger[$customerId]->ledger_credit ?? 0);
            $balances[(int) $customerId] = [
                'debit_amount' => round($debit, 2),
                'credit_amount' => round($credit, 2),
                'balance' => round($debit - $credit, 2),
            ];
        }

        return $balances;
    }

    public function bankAccounts(Request $request)
    {
        $user = $request->user();
        $accounts = DB::table('bank_accounts')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'bank_name', 'branch_name', 'account_name', 'account_no', 'current_balance')
            ->orderBy('bank_name')
            ->get();
        return response()->json(['data' => $accounts]);
    }

    /**
     * Create collection records for a handheld sale from a payments array.
     * Each payment: ['method' => 'cash'|'bank_transfer'|'customer_balance',
     * 'amount' => float, 'bank_account_id' => int?].
     * Returns the total collected amount (added to the invoice paid_amount).
     */
    private function createSaleCollections(array $payments, SalesInvoice $invoice, int $customerId, $user, $employee): float
    {
        if (empty($payments)) {
            return 0;
        }

        $balances = $this->customerBalances($user->company_id);
        $bal = $balances[$customerId] ?? ['balance' => 0];
        $availableCredit = ($bal['balance'] ?? 0) < 0 ? -(float) ($bal['balance']) : 0;

        $totalPaid = 0;
        $now = now();

        foreach ($payments as $p) {
            $method = $p['method'] ?? 'cash';
            $amount = (float) ($p['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $pm = DB::table('payment_methods')
                ->where('code', $method)
                ->where('is_active', true)
                ->first();
            $paymentMethodId = $pm?->id;

            $bankAccountId = null;
            if ($method === 'bank_transfer') {
                $bankAccountId = (int) ($p['bank_account_id'] ?? 0);
                $bank = DB::table('bank_accounts')
                    ->where('id', $bankAccountId)
                    ->where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$bank) {
                    $bank = DB::table('bank_accounts')
                        ->where('company_id', $user->company_id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->first();
                    $bankAccountId = $bank?->id;
                }
                if ($bankAccountId) {
                    DB::table('bank_accounts')->where('id', $bankAccountId)->increment('current_balance', $amount);
                }
            }

            if ($method === 'customer_balance') {
                if ($amount > $availableCredit) {
                    $amount = $availableCredit;
                }
                if ($amount <= 0) {
                    continue;
                }
                DB::table('customer_ledger')->insert([
                    'customer_id' => $customerId,
                    'transaction_date' => $now->toDateString(),
                    'reference_type' => 'balance_payment',
                    'reference_id' => $invoice->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'balance' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('collections')->insert([
                'company_id' => $user->company_id,
                'branch_id' => $invoice->branch_id,
                'collection_no' => 'HH-' . $now->format('YmdHis') . '-' . $invoice->id . '-' . rand(100, 999),
                'collection_date' => $now->toDateString(),
                'collection_time' => $now->format('H:i:s'),
                'sales_rep_id' => $employee->id,
                'customer_id' => $customerId,
                'sales_invoice_id' => $invoice->id,
                'payment_method_id' => $paymentMethodId,
                'bank_account_id' => $bankAccountId,
                'amount' => $amount,
                'collection_type' => 'receipt',
                'reference_no' => null,
                'notes' => $method === 'customer_balance'
                    ? 'تحصيل من رصيد العميل'
                    : ($method === 'bank_transfer' ? 'تحويل بنكي' : 'نقدي'),
                'status' => 'approved',
                'created_by' => $employee->id,
                'approved_by' => $employee->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $totalPaid += $amount;
        }

        return $totalPaid;
    }

    /**
     * Save payment methods to the sales_invoice_payment_methods table.
     * This provides a structured record of how an invoice was paid,
     * separate from the collections table which tracks actual receipts.
     */
    private function saveInvoicePaymentMethods(array $payments, SalesInvoice $invoice, int $companyId): void
    {
        if (empty($payments)) {
            return;
        }

        // Delete existing payment methods for this invoice (for updates)
        SalesInvoicePaymentMethod::where('sales_invoice_id', $invoice->id)->delete();

        foreach ($payments as $p) {
            $method = $p['method'] ?? 'cash';
            $amount = (float) ($p['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $pm = DB::table('payment_methods')
                ->where('code', $method)
                ->where('is_active', true)
                ->first();

            $bankAccountId = null;
            if ($method === 'bank_transfer') {
                $bankAccountId = (int) ($p['bank_account_id'] ?? 0);
                $bank = DB::table('bank_accounts')
                    ->where('id', $bankAccountId)
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$bank) {
                    $bank = DB::table('bank_accounts')
                        ->where('company_id', $companyId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->first();
                    $bankAccountId = $bank?->id;
                }
            }

            SalesInvoicePaymentMethod::create([
                'company_id' => $companyId,
                'sales_invoice_id' => $invoice->id,
                'payment_method_id' => $pm?->id,
                'bank_account_id' => $bankAccountId,
                'amount' => $amount,
                'method_code' => $method,
                'notes' => $method === 'customer_balance'
                    ? 'تحصيل من رصيد العميل'
                    : ($method === 'bank_transfer' ? 'تحويل بنكي' : 'نقدي'),
            ]);
        }
    }

    /**
     * Resolve the handheld sale price for an issue order's items.
     * Priority: stored sales_price > item_units.sale_price (sales/default unit)
     * > stored purchase_price. Falls back so a price always appears when one exists.
     */
    private function issueOrderHandheldItems(int $issueOrderId): array
    {
        $issueItems = DB::table('issue_order_items')
            ->where('issue_order_id', $issueOrderId)
            ->whereNull('deleted_at')
            ->get();

        $itemIds = $issueItems->pluck('item_id')->unique()->all();
        $unitPriceMap = [];
        $purchasePriceMap = [];
        if ($itemIds) {
            $units = DB::table('item_units')->whereIn('item_id', $itemIds)->get();
            foreach ($units as $u) {
                if ($u->is_sales_unit && (float) $u->sale_price > 0) {
                    $unitPriceMap[$u->item_id] = (float) $u->sale_price;
                } elseif (!isset($unitPriceMap[$u->item_id])) {
                    $unitPriceMap[$u->item_id] = (float) $u->sale_price;
                }
                if ((float) $u->purchase_price > 0 && !isset($purchasePriceMap[$u->item_id])) {
                    $purchasePriceMap[$u->item_id] = (float) $u->purchase_price;
                }
            }
        }

        return $issueItems->map(function ($item) use ($unitPriceMap, $purchasePriceMap) {
            $product = DB::table('items')->where('id', $item->item_id)->first();
            $stored = (float) ($item->sales_price ?? 0);
            $price = $stored > 0
                ? $stored
                : ($unitPriceMap[$item->item_id] ?? (float) ($item->purchase_price ?? 0));

            $purchasePrice = $purchasePriceMap[$item->item_id] ?? (float) ($item->purchase_price ?? 0);

            $qty = (float) ($item->issued_quantity ?? 0);
            if ($qty <= 0) {
                $baseQty = (float) ($item->base_quantity ?? 0);
                if ($baseQty > 0) {
                    $qty = $baseQty;
                } elseif ((float) ($item->load_quantity ?? 0) > 0) {
                    $qty = (float) $item->load_quantity;
                }
            }

            return [
                'item_id' => $item->item_id,
                'item_name' => $product ? ($product->name_ar ?? $product->name_en ?? '') : '',
                'item_code' => $product ? $product->code : '',
                'quantity' => $qty,
                'unit_price' => $price,
                'purchase_price' => $purchasePrice,
                'line_total' => $item->total_amount ?? 0,
            ];
        })->all();
    }

    /**
     * دالة معالجة: loadOrders — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function loadOrders(Request $request)
    {
        $user = $request->user();
        $resources = $this->getAssignmentResources($user->id);
        $employeeId = $request->input('user_id') ?? $resources['user_id'];

        if (!$employeeId) {
            return response()->json(['open' => [], 'closed' => []]);
        }

        $issueOrders = DB::table('issue_orders')
            ->where('issue_orders.user_id', $employeeId)
            ->whereNull('issue_orders.deleted_at')
            ->leftJoin('load_requests', 'issue_orders.load_request_id', '=', 'load_requests.id')
            ->select(
                'issue_orders.*',
                'load_requests.request_no as load_request_no',
                'load_requests.status as load_request_status',
                'load_requests.total_quantity as load_total_quantity',
                'load_requests.total_amount as load_total_amount'
            )
            ->get();

        $open = [];
        $closed = [];

        foreach ($issueOrders as $io) {
            $returnOrders = DB::table('return_orders')
                ->where('issue_order_id', $io->id)
                ->whereNull('deleted_at')
                ->get();

            $hasReturn = $returnOrders->isNotEmpty();
            $totalReturnedQty = $returnOrders->sum('total_quantity');

            $isClosed = in_array($io->status, ['cancelled', 'closed', 'delivered'])
                || !empty($io->received_by)
                || ($io->load_request_id && in_array(($io->load_request_status ?? null), ['cancelled', 'closed']));

            $items = $this->issueOrderHandheldItems($io->id);

            $orderData = [
                'id' => $io->id,
                'issue_no' => $io->issue_no,
                'load_request_id' => $io->load_request_id,
                'load_request_no' => $io->load_request_no,
                'load_request_status' => $io->load_request_status ?? $io->status,
                'issue_date' => $io->issue_date,
                'status' => $io->status,
                'total_items_count' => $io->total_items_count,
                'total_quantity' => $io->total_quantity,
                'total_amount' => $io->total_amount,
                'returned_quantity' => $totalReturnedQty,
                'items' => $items,
            ];

            if ($hasReturn || $isClosed) {
                $closed[] = $orderData;
            } else {
                $open[] = $orderData;
            }
        }

        return response()->json([
            'open' => $open,
            'closed' => $closed,
            'open_count' => count($open),
            'closed_count' => count($closed),
        ]);
    }

    public function loadOrder(Request $request, string $loadRequestNo)
    {
        $user = $request->user();
        $employeeId = (int) DB::table('employees')->where('id', $user->id)->value('id');
        if (!$employeeId) {
            return response()->json(['message' => 'الموظف غير موجود'], 404);
        }

        $loadRequest = DB::table('load_requests')
            ->where('request_no', $loadRequestNo)
            ->where('employee_id', $employeeId)
            ->whereNull('deleted_at')
            ->first();

        if (!$loadRequest) {
            return response()->json(['message' => 'أمر التحميل غير موجود'], 404);
        }

        $status = $loadRequest->status;
        if (!in_array($status, ['approved', 'loading'])) {
            return response()->json([
                'message' => 'لا يمكن تحميل الأمر في الحالة الحالية',
            ], 422);
        }

        $issueOrder = DB::table('issue_orders')
            ->where('load_request_id', $loadRequest->id)
            ->where('employee_id', $employeeId)
            ->whereNull('deleted_at')
            ->first();

        if (!$issueOrder) {
            return response()->json(['message' => 'لم يتم العثور على أمر صرف لهذا الطلب'], 404);
        }

        DB::transaction(function () use ($user, $loadRequest, $issueOrder, $employeeId) {
            $warehouseId = $loadRequest->warehouse_id;
            $companyId = $user->company_id;

            $type = \App\Models\InventoryTransactionType::where('code', 'TRANSFER_TO_REP')->first();
            if (!$type) {
                $type = \App\Models\InventoryTransactionType::firstOrCreate(
                    ['code' => 'TRANSFER_TO_REP'],
                    ['name' => 'تحميل للمندوب', 'effect' => 'subtraction', 'is_active' => true]
                );
            }

            $txn = \App\Models\InventoryTransaction::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\InventoryTransaction::nextTransactionNo($companyId),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => \App\Models\LoadRequest::class,
                'reference_id' => $loadRequest->id,
                'notes' => "تحميل أمر التحميل {$loadRequest->request_no} من المخزن",
                'status' => 'posted',
                'created_by' => $employeeId,
            ]);

            $items = DB::table('issue_order_items')
                ->where('issue_order_id', $issueOrder->id)
                ->whereNull('deleted_at')
                ->get();

            $unitService = app(\App\Services\UnitConversionService::class);

            foreach ($items as $item) {
                $baseQty = (float) ($item->base_quantity ?? 0);
                if ($baseQty <= 0) {
                    $cf = (float) ($item->conversion_factor ?? 1);
                    $baseQty = (float) ($item->issued_quantity ?? 0) * ($cf > 0 ? $cf : 1);
                }
                if ($baseQty <= 0) {
                    continue;
                }

                $baseUnitId = $unitService->getBaseUnitId($item->item_id) ?? $item->unit_id;

                \App\Models\InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $baseUnitId,
                    'conversion_factor' => (float) ($item->conversion_factor ?? 1),
                    'qty' => $baseQty,
                    'unit_cost' => $item->purchase_price,
                    'total_cost' => $item->total_amount,
                    'from_location_type' => 'warehouse',
                    'from_location_id' => $warehouseId,
                    'to_location_type' => 'rep',
                    'to_location_id' => $employeeId,
                ]);

                $distribution = \App\Models\RepItemDistribution::where('company_id', $companyId)
                    ->where('user_id', $employeeId)
                    ->where('item_id', $item->item_id)
                    ->where('issue_order_id', $issueOrder->id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($distribution) {
                    $distribution->update([
                        'loaded_qty' => $distribution->loaded_qty + $baseQty,
                        'remaining_qty' => $distribution->remaining_qty + $baseQty,
                    ]);
                } else {
                    \App\Models\RepItemDistribution::create([
                        'company_id' => $companyId,
                        'user_id' => $employeeId,
                        'employee_id' => $employeeId,
                        'item_id' => $item->item_id,
                        'issue_order_id' => $issueOrder->id,
                        'load_request_id' => $loadRequest->id,
                        'loaded_qty' => $baseQty,
                        'sold_qty' => 0,
                        'remaining_qty' => $baseQty,
                        'status' => 'active',
                    ]);
                }
            }

            DB::table('load_requests')->where('id', $loadRequest->id)->update([
                'status' => 'loaded',
                'updated_at' => now(),
            ]);

            DB::table('issue_orders')->where('id', $issueOrder->id)->update([
                'status' => 'loaded',
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'تم تحميل الأمر بنجاح']);
    }

    public function complementaryOrders(Request $request)
    {
        $user = $request->user();
        $resources = $this->getAssignmentResources($user->id);
        $employeeId = $request->input('user_id') ?? $resources['user_id'];

        if (!$employeeId) {
            return response()->json(['orders' => []]);
        }

        $complementaryRequests = DB::table('load_requests')
            ->where('load_requests.user_id', $employeeId)
            ->where('load_requests.load_type', 'complementary')
            ->whereIn('load_requests.status', ['approved', 'loading'])
            ->whereNull('load_requests.deleted_at')
            ->get();

        $orders = [];

        foreach ($complementaryRequests as $lr) {
            $issueOrder = DB::table('issue_orders')
                ->where('load_request_id', $lr->id)
                ->whereNull('deleted_at')
                ->first();

            if (!$issueOrder) continue;

            $hasReturn = DB::table('return_orders')
                ->where('issue_order_id', $issueOrder->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasReturn) continue;

            $items = $this->issueOrderHandheldItems($issueOrder->id);

            $parentRequest = null;
            if ($lr->parent_load_request_id) {
                $parent = DB::table('load_requests')->where('id', $lr->parent_load_request_id)->first();
                if ($parent) {
                    $parentRequest = [
                        'id' => $parent->id,
                        'request_no' => $parent->request_no,
                    ];
                }
            }

            $orders[] = [
                'id' => $issueOrder->id,
                'issue_no' => $issueOrder->issue_no,
                'load_request_id' => $lr->id,
                'load_request_no' => $lr->request_no,
                'parent_request' => $parentRequest,
                'load_type' => $lr->load_type,
                'issue_date' => $issueOrder->issue_date,
                'status' => $issueOrder->status,
                'total_items_count' => $issueOrder->total_items_count,
                'total_quantity' => $issueOrder->total_quantity,
                'total_amount' => $issueOrder->total_amount,
                'items' => $items,
            ];
        }

        return response()->json(['orders' => $orders]);
    }

    public function updateLoadRequestStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,pending,approved,rejected,loading,loaded,completed,cancelled,closed',
        ]);

        $loadRequest = DB::table('load_requests')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$loadRequest) {
            return response()->json(['message' => 'الطلب غير موجود'], 404);
        }

        DB::table('load_requests')
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'تم تحديث الحالة بنجاح', 'status' => $validated['status']]);
    }

    public function cancelLoadRequest(Request $request, $id)
    {
        $user = $request->user();
        $employeeId = (int) DB::table('employees')->where('id', $user->id)->value('id');

        $loadRequest = DB::table('load_requests')
            ->where('id', $id)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$loadRequest) {
            return response()->json(['message' => 'أمر التحميل غير موجود'], 404);
        }

        $employeeId = (int) $loadRequest->user_id;

        $status = $loadRequest->status;
        if (!in_array($status, ['approved', 'loading', 'loaded'])) {
            return response()->json([
                'message' => 'لا يمكن إلغاء أمر التحميل في الحالة الحالية',
            ], 422);
        }

        $issueOrder = DB::table('issue_orders')
            ->where('load_request_id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$issueOrder) {
            DB::table('load_requests')->where('id', $id)->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'تم إلغاء أمر التحميل']);
        }

        DB::transaction(function () use ($user, $loadRequest, $issueOrder, $employeeId) {
            $warehouseId = $loadRequest->warehouse_id;
            $companyId = $user->company_id;

            $type = \App\Models\InventoryTransactionType::where('code', 'RETURN_TO_WAREHOUSE')->first();
            if (!$type) {
                $type = \App\Models\InventoryTransactionType::firstOrCreate(
                    ['code' => 'RETURN_TO_WAREHOUSE'],
                    ['name' => 'إرجاع لأمر التحميل للمخزن', 'effect' => 'addition', 'is_active' => true]
                );
            }

            $txn = \App\Models\InventoryTransaction::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\InventoryTransaction::nextTransactionNo($companyId),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => \App\Models\LoadRequest::class,
                'reference_id' => $loadRequest->id,
                'notes' => "إرجاع أمر التحميل {$loadRequest->request_no} للمخزن",
                'status' => 'posted',
                'created_by' => $employeeId,
            ]);

            $items = DB::table('issue_order_items')
                ->where('issue_order_id', $issueOrder->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($items as $item) {
                $baseQty = (float) ($item->base_quantity ?? 0);
                if ($baseQty <= 0) {
                    $cf = (float) ($item->conversion_factor ?? 1);
                    $baseQty = (float) ($item->issued_quantity ?? 0) * ($cf > 0 ? $cf : 1);
                }
                if ($baseQty <= 0) {
                    continue;
                }

                $unitService = app(\App\Services\UnitConversionService::class);
                $baseUnitId = $unitService->getBaseUnitId($item->item_id) ?? $item->unit_id;

                \App\Models\InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $baseUnitId,
                    'conversion_factor' => (float) ($item->conversion_factor ?? 1),
                    'qty' => $baseQty,
                    'unit_cost' => $item->purchase_price,
                    'total_cost' => $item->total_amount,
                    'from_location_type' => 'rep',
                    'from_location_id' => $employeeId,
                    'to_location_type' => 'warehouse',
                    'to_location_id' => $warehouseId,
                ]);

                $distribution = \App\Models\RepItemDistribution::where('company_id', $companyId)
                    ->where('user_id', $employeeId)
                    ->where('item_id', $item->item_id)
                    ->where('issue_order_id', $issueOrder->id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($distribution) {
                    $distribution->update([
                        'loaded_qty' => max(0, $distribution->loaded_qty - $baseQty),
                        'remaining_qty' => max(0, $distribution->remaining_qty - $baseQty),
                    ]);
                }
            }

            DB::table('issue_orders')->where('id', $issueOrder->id)->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

            DB::table('load_requests')->where('id', $loadRequest->id)->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'تم إلغاء أمر التحميل وإرجاع الكمية للمخزن']);
    }

    /**
     * دالة معالجة: salesmanProfile — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function salesmanProfile(Request $request)
    {
        $user = $request->user();
        $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();
        $resources = $this->getAssignmentResources($user->id);
        $counts = $this->getCounts($user, $defaultBranch, $resources);
        $territoryRoutes = $this->getTerritoryRoutesCounts($resources);

        return response()->json([
            'id' => $user->id,
            'usercode' => $user->usercode,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name_ar ?? $user->company->name,
            ] : null,
            'branch' => $defaultBranch ? [
                'id' => $defaultBranch->id,
                'name' => $defaultBranch->name_ar ?? $defaultBranch->name,
            ] : null,
            'warehouse' => $resources['warehouse'],
            'treasury' => $resources['treasury'],
            'sales_area' => $resources['sales_area'],
            'routes_count' => $counts['routes'],
            'customers_count' => $counts['customers'],
            'territory_routes' => $territoryRoutes,
        ]);
    }

    /**
     * دالة معالجة: startDayCounts — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function startDayCounts(Request $request)
    {
        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $routeIds = collect();
        $territoryIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('user_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('route_id')
                ->pluck('route_id');

            $territoryIds = DB::table('routes')
                ->whereIn('id', $routeIds)
                ->distinct('sales_territory_id')
                ->pluck('sales_territory_id');
        }

        $customersCount = 0;
        if ($routeIds->isNotEmpty()) {
            $customersCount = DB::table('route_customers')
                ->whereIn('route_id', $routeIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('customer_id')
                ->count('customer_id');
        }

        $itemsCount = DB::table('items')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        $productsCount = DB::table('items')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        return response()->json([
            'territories_count' => $territoryIds->count(),
            'routes_count' => $routeIds->count(),
            'customers_count' => $customersCount,
            'items_count' => $itemsCount,
            'products_count' => $productsCount,
        ]);
    }

    public function startDay(Request $request)
    {
        $request->validate([
            'start_km' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();

        if (!$employee) {
            return response()->json(['error' => 'employee not found'], 404);
        }

        $vehicleAssignment = DB::table('vehicle_assignments')
            ->where(function ($q) use ($employee) {
                $q->where('sales_rep_id', $employee->id)
                  ->orWhere('driver_id', $employee->id);
            })
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();

        if (!$vehicleAssignment) {
            return response()->json(['error' => 'no vehicle assigned'], 404);
        }

        $today = now()->toDateString();

        $existing = DB::table('vehicle_daily_shifts')
            ->where('vehicle_id', $vehicleAssignment->vehicle_id)
            ->where('sales_rep_id', $employee->id)
            ->where('shift_date', $today)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('vehicle_daily_shifts')
                ->where('id', $existing->id)
                ->update([
                    'start_km' => $request->start_km,
                    'start_time' => now()->toTimeString(),
                    'status' => 'IN_PROGRESS',
                ]);

            $shiftId = $existing->id;
        } else {
            $shiftId = DB::table('vehicle_daily_shifts')->insertGetId([
                'vehicle_id' => $vehicleAssignment->vehicle_id,
                'driver_id' => $vehicleAssignment->driver_id,
                'sales_rep_id' => $employee->id,
                'shift_date' => $today,
                'start_km' => $request->start_km,
                'start_time' => now()->toTimeString(),
                'status' => 'IN_PROGRESS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'shift_id' => $shiftId,
        ]);
    }

    /**
     * دالة معالجة: routesWithCustomers — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function routesWithCustomers(Request $request)
    {
        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $routeIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('user_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('route_id')
                ->pluck('route_id');
        }

        $routes = [];
        $customerBalances = $this->customerBalances($user->company_id);
        if ($routeIds->isNotEmpty()) {
            $routes = DB::table('routes')
                ->whereIn('routes.id', $routeIds)
                ->where('routes.is_active', true)
                ->whereNull('routes.deleted_at')
                ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
                ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'routes.sales_territory_id', 'sales_territories.name_ar as territory_name')
                ->get()
                ->map(function ($route) use ($customerBalances) {
                    $customerIds = DB::table('route_customers')
                        ->where('route_id', $route->id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->pluck('customer_id');

                    $customers = DB::table('customers')
                        ->whereIn('id', $customerIds)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->get(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line', 'latitude', 'longitude'])
                        ->map(function ($c) use ($customerBalances) {
                            return [
                                'id' => $c->id,
                                'code' => $c->code,
                                'name' => $c->name_ar ?? $c->name_en,
                                'phone' => $c->phone,
                                'mobile' => $c->mobile,
                                'address' => $c->address_line,
                                'latitude' => $c->latitude,
                                'longitude' => $c->longitude,
                                'debit_amount' => $customerBalances[(int) $c->id]['debit_amount'] ?? 0,
                                'credit_amount' => $customerBalances[(int) $c->id]['credit_amount'] ?? 0,
                                'balance' => $customerBalances[(int) $c->id]['balance'] ?? 0,
                            ];
                        });

                    return [
                        'id' => $route->id,
                        'code' => $route->code,
                        'name' => $route->name_ar ?? $route->name_en,
                        'territory_name' => $route->territory_name,
                        'sales_territory_id' => $route->sales_territory_id ?? 0,
                        'customers' => $customers,
                    ];
                })
                ->toArray();
        }

        return response()->json(['routes' => $routes]);
    }

    /**
     * دالة معالجة: nextCustomerCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function nextCustomerCode(Request $request)
    {
        $user = $request->user();

        $lastCode = DB::table('customers')
            ->where('company_id', $user->company_id)
            ->where('code', 'like', 'CU-%')
            ->whereNotNull('code')
            ->orderByRaw('CAST(SUBSTR(code, 4) AS INTEGER) DESC')
            ->value('code');

        $nextNumber = 1;
        if ($lastCode && preg_match('/CU-(\d+)/', $lastCode, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        $nextCode = 'CU-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return response()->json(['code' => $nextCode]);
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Handheld2) حسب الطلب.
     */
    private function getCounts(User $user, $defaultBranch, array $resources): array
    {
        $routeIds = $resources['route_ids'] ?? collect();

        if ($routeIds->isEmpty() && $defaultBranch) {
            $routeIds = DB::table('routes')
                ->where('branch_id', $defaultBranch->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('id');
        }

        if ($routeIds->isEmpty() && $user->company_id) {
            $routeIds = DB::table('routes')
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('id');
        }

        $routesCount = $routeIds->count();

        $customersCount = 0;
        if ($routeIds->isNotEmpty()) {
            $customersCount = DB::table('route_customers')
                ->whereIn('route_id', $routeIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('customer_id')
                ->count('customer_id');
        }

        return ['routes' => $routesCount, 'customers' => $customersCount];
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Handheld2) حسب الطلب.
     */
    private function getTerritoryRoutesCounts(array $resources): array
    {
        $routeIds = $resources['route_ids'] ?? collect();

        if ($routeIds->isEmpty()) {
            return [];
        }

        $routes = DB::table('routes')
            ->whereIn('routes.id', $routeIds)
            ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
            ->select('routes.id', 'routes.sales_territory_id', 'sales_territories.name_ar')
            ->get();

        $grouped = $routes->groupBy('sales_territory_id');
        $result = [];

        foreach ($grouped as $territoryId => $territoryRoutes) {
            $territoryName = $territoryRoutes->first()->name_ar ?? 'غير محدد';
            $routeIdsInTerritory = $territoryRoutes->pluck('id');

            $customersCount = DB::table('route_customers')
                ->whereIn('route_id', $routeIdsInTerritory)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('customer_id')
                ->count('customer_id');

            $result[] = [
                'territory_id' => $territoryId,
                'territory_name' => $territoryName,
                'routes_count' => $territoryRoutes->count(),
                'customers_count' => $customersCount,
            ];
        }

        return $result;
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Handheld2) حسب الطلب.
     */
    private function getAssignmentResources(int $userId): array
    {
        $employee = DB::table('employees')->where('id', $userId)->first();
        if (!$employee && DB::getSchemaBuilder()->hasColumn('employees', 'user_id')) {
            $employee = DB::table('employees')->where('user_id', $userId)->first();
        }

        if (!$employee) {
            return [
                'warehouse' => null,
                'treasury' => null,
                'sales_area' => null,
                'user_id' => null,
                'employee_id' => null,
                'route_ids' => collect(),
            ];
        }

        $routeIds = DB::table('route_schedules')
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->distinct('route_id')
            ->pluck('route_id');

        $assignment = DB::table('salesman_assignments')
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        $warehouse = null;
        $treasury = null;
        $salesArea = null;

        if ($assignment) {
            if ($assignment->warehouse_id) {
                $w = DB::table('warehouses')->where('id', $assignment->warehouse_id)->first();
                if ($w) {
                    $warehouse = ['id' => $w->id, 'name' => $w->name_ar ?? $w->name];
                }
            }

            if ($assignment->treasury_id) {
                $t = DB::table('treasuries')->where('id', $assignment->treasury_id)->first();
                if ($t) {
                    $treasury = ['id' => $t->id, 'name' => $t->name_ar ?? $t->name];
                }
            }

            if ($assignment->sales_territory_id) {
                $st = DB::table('sales_territories')->where('id', $assignment->sales_territory_id)->first();
                if ($st) {
                    $salesArea = $st->name_ar ?? $st->name;
                }
            }
        }

        return [
            'warehouse' => $warehouse,
            'treasury' => $treasury,
            'sales_area' => $salesArea,
            'user_id' => $employee->id,
            'employee_id' => $employee->id,
            'route_ids' => $routeIds,
        ];
    }

    /**
     * دالة معالجة: health — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function health()
    {
        return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
    }

    /**
     * دالة معالجة: syncPush — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function syncPush(Request $request)
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.uuid' => 'nullable|string',
            'records.*.client_uuid' => 'nullable|string',
            'records.*.entity_type' => 'required|string',
            'records.*.action' => 'required|string|in:create,update,delete',
            'records.*.payload' => 'required|array',
            'records.*.payload.client_uuid' => 'nullable|string',
        ]);

        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();
        if (!$employee && DB::getSchemaBuilder()->hasColumn('employees', 'user_id')) {
            $employee = DB::table('employees')->where('id', $user->id)->first();
        }

        if (!$employee) {
            return response()->json(['message' => 'الموظف غير موجود'], 404);
        }

        $results = [];

        foreach ($request->records as $record) {
            $clientUuid = $this->resolveClientUuid($record);

            try {
                if ($record['entity_type'] !== 'sale') {
                    Log::warning('[INVOICE SYNC] unsupported entity_type', [
                        'client_uuid' => $clientUuid,
                        'entity_type' => $record['entity_type'],
                    ]);
                    $results[] = [
                        'client_uuid' => $clientUuid,
                        'status' => 'skipped',
                        'message' => 'نوع غير مدعوم: ' . $record['entity_type'],
                    ];
                    continue;
                }

                if (!$clientUuid) {
                    $results[] = [
                        'client_uuid' => null,
                        'status' => 'error',
                        'message' => 'client_uuid مطلوب لمزامنة الفاتورة',
                    ];
                    continue;
                }

                switch ($record['action']) {
                    case 'create':
                        $results[] = $this->syncCreateSale($user, $employee, $record, $clientUuid);
                        break;
                    case 'update':
                        $results[] = $this->syncUpdateSale($user, $employee, $record, $clientUuid);
                        break;
                    case 'delete':
                        $results[] = $this->syncDeleteSale($user, $employee, $record, $clientUuid);
                        break;
                }
            } catch (\Exception $e) {
                Log::error('[INVOICE SYNC] exception', [
                    'client_uuid' => $clientUuid,
                    'action' => $record['action'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Resolve the stable offline identifier used as the sync key.
     * Accepts client_uuid from the record or payload, falling back to the
     * legacy record-level `uuid` the Flutter app currently sends.
     */
    private function resolveClientUuid(array $record): ?string
    {
        $payload = $record['payload'] ?? [];

        return $record['client_uuid']
            ?? ($payload['client_uuid'] ?? null)
            ?? ($record['uuid'] ?? null)
            ?? null;
    }

    /**
     * دالة معالجة: syncCreateSale — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function syncCreateSale($user, $employee, $record, string $clientUuid)
    {
        Log::info('[INVOICE SYNC] action=create', ['client_uuid' => $clientUuid, 'company_id' => $user->company_id]);

        $payload = $record['payload'];

        $customer_id = $payload['customer_id'] ?? null;
        $items = $payload['items'] ?? [];

        if (!$customer_id || empty($items)) {
            return [
                'success' => false,
                'client_uuid' => $clientUuid,
                'status' => 'error',
                'message' => 'بيانات ناقصة: customer_id/items مطلوبة',
            ];
        }

        return DB::transaction(function () use ($user, $employee, $payload, $record, $customer_id, $items, $clientUuid) {
            // Idempotent: a retry of the same create (same client_uuid + company) must
            // never produce a second invoice. The UNIQUE(company_id, client_uuid) index
            // also guards against a race between two concurrent identical creates.
            $existing = SalesInvoice::where('company_id', $user->company_id)
                ->where('client_uuid', $clientUuid)
                ->first();

            if ($existing) {
                Log::info('[INVOICE SYNC] existing invoice found (create is idempotent)', [
                    'client_uuid' => $clientUuid,
                    'invoice_id' => $existing->id,
                    'invoice_no' => $existing->invoice_no,
                ]);

                return [
                    'success' => true,
                    'client_uuid' => $clientUuid,
                    'status' => 'already_synced',
                    'invoice_id' => $existing->id,
                    'invoice_no' => $existing->invoice_no,
                ];
            }

            [$itemsData, $subtotal, $taxTotal] = $this->buildInvoiceItems($user, $employee, $payload, $items);

            $netTotal = $payload['net_total'] ?? ($subtotal + $taxTotal);
            $paidAmount = $payload['paid_amount'] ?? $netTotal;
            $remainingAmount = $payload['remaining_amount'] ?? max(0, $netTotal - $paidAmount);

            $warehouse = Warehouse::where('company_id', $user->company_id)
                ->where('is_active', true)->first();

            // Use the invoice number supplied by the handheld (device numbering).
            // Fall back to server-side generation only if it is missing or a
            // duplicate for this company.
            $invoiceNo = $this->resolveHandheldInvoiceNo($user->company_id, $payload);
            $invoice = SalesInvoice::create([
                'company_id' => $user->company_id,
                'branch_id' => $payload['branch_id'] ?? null,
                'warehouse_id' => $warehouse?->id,
                'treasury_id' => $payload['treasury_id'] ?? null,
                'load_request_id' => $payload['load_request_id'] ?? null,
                'issue_order_id' => $payload['issue_order_id'] ?? ($itemsData[0]['issue_order_id'] ?? null),
                'route_id' => $payload['route_id'] ?? null,
                'sales_territory_id' => $payload['sales_territory_id'] ?? null,
                'sales_rep_id' => $payload['sales_rep_id'] ?? $employee->id,
                'customer_id' => $customer_id,
                'client_uuid' => $clientUuid,
                'invoice_no' => $invoiceNo,
                'temp_invoice_no' => $payload['temp_invoice_no'] ?? $payload['invoice_no'] ?? null,
                'source' => $payload['source'] ?? 'mobile',
                'mode' => $payload['mode'] ?? 'offline',
                'device_id' => $payload['device_id'] ?? null,
                'sync_status' => 'synced',
                'synced_at' => now(),
                'number_series_id' => $payload['number_series_id'] ?? null,
                'invoice_date' => $payload['invoice_date'] ?? now()->toDateString(),
                'invoice_time' => $payload['invoice_time'] ?? now()->format('H:i:s'),
                'subtotal' => $payload['subtotal'] ?? $subtotal,
                'item_discount_total' => $payload['item_discount_total'] ?? 0,
                'invoice_discount_total' => $payload['invoice_discount_total'] ?? 0,
                'tax_total' => $payload['tax_total'] ?? $taxTotal,
                'incentive_total' => $payload['incentive_total'] ?? 0,
                'net_total' => $netTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $payload['status'] ?? 'approved',
                'notes' => $payload['notes'] ?? '',
                'created_by' => $employee->id,
            ]);

            foreach ($itemsData as $itemData) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'] ?? null,
                    'qty' => $itemData['qty'],
                    'bonus_qty' => 0,
                    'price' => $itemData['price'],
                    'gross_amount' => $itemData['gross_amount'],
                    'discount_type' => null,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => $itemData['tax_percent'],
                    'tax_amount' => $itemData['tax_amount'],
                    'net_amount' => $itemData['net_amount'],
                ]);
            }

            $payments = $payload['payments'] ?? [];
            if (!empty($payments)) {
                $collectedTotal = $this->createSaleCollections(
                    $payments,
                    $invoice,
                    (int) $customer_id,
                    $user,
                    $employee
                );
                if ($collectedTotal > 0) {
                    $paidAmount = $collectedTotal;
                    $remainingAmount = max(0, $netTotal - $paidAmount);
                    $invoice->update([
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $remainingAmount,
                    ]);
                }

                $this->saveInvoicePaymentMethods($payments, $invoice, $user->company_id);
            }

            try { $invoice->post(); } catch (\Exception $e) {
                Log::warning('[INVOICE SYNC] post failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }

            $this->applyDistribution($user, $employee, $itemsData);

            Log::info('[INVOICE SYNC] created', [
                'client_uuid' => $clientUuid,
                'server_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);

            return [
                'success' => true,
                'client_uuid' => $clientUuid,
                'status' => 'synced',
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ];
        });
    }

    /**
     * دالة معالجة: syncUpdateSale — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function syncUpdateSale($user, $employee, $record, string $clientUuid)
    {
        Log::info('[INVOICE SYNC] action=update', ['client_uuid' => $clientUuid, 'company_id' => $user->company_id]);

        $payload = $record['payload'];

        // Sync key is company_id + client_uuid (never invoice_no). Scoped to the
        // authenticated user's company so a user cannot touch another company's invoice.
        $invoice = SalesInvoice::where('company_id', $user->company_id)
            ->where('client_uuid', $clientUuid)
            ->first();

        if (!$invoice) {
            Log::warning('[INVOICE SYNC] update target not found', ['client_uuid' => $clientUuid]);
            return [
                'success' => false,
                'client_uuid' => $clientUuid,
                'status' => 'not_found',
                'message' => 'الفاتورة غير موجودة على السيرفر (client_uuid: ' . $clientUuid . ')',
            ];
        }

        $customer_id = $payload['customer_id'] ?? $invoice->customer_id;
        $items = $payload['items'] ?? [];

        if (!$customer_id || empty($items)) {
            return [
                'success' => false,
                'client_uuid' => $clientUuid,
                'status' => 'error',
                'message' => 'بيانات ناقصة: customer_id/items مطلوبة',
            ];
        }

        return DB::transaction(function () use ($user, $employee, $payload, $record, $customer_id, $items, $invoice, $clientUuid) {
            // Reverse the previously recorded rep distribution for the old line items
            // so we don't double-count sold/remaining quantities.
            foreach ($invoice->items as $oldItem) {
                $this->adjustDistribution($user, $employee, $oldItem->item_id, -$oldItem->qty);
            }

            // Replace line items with the edited ones.
            $invoice->items()->delete();

            [$itemsData, $subtotal, $taxTotal] = $this->buildInvoiceItems($user, $employee, $payload, $items);

            // Recompute totals from the source of truth (the item lines), not blindly
            // from the client payload — but honour explicit client totals when provided.
            $netTotal = $payload['net_total'] ?? ($subtotal + $taxTotal);
            $paidAmount = $payload['paid_amount'] ?? $netTotal;
            $remainingAmount = $payload['remaining_amount'] ?? max(0, $netTotal - $paidAmount);

            $newStatus = $invoice->status === 'cancelled' ? 'approved' : $invoice->status;

            $invoice->update([
                'branch_id' => $payload['branch_id'] ?? $invoice->branch_id,
                'warehouse_id' => $payload['warehouse_id'] ?? $invoice->warehouse_id,
                'treasury_id' => $payload['treasury_id'] ?? $invoice->treasury_id,
                'load_request_id' => $payload['load_request_id'] ?? $invoice->load_request_id,
                'issue_order_id' => $payload['issue_order_id'] ?? ($itemsData[0]['issue_order_id'] ?? null),
                'route_id' => $payload['route_id'] ?? $invoice->route_id,
                'sales_territory_id' => $payload['sales_territory_id'] ?? $invoice->sales_territory_id,
                'sales_rep_id' => $payload['sales_rep_id'] ?? $invoice->sales_rep_id,
                'customer_id' => $customer_id,
                'temp_invoice_no' => $payload['temp_invoice_no'] ?? $invoice->temp_invoice_no,
                'source' => $payload['source'] ?? $invoice->source,
                'mode' => $payload['mode'] ?? $invoice->mode,
                'device_id' => $payload['device_id'] ?? $invoice->device_id,
                'sync_status' => 'synced',
                'synced_at' => now(),
                'number_series_id' => $payload['number_series_id'] ?? $invoice->number_series_id,
                'invoice_date' => $payload['invoice_date'] ?? $invoice->invoice_date,
                'invoice_time' => $payload['invoice_time'] ?? $invoice->invoice_time,
                'subtotal' => $payload['subtotal'] ?? $subtotal,
                'item_discount_total' => $payload['item_discount_total'] ?? 0,
                'invoice_discount_total' => $payload['invoice_discount_total'] ?? 0,
                'tax_total' => $payload['tax_total'] ?? $taxTotal,
                'incentive_total' => $payload['incentive_total'] ?? 0,
                'net_total' => $netTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $newStatus,
                'notes' => $payload['notes'] ?? $invoice->notes,
                'created_by' => $invoice->created_by,
                // company_id, client_uuid and invoice_no are intentionally NOT updated.
            ]);

            // If the invoice had been cancelled, reopen it (no-op for mobile stock) and
            // keep it approved rather than silently leaving it cancelled.
            if ($invoice->status === 'cancelled') {
                try { $invoice->reopen(); } catch (\Exception $e) {
                    Log::warning('[INVOICE SYNC] reopen failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
                }
                $invoice->update(['status' => 'approved']);
            }

            foreach ($itemsData as $itemData) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'] ?? null,
                    'qty' => $itemData['qty'],
                    'bonus_qty' => 0,
                    'price' => $itemData['price'],
                    'gross_amount' => $itemData['gross_amount'],
                    'discount_type' => null,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => $itemData['tax_percent'],
                    'tax_amount' => $itemData['tax_amount'],
                    'net_amount' => $itemData['net_amount'],
                ]);
            }

            $payments = $payload['payments'] ?? [];
            if (!empty($payments)) {
                $collectedTotal = $this->createSaleCollections(
                    $payments,
                    $invoice,
                    (int) $customer_id,
                    $user,
                    $employee
                );
                if ($collectedTotal > 0) {
                    $paidAmount = $collectedTotal;
                    $remainingAmount = max(0, $netTotal - $paidAmount);
                    $invoice->update([
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $remainingAmount,
                    ]);
                }

                $this->saveInvoicePaymentMethods($payments, $invoice, $user->company_id);
            } else {
                // If no payments sent, clear existing payment methods
                SalesInvoicePaymentMethod::where('sales_invoice_id', $invoice->id)->delete();
            }

            $this->applyDistribution($user, $employee, $itemsData);

            Log::info('[INVOICE SYNC] updated', [
                'client_uuid' => $clientUuid,
                'server_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);

            return [
                'success' => true,
                'client_uuid' => $clientUuid,
                'status' => 'synced',
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ];
        });
    }

    /**
     * Use the handheld-supplied invoice number when present and unique for the
     * company; otherwise return null so the model's creating event generates one.
     */
    private function resolveHandheldInvoiceNo(int $companyId, array $payload): ?string
    {
        $candidate = $payload['invoice_no'] ?? $payload['temp_invoice_no'] ?? null;

        if (is_string($candidate) && trim($candidate) !== '') {
            $exists = SalesInvoice::where('company_id', $companyId)
                ->where('invoice_no', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * دالة معالجة: buildInvoiceItems — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function buildInvoiceItems($user, $employee, $payload, $items)
    {
        $subtotal = 0;
        $taxTotal = 0;
        $itemsData = [];

        foreach ($items as $item) {
            $qty = $item['quantity'] ?? $item['qty'] ?? 0;
            $price = $item['unit_price'] ?? $item['price'] ?? 0;
            $taxPercent = $item['tax_percent'] ?? 0;
            $lineTotal = $qty * $price;
            $taxAmount = $lineTotal * ($taxPercent / 100);
            $subtotal += $lineTotal;
            $taxTotal += $taxAmount;

            $itemCode = $item['item_code'] ?? null;
            $serverItem = null;
            if ($itemCode) {
                $serverItem = DB::table('items')->where('code', $itemCode)->where('is_active', true)->first();
            }
            if (!$serverItem && !empty($item['item_id'])) {
                $serverItem = DB::table('items')->where('id', $item['item_id'])->where('is_active', true)->first();
            }

            $realItemId = $serverItem?->id ?? $item['item_id'] ?? null;

            $itemsData[] = [
                'item_id' => $realItemId,
                'qty' => $qty,
                'price' => $price,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'gross_amount' => $lineTotal,
                'net_amount' => $lineTotal + $taxAmount,
                'unit_id' => $item['unit_id'] ?? $serverItem?->base_unit_id ?? null,
                'issue_order_id' => $item['issue_order_id'] ?? null,
            ];
        }

        return [$itemsData, $subtotal, $taxTotal];
    }

    /**
     * دالة معالجة: applyDistribution — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function applyDistribution($user, $employee, $itemsData)
    {
        foreach ($itemsData as $itemData) {
            $this->adjustDistribution($user, $employee, $itemData['item_id'], $itemData['qty']);
        }
    }

    /**
     * دالة معالجة: adjustDistribution — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function adjustDistribution($user, $employee, $itemId, $deltaQty)
    {
        if (!$itemId || $deltaQty == 0) {
            return;
        }

        $distribution = RepItemDistribution::where('company_id', $user->company_id)
            ->where('user_id', $employee->id)
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$distribution) {
            return;
        }

        if ($deltaQty > 0) {
            $distribution->update([
                'sold_qty' => $distribution->sold_qty + $deltaQty,
                'remaining_qty' => max(0, $distribution->remaining_qty - $deltaQty),
            ]);
        } else {
            $distribution->update([
                'sold_qty' => max(0, $distribution->sold_qty + $deltaQty),
                'remaining_qty' => $distribution->remaining_qty - $deltaQty,
            ]);
        }
    }

    /**
     * دالة معالجة: syncDeleteSale — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    private function syncDeleteSale($user, $employee, $record, string $clientUuid)
    {
        Log::info('[INVOICE SYNC] action=delete', ['client_uuid' => $clientUuid, 'company_id' => $user->company_id]);

        // Include soft-deleted rows so a previously deleted invoice stays idempotent.
        // Always scoped to the authenticated user's company.
        $invoice = SalesInvoice::withTrashed()
            ->where('company_id', $user->company_id)
            ->where('client_uuid', $clientUuid)
            ->first();

        if (!$invoice) {
            Log::warning('[INVOICE SYNC] delete target not found', ['client_uuid' => $clientUuid]);
            return [
                'success' => false,
                'client_uuid' => $clientUuid,
                'status' => 'not_found',
                'message' => 'الفاتورة غير موجودة على السيرفر (client_uuid: ' . $clientUuid . ')',
            ];
        }

        // Idempotent: a second delete of an already-cancelled / already-deleted invoice
        // is a success, never an error.
        if ($invoice->trashed() || $invoice->status === 'cancelled') {
            Log::info('[INVOICE SYNC] delete already applied (idempotent)', [
                'client_uuid' => $clientUuid,
                'invoice_id' => $invoice->id,
            ]);
            return [
                'success' => true,
                'client_uuid' => $clientUuid,
                'status' => 'already_deleted',
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ];
        }

        // Use the system's cancellation/reversal logic (not a hard delete) so stock,
        // accounting and balances remain consistent. For mobile-source invoices the
        // stock reverse is a no-op, but the status reversal is the source of truth.
        try {
            $invoice->cancel();
        } catch (\Exception $e) {
            Log::warning('[INVOICE SYNC] cancel failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'client_uuid' => $clientUuid,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        Log::info('[INVOICE SYNC] deleted (cancelled)', [
            'client_uuid' => $clientUuid,
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
        ]);

        return [
            'success' => true,
            'client_uuid' => $clientUuid,
            'status' => 'synced',
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
        ];
    }

    /**
     * دالة معالجة: syncPull — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function syncPull(Request $request)
    {
        $request->validate([
            'cursors' => 'nullable|array',
        ]);

        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $routeIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('user_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('route_id')
                ->pluck('route_id');
        }

        $customers = [];
        if ($routeIds->isNotEmpty()) {
            $customerIds = DB::table('route_customers')
                ->whereIn('route_id', $routeIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('customer_id')
                ->pluck('customer_id');

            $customers = DB::table('customers')
                ->whereIn('id', $customerIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get(['id', 'code', 'name_ar', 'phone', 'address_line', 'latitude', 'longitude'])
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'code' => $c->code,
                        'name' => $c->name_ar,
                        'phone' => $c->phone,
                        'address' => $c->address_line,
                        'latitude' => $c->latitude,
                        'longitude' => $c->longitude,
                        '_sync_action' => 'insert',
                    ];
                })
                ->toArray();
        }

        $products = DB::table('items')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->limit(50)
            ->get(['id', 'code', 'name_ar'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name_ar,
                    '_sync_action' => 'insert',
                ];
            })
            ->toArray();

        $changes = [
            'customers' => $customers,
            'products' => $products,
        ];

        $newCursors = [
            'customers' => (string) time(),
            'products' => (string) time(),
        ];

        return response()->json([
            'changes' => $changes,
            'new_cursors' => $newCursors,
        ]);
    }

    /**
     * كشف حساب العميل في فترة زمنية (من/إلى).
     * يجمع فواتير البيع والتحصيلات ويحسب الرصيد الجاري لكل حركة.
     */
    public function customerStatement(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;
        $branchId = $request->header('X-Branch-Id');
        $branchId = $branchId ? (int) $branchId : null;

        $customerId = (int) $validated['customer_id'];
        $from = $validated['from_date'];
        $to = $validated['to_date'];

        $customer = DB::table('customers')
            ->where('id', $customerId)
            ->where('company_id', $companyId)
            ->first(['id', 'code', 'name_ar', 'name_en']);

        if (!$customer) {
            return response()->json(['message' => 'العميل غير موجود'], 404);
        }

        $invoiceScope = function ($q) use ($companyId, $branchId, $customerId) {
            return $q->where('company_id', $companyId)
                ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'cancelled')
                ->whereNull('deleted_at');
        };

        $collectionScope = function ($q) use ($companyId, $branchId, $customerId) {
            return $q->where('company_id', $companyId)
                ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('status', 'approved')
                ->whereNull('collections.deleted_at');
        };

        // الرصيد الافتتاحي قبل تاريخ البداية
        $openingDebit = (float) $invoiceScope(DB::table('sales_invoices'))
            ->where('invoice_date', '<', $from)
            ->sum('net_total');
        $openingCredit = (float) $collectionScope(DB::table('collections'))
            ->where('collection_date', '<', $from)
            ->sum('amount');
        $openingBalance = round($openingDebit - $openingCredit, 2);

        $invoices = $invoiceScope(DB::table('sales_invoices'))
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'net_total',
                'paid_amount',
                'remaining_amount',
            ]);

        $collections = $collectionScope(DB::table('collections'))
            ->whereBetween('collection_date', [$from, $to])
            ->leftJoin('payment_methods', 'collections.payment_method_id', '=', 'payment_methods.id')
            ->orderBy('collection_date')
            ->orderBy('collections.id')
            ->get([
                'collections.id',
                'collections.collection_no',
                'collections.collection_date',
                'collections.amount',
                'payment_methods.name as payment_method_name',
            ]);

        $events = [];
        foreach ($invoices as $inv) {
            $events[] = ['date' => $inv->invoice_date, 'type' => 'invoice', 'ref' => $inv];
        }
        foreach ($collections as $col) {
            $events[] = ['date' => $col->collection_date, 'type' => 'collection', 'ref' => $col];
        }

        usort($events, function ($a, $b) {
            if ($a['date'] === $b['date']) {
                // الفواتير قبل التحصيلات في نفس اليوم
                return $a['type'] === $b['type'] ? 0 : ($a['type'] === 'invoice' ? -1 : 1);
            }
            return strcmp($a['date'], $b['date']);
        });

        $balance = $openingBalance;
        $rows = [];
        foreach ($events as $ev) {
            if ($ev['type'] === 'invoice') {
                $inv = $ev['ref'];
                $debit = round((float) $inv->net_total, 2);
                $credit = 0.0;
                $isPaid = $inv->net_total > 0 && (float) $inv->paid_amount >= (float) $inv->net_total;
                $movementType = 'فاتورة بيع';
                $paymentMethod = $isPaid ? 'نقدي' : 'آجل (ذمم مدينة)';
                $referenceNo = $inv->invoice_no;
            } else {
                $col = $ev['ref'];
                $debit = 0.0;
                $credit = round((float) $col->amount, 2);
                $movementType = 'تحصيل';
                $paymentMethod = $col->payment_method_name ?: 'نقدي';
                $referenceNo = $col->collection_no;
            }

            $balance = round($balance + $debit - $credit, 2);
            $rows[] = [
                'date' => $ev['date'],
                'reference_no' => $referenceNo,
                'movement_type' => $movementType,
                'payment_method' => $paymentMethod,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name_ar ?? $customer->name_en,
            ],
            'from_date' => $from,
            'to_date' => $to,
            'opening_balance' => $openingBalance,
            'closing_balance' => round($balance, 2),
            'rows' => $rows,
        ]);
    }

    /**
     * تقرير مبيعات العميل في فترة زمنية (من/إلى).
     * كل منتج يظهر في عمود مستقل، وعمود الإجمالي لقيمة المبيعات اليومية.
     */
    public function customerSalesReport(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;
        $branchId = $request->header('X-Branch-Id');
        $branchId = $branchId ? (int) $branchId : null;

        $customerId = (int) $validated['customer_id'];
        $from = $validated['from_date'];
        $to = $validated['to_date'];

        $invoices = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'invoice_no', 'invoice_date', 'net_total']);

        if ($invoices->isEmpty()) {
            return response()->json([
                'from_date' => $from,
                'to_date' => $to,
                'products' => [],
                'rows' => [],
            ]);
        }

        $invoiceIds = $invoices->pluck('id')->all();

        $items = DB::table('sales_invoice_items')
            ->whereIn('sales_invoice_id', $invoiceIds)
            ->whereNull('sales_invoice_items.deleted_at')
            ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
            ->select(
                'sales_invoice_items.sales_invoice_id',
                'sales_invoice_items.item_id',
                'items.code',
                'items.name_ar',
                'items.name_en',
                'sales_invoice_items.qty',
                'sales_invoice_items.net_amount'
            )
            ->get();

        $productMap = [];
        foreach ($items as $it) {
            if (!isset($productMap[$it->item_id])) {
                $productMap[$it->item_id] = [
                    'id' => $it->item_id,
                    'code' => $it->code,
                    'name' => $it->name_ar ?? $it->name_en,
                ];
            }
        }
        $products = array_values($productMap);

        $byDate = [];
        foreach ($invoices as $inv) {
            $date = $inv->invoice_date;
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'date' => $date,
                    'invoice_count' => 0,
                    'quantities' => [],
                    'amounts' => [],
                    'total_qty' => 0.0,
                    'total_amount' => 0.0,
                    'invoice_nos' => [],
                ];
            }
            $byDate[$date]['invoice_count']++;
            $byDate[$date]['total_amount'] += (float) $inv->net_total;
            $byDate[$date]['invoice_nos'][] = $inv->invoice_no;
        }

        foreach ($items as $it) {
            $inv = $invoices->firstWhere('id', $it->sales_invoice_id);
            if (!$inv) {
                continue;
            }
            $date = $inv->invoice_date;
            $pid = $it->item_id;
            $qty = (float) $it->qty;
            $amount = (float) ($it->net_amount ?? 0);
            $byDate[$date]['quantities'][$pid] = ($byDate[$date]['quantities'][$pid] ?? 0) + $qty;
            $byDate[$date]['amounts'][$pid] = ($byDate[$date]['amounts'][$pid] ?? 0) + $amount;
            $byDate[$date]['total_qty'] += $qty;
        }

        $rows = array_values($byDate);
        foreach ($rows as &$row) {
            $row['total_qty'] = round($row['total_qty'], 2);
            $row['total_amount'] = round($row['total_amount'], 2);
        }
        unset($row);

        return response()->json([
            'from_date' => $from,
            'to_date' => $to,
            'products' => $products,
            'rows' => $rows,
        ]);
    }

    /**
     * تفاصيل فاتورة بيع واحدة لطباعتها من شاشة كشف الحساب.
     */
    public function invoiceDetails(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|string',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;

        $invoice = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->where('invoice_no', $validated['invoice_no'])
            ->whereNull('deleted_at')
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'الفاتورة غير موجودة'], 404);
        }

        $customer = DB::table('customers')
            ->where('id', $invoice->customer_id)
            ->first(['name_ar', 'name_en', 'code', 'phone', 'mobile', 'address_line']);

        $items = DB::table('sales_invoice_items')
            ->where('sales_invoice_id', $invoice->id)
            ->whereNull('sales_invoice_items.deleted_at')
            ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
            ->select(
                'sales_invoice_items.qty',
                'sales_invoice_items.price',
                'sales_invoice_items.gross_amount',
                'sales_invoice_items.net_amount',
                'items.code',
                'items.name_ar',
                'items.name_en'
            )
            ->get();

        $mappedItems = $items->map(function ($it) {
            return [
                'item_name' => $it->name_ar ?? $it->name_en,
                'code' => $it->code,
                'qty' => (float) $it->qty,
                'price' => (float) $it->price,
                'gross_amount' => (float) ($it->net_amount ?? $it->gross_amount),
            ];
        });

        return response()->json([
            'invoice' => [
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'invoice_time' => $invoice->invoice_time,
                'customer_name' => $customer ? ($customer->name_ar ?? $customer->name_en) : '',
                'customer_code' => $customer ? $customer->code : '',
                'customer_phone' => $customer ? ($customer->phone ?? $customer->mobile) : '',
                'customer_address' => $customer ? $customer->address_line : '',
                'subtotal' => (float) $invoice->subtotal,
                'tax_total' => (float) ($invoice->tax_total ?? 0),
                'discount_total' => (float) ($invoice->invoice_discount_total ?? 0),
                'net_total' => (float) $invoice->net_total,
                'total' => (float) $invoice->net_total,
                'paid_amount' => (float) $invoice->paid_amount,
                'remaining_amount' => (float) $invoice->remaining_amount,
                'items' => $mappedItems,
            ],
        ]);
    }

    /**
     * Get payment methods for a specific invoice by client_uuid.
     */
    public function invoicePaymentMethods(Request $request, string $clientUuid)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $invoice = SalesInvoice::where('company_id', $companyId)
            ->where('client_uuid', $clientUuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'الفاتورة غير موجودة'], 404);
        }

        $paymentMethods = SalesInvoicePaymentMethod::where('sales_invoice_id', $invoice->id)
            ->with('paymentMethod')
            ->get()
            ->map(fn ($pm) => [
                'id' => $pm->id,
                'method_code' => $pm->method_code,
                'method_name' => $pm->paymentMethod?->name ?? $pm->method_code,
                'amount' => (float) $pm->amount,
                'bank_account_id' => $pm->bank_account_id,
                'notes' => $pm->notes,
            ]);

        return response()->json([
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'client_uuid' => $clientUuid,
            'paid_amount' => (float) $invoice->paid_amount,
            'remaining_amount' => (float) $invoice->remaining_amount,
            'payment_methods' => $paymentMethods,
        ]);
    }
}
