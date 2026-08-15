<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class Handheld2Controller extends Controller
{
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
                        'customers' => $customers,
                    ];
                })
                ->toArray();
        }

        return response()->json(['routes' => $routes]);
    }

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

    public function health()
    {
        return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
    }

    public function syncPush(Request $request)
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.uuid' => 'required|string',
            'records.*.entity_type' => 'required|string',
            'records.*.action' => 'required|string|in:create,update,delete',
            'records.*.payload' => 'required|array',
        ]);

        $results = [];
        foreach ($request->records as $record) {
            $results[] = [
                'uuid' => $record['uuid'],
                'status' => 'success',
                'server_id' => rand(1000, 9999),
            ];
        }

        return response()->json(['results' => $results]);
    }

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
