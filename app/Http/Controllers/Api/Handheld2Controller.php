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
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Inventory\RepItemDistribution;
use App\Models\Inventory\Device;
use App\Models\Warehouse;

class Handheld2Controller extends Controller
{
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
        $employeeId = $resources['employee_id'];

        $routes = [];
        $customersList = [];
        if ($routeIds->isNotEmpty()) {
            $routes = DB::table('routes')
                ->whereIn('routes.id', $routeIds)
                ->where('routes.is_active', true)
                ->whereNull('routes.deleted_at')
                ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
                ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'sales_territories.name_ar as territory_name')
                ->get()
                ->map(function ($route) {
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
                        ->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'code' => $c->code,
                                'name' => $c->name_ar ?? $c->name_en,
                                'phone' => $c->phone,
                                'mobile' => $c->mobile,
                                'address' => $c->address_line,
                                'latitude' => $c->latitude,
                                'longitude' => $c->longitude,
                            ];
                        });

                    return [
                        'id' => $route->id,
                        'code' => $route->code,
                        'name' => $route->name_ar ?? $route->name_en,
                        'territory_name' => $route->territory_name,
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
                ->whereIn('id', $allCustomerIds)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line', 'latitude', 'longitude'])
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'code' => $c->code,
                        'name' => $c->name_ar ?? $c->name_en,
                        'phone' => $c->phone,
                        'mobile' => $c->mobile,
                        'address' => $c->address_line,
                        'latitude' => $c->latitude,
                        'longitude' => $c->longitude,
                    ];
                })
                ->toArray();
        }

            $loadRequests = [];
        if ($employeeId) {
            $loadRequests = DB::table('load_requests')
                ->where('employee_id', $employeeId)
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

        $items = DB::table('items')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name_ar ?? $item->name_en,
                ];
            })
            ->toArray();

        return response()->json([
            'routes' => $routes,
            'customers' => $customersList,
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
     * دالة معالجة: loadOrders — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Handheld2).
     */
    public function loadOrders(Request $request)
    {
        $user = $request->user();
        $resources = $this->getAssignmentResources($user->id);
        $employeeId = $request->input('employee_id') ?? $resources['employee_id'];

        if (!$employeeId) {
            return response()->json(['open' => [], 'closed' => []]);
        }

        $issueOrders = DB::table('issue_orders')
            ->where('issue_orders.employee_id', $employeeId)
            ->whereNull('issue_orders.deleted_at')
            ->leftJoin('load_requests', 'issue_orders.load_request_id', '=', 'load_requests.id')
            ->select(
                'issue_orders.*',
                'load_requests.request_no as load_request_no',
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

            $items = DB::table('issue_order_items')
                ->where('issue_order_id', $io->id)
                ->whereNull('deleted_at')
                ->get()
                ->map(function ($item) {
                    $product = DB::table('items')->where('id', $item->item_id)->first();
                    return [
                        'item_name' => $product ? ($product->name_ar ?? $product->name_en ?? '') : '',
                        'item_code' => $product ? $product->code : '',
                        'quantity' => $item->issued_quantity ?? 0,
                        'unit_price' => $item->purchase_price ?? 0,
                        'line_total' => $item->total_amount ?? 0,
                    ];
                });

            $orderData = [
                'id' => $io->id,
                'issue_no' => $io->issue_no,
                'load_request_id' => $io->load_request_id,
                'load_request_no' => $io->load_request_no,
                'issue_date' => $io->issue_date,
                'status' => $io->status,
                'total_items_count' => $io->total_items_count,
                'total_quantity' => $io->total_quantity,
                'total_amount' => $io->total_amount,
                'returned_quantity' => $totalReturnedQty,
                'items' => $items,
            ];

            if ($hasReturn) {
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
        $employee = DB::table('employees')->where('user_id', $user->id)->first();
        $routeIds = collect();
        $territoryIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('employee_id', $employee->id)
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
        $employee = DB::table('employees')->where('user_id', $user->id)->first();

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
        $employee = DB::table('employees')->where('user_id', $user->id)->first();
        $routeIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->distinct('route_id')
                ->pluck('route_id');
        }

        $routes = [];
        if ($routeIds->isNotEmpty()) {
            $routes = DB::table('routes')
                ->whereIn('routes.id', $routeIds)
                ->where('routes.is_active', true)
                ->whereNull('routes.deleted_at')
                ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
                ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'sales_territories.name_ar as territory_name')
                ->get()
                ->map(function ($route) {
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
                        ->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'code' => $c->code,
                                'name' => $c->name_ar ?? $c->name_en,
                                'phone' => $c->phone,
                                'mobile' => $c->mobile,
                                'address' => $c->address_line,
                                'latitude' => $c->latitude,
                                'longitude' => $c->longitude,
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
        $employee = DB::table('employees')->where('user_id', $userId)->first();

        if (!$employee) {
            return [
                'warehouse' => null,
                'treasury' => null,
                'sales_area' => null,
                'employee_id' => null,
                'route_ids' => collect(),
            ];
        }

        $routeIds = DB::table('route_schedules')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->distinct('route_id')
            ->pluck('route_id');

        $assignment = DB::table('salesman_assignments')
            ->where('employee_id', $employee->id)
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
        $employee = DB::table('employees')->where('user_id', $user->id)->first();

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
            ->where('employee_id', $employee->id)
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
        $employee = DB::table('employees')->where('user_id', $user->id)->first();
        $routeIds = collect();

        if ($employee) {
            $routeIds = DB::table('route_schedules')
                ->where('employee_id', $employee->id)
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
}
